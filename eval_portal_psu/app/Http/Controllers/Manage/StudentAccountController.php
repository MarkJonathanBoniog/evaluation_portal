<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateStudentAccountsJob;
use App\Models\AcademicPeriod;
use App\Models\College;
use App\Models\Department;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudentAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Scope: chairmen limited to their departments unless also system admin
        $departmentScope = collect();
        if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
            $departmentScope = $user->chairmanAssignments()->pluck('department_id');
        }

        $chairmanDepartment = null;
        if ($departmentScope->isNotEmpty()) {
            $firstDept = Department::with('college')->find($departmentScope->first());
            if ($firstDept) {
                $chairmanDepartment = [
                    'department_id'   => $firstDept->id,
                    'department_name' => $firstDept->name,
                    'college_id'      => $firstDept->college_id,
                    'college_name'    => $firstDept->college?->name,
                ];
            }
        }

        $query = User::role('student')
            ->with(['studentProfile.department.college']);

        if ($departmentScope->isNotEmpty()) {
            $query->whereHas('studentProfile', function ($q) use ($departmentScope) {
                $q->whereIn('department_id', $departmentScope);
            });
        }

        if ($search = $request->string('q')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', function ($sq) use ($search) {
                        $sq->where('student_number', 'like', "%{$search}%");
                    });
            });
        }

        $collegeId    = filter_var($request->input('college_id'), FILTER_VALIDATE_INT);
        $departmentId = filter_var($request->input('department_id'), FILTER_VALIDATE_INT);

        if ($collegeId) {
            $query->whereHas('studentProfile.department', fn ($q) => $q->where('college_id', $collegeId));
        }

        if ($departmentId) {
            $query->whereHas('studentProfile', fn ($q) => $q->where('department_id', $departmentId));
        }

        $students = $query
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        $deptQuery = Department::with('college')->orderBy('name');
        if ($departmentScope->isNotEmpty()) {
            $deptQuery->whereIn('id', $departmentScope);
        }

        $departments = $deptQuery->get(['id','name','college_id']);
        $colleges    = College::orderBy('name')->get(['id','name']);

        return view('manage.students.index', compact('students','colleges','departments','chairmanDepartment'));
    }

    public function update(Request $request, User $student)
    {
        abort_unless($student->hasRole('student'), 404);

        $profile = $student->studentProfile ?? new StudentProfile(['user_id' => $student->id]);

        $data = $request->validate([
            'name'           => ['required','string','max:255'],
            'email'          => ['required','email','max:255', Rule::unique('users','email')->ignore($student->id)],
            'student_number' => ['required','string','max:32', Rule::unique('student_profiles','student_number')->ignore($profile->id)],
            'department_id'  => ['nullable','exists:departments,id'],
        ]);

        DB::transaction(function () use ($student, $profile, $data) {
            $student->update([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            $profile->fill([
                'student_number' => $data['student_number'],
                'department_id'  => $data['department_id'] ?? null,
            ]);

            $profile->user_id = $student->id;
            $profile->save();
        });

        return back()->with('status', 'Student updated successfully.');
    }

    public function destroy(User $student)
    {
        abort_unless($student->hasRole('student'), 404);

        DB::transaction(function () use ($student) {
            DB::table('section_student')->where('student_user_id', $student->id)->delete();
            $student->delete();
        });

        return back()->with('status', 'Student deleted.');
    }

    public function generate(Request $request)
    {
        $user = $request->user();

        abort_unless($user->hasAnyRole(['chairman','systemadmin']), 403);

        $departmentScope = collect();
        if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
            $departmentScope = $user->chairmanAssignments()->pluck('department_id');
        }

        $rules = [
            'start_student_number' => ['required','regex:/^[0-9]{4}$/'],
            'end_student_number'   => ['required','regex:/^[0-9]{4}$/'],
            'password'             => ['required','string','min:6','max:255'],
        ];

        if ($user->hasRole('systemadmin')) {
            $rules['college_id']    = ['required','exists:colleges,id'];
            $rules['department_id'] = ['required','exists:departments,id'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'start_student_number.regex' => 'Start student number must be 4 digits (e.g., 0001).',
            'end_student_number.regex'   => 'End student number must be 4 digits (e.g., 0005).',
        ]);

        $validator->after(function ($validator) use ($user, $request, $departmentScope) {
            $start = (int) $request->input('start_student_number');
            $end   = (int) $request->input('end_student_number');

            if ($start && $end && $end < $start) {
                $validator->errors()->add('end_student_number', 'End number must be greater than or equal to start.');
            }

            if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
                if ($departmentScope->isEmpty()) {
                    $validator->errors()->add('department_id', 'No department assignment found for your role.');
                }
            }

            if ($user->hasRole('systemadmin')) {
                $deptId    = $request->input('department_id');
                $collegeId = $request->input('college_id');

                if ($deptId && $collegeId) {
                    $matches = Department::where('id', $deptId)
                        ->where('college_id', $collegeId)
                        ->exists();

                    if (! $matches) {
                        $validator->errors()->add('department_id', 'Department does not belong to the selected college.');
                    }
                }
            }
        });

        $data = $validator->validate();

        $departmentId = $data['department_id'] ?? null;
        $collegeId    = $data['college_id'] ?? null;

        if (! $user->hasRole('systemadmin')) {
            $dept = Department::with('college')->find($departmentScope->first());
            if (! $dept) {
                return response()->json([
                    'message' => 'No department assignment found for your role.',
                ], 422);
            }

            $departmentId = $dept->id;
            $collegeId    = $dept->college_id;
        }

        $departmentId = (int) $departmentId;
        $collegeId    = (int) $collegeId;

        GenerateStudentAccountsJob::dispatch(
            (string) $data['start_student_number'],
            (string) $data['end_student_number'],
            (string) $data['password'],
            $collegeId,
            $departmentId,
            $user->id
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Account generation has been applied.']);
        }

        return back()->with('status', 'Account generation has been applied.');
    }
}
