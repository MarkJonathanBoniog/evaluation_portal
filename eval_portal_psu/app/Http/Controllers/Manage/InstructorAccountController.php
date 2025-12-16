<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\ChairmanAssignment;
use App\Models\College;
use App\Models\Department;
use App\Models\DeanAssignment;
use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $departmentScope = collect();
        if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
            $departmentScope = $user->chairmanAssignments()->pluck('department_id');
        }

        $query = User::role('instructor')
            ->with(['instructorProfile.department.college']);

        if ($departmentScope->isNotEmpty()) {
            $query->whereHas('instructorProfile', function ($q) use ($departmentScope) {
                $q->whereIn('department_id', $departmentScope);
            });
        }

        if ($search = $request->string('q')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('instructorProfile', function ($sq) use ($search) {
                        $sq->where('faculty_rank', 'like', "%{$search}%");
                    });
            });
        }

        $collegeId    = filter_var($request->input('college_id'), FILTER_VALIDATE_INT);
        $departmentId = filter_var($request->input('department_id'), FILTER_VALIDATE_INT);

        if ($collegeId) {
            $query->whereHas('instructorProfile.department', fn ($q) => $q->where('college_id', $collegeId));
        }

        if ($departmentId) {
            $query->whereHas('instructorProfile', fn ($q) => $q->where('department_id', $departmentId));
        }

        $instructors = $query
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        $deptQuery = Department::with('college')->orderBy('name');
        if ($departmentScope->isNotEmpty()) {
            $deptQuery->whereIn('id', $departmentScope);
        }

        $departments = $deptQuery->get(['id','name','college_id']);
        $colleges    = College::orderBy('name')->get(['id','name']);

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

        return view('manage.instructors.index', compact('instructors','departments','colleges','chairmanDepartment'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $departmentScope = collect();
        if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
            $departmentScope = $user->chairmanAssignments()->pluck('department_id');
        }

        $rules = [
            'name'          => ['required','string','max:255'],
            'email'         => ['required','email','max:255','unique:users,email'],
            'faculty_rank'  => ['required','string','max:255'],
            'department_id' => ['required','exists:departments,id'],
            'college_id'    => [$user->hasRole('systemadmin') ? 'required' : 'nullable','exists:colleges,id'],
            'role_assignment' => ['nullable', Rule::in(['chairman','dean'])],
        ];

        $data = $request->validate($rules);

        $department = Department::with('college')->find($data['department_id']);

        if (! $department) {
            return back()->withErrors(['department_id' => 'Department not found.'])->withInput();
        }

        if ($departmentScope->isNotEmpty() && ! $departmentScope->contains($department->id)) {
            return back()->withErrors(['department_id' => 'You can only assign instructors within your department.'])->withInput();
        }

        if ($data['college_id'] && $department->college_id && (int) $data['college_id'] !== (int) $department->college_id) {
            return back()->withErrors(['department_id' => 'Department does not belong to the selected college.'])->withInput();
        }

        DB::transaction(function () use ($data, $department) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
            ]);

            $user->assignRole('instructor');

            InstructorProfile::create([
                'user_id'        => $user->id,
                'instructor_uid' => Str::uuid()->toString(),
                'faculty_rank'   => $data['faculty_rank'],
                'department_id'  => $department->id,
            ]);

            $this->applyRoleAssignment($request->user(), $user, $department, $data['role_assignment'] ?? null);
        });

        return back()->with('status', 'Instructor created successfully.');
    }

    public function update(Request $request, User $instructor)
    {
        abort_unless($instructor->hasRole('instructor'), 404);

        $user = $request->user();

        $departmentScope = collect();
        if ($user->hasRole('chairman') && ! $user->hasRole('systemadmin')) {
            $departmentScope = $user->chairmanAssignments()->pluck('department_id');
        }

        $profile = $instructor->instructorProfile ?? new InstructorProfile([
            'user_id'        => $instructor->id,
            'instructor_uid' => Str::uuid()->toString(),
        ]);

        $rules = [
            'name'          => ['required','string','max:255'],
            'email'         => ['required','email','max:255', Rule::unique('users','email')->ignore($instructor->id)],
            'faculty_rank'  => ['required','string','max:255'],
            'department_id' => ['required','exists:departments,id'],
            'college_id'    => [$user->hasRole('systemadmin') ? 'required' : 'nullable','exists:colleges,id'],
            'role_assignment' => ['nullable', Rule::in(['chairman','dean'])],
        ];

        $data = $request->validate($rules);

        $department = Department::with('college')->find($data['department_id']);

        if (! $department) {
            return back()->withErrors(['department_id' => 'Department not found.'])->withInput();
        }

        if ($departmentScope->isNotEmpty() && ! $departmentScope->contains($department->id)) {
            return back()->withErrors(['department_id' => 'You can only assign instructors within your department.'])->withInput();
        }

        if ($data['college_id'] && $department->college_id && (int) $data['college_id'] !== (int) $department->college_id) {
            return back()->withErrors(['department_id' => 'Department does not belong to the selected college.'])->withInput();
        }

        DB::transaction(function () use ($request, $instructor, $profile, $data, $department) {
            $instructor->update([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            $profile->fill([
                'faculty_rank'  => $data['faculty_rank'],
                'department_id' => $department->id,
            ]);

            $profile->user_id = $instructor->id;
            if (empty($profile->instructor_uid)) {
                $profile->instructor_uid = Str::uuid()->toString();
            }
            $profile->save();

            $this->applyRoleAssignment($request->user(), $instructor, $department, $data['role_assignment'] ?? null);
        });

        return back()->with('status', 'Instructor updated successfully.');
    }

    public function destroy(User $instructor)
    {
        abort_unless($instructor->hasRole('instructor'), 404);

        DB::transaction(function () use ($instructor) {
            DB::table('sections')->where('instructor_user_id', $instructor->id)->update(['instructor_user_id' => null]);
            $instructor->delete();
        });

        return back()->with('status', 'Instructor deleted.');
    }

    protected function applyRoleAssignment(User $actor, User $target, Department $department, ?string $roleAssignment): void
    {
        if (! $actor->hasRole('systemadmin')) {
            // Only system admins can assign chair/dean
            return;
        }

        if ($roleAssignment === 'chairman') {
            // Remove previous chair for this department
            $previous = ChairmanAssignment::where('department_id', $department->id)->get();
            foreach ($previous as $assignment) {
                $prevUser = $assignment->user;
                $assignment->delete();
                if ($prevUser && $prevUser->hasRole('chairman')) {
                    $prevUser->removeRole('chairman');
                }
            }

            ChairmanAssignment::updateOrCreate(
                ['department_id' => $department->id],
                ['user_id' => $target->id]
            );

            if (! $target->hasRole('chairman')) {
                $target->assignRole('chairman');
            }
        } elseif ($roleAssignment === 'dean') {
            $collegeId = $department->college_id;
            if (! $collegeId) {
                return;
            }

            $previous = DeanAssignment::where('college_id', $collegeId)->get();
            foreach ($previous as $assignment) {
                $prevUser = $assignment->user;
                $assignment->delete();
                if ($prevUser && $prevUser->hasRole('dean')) {
                    $prevUser->removeRole('dean');
                }
            }

            DeanAssignment::updateOrCreate(
                ['college_id' => $collegeId],
                ['user_id' => $target->id]
            );

            if (! $target->hasRole('dean')) {
                $target->assignRole('dean');
            }
        }
    }
}
