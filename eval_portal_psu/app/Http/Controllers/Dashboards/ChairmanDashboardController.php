<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Section;
use App\Models\SuperiorEvaluation;
use Illuminate\Support\Facades\DB;

class ChairmanDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1) Departments that this user chairs
        $departmentIds = $user->chairedDepartments()
            ->pluck('departments.id');

        if ($departmentIds->isEmpty()) {
            return view('dashboards.instructor.chairman.index', [
                'periods'             => collect(),
                'instructorsByPeriod' => collect(),
                'evaluationStatus'    => collect(),
                'deansByCollege'      => collect(),
            ]);
        }

        // 2) Academic periods for those departments
        $periods = AcademicPeriod::with(['college', 'department'])
            ->whereIn('department_id', $departmentIds)
            ->orderByDesc('year_start')
            ->orderByDesc('term')
            ->get();

        $periodIds = $periods->pluck('id');

        if ($periodIds->isEmpty()) {
            return view('dashboards.instructor.chairman.index', [
                'periods'             => $periods,
                'instructorsByPeriod' => collect(),
                'evaluationStatus'    => collect(),
                'deansByCollege'      => collect(),
            ]);
        }

        // 2.5) Colleges covered by these periods
        $collegeIds = $periods->pluck('college_id')->filter()->unique();

        // 5) Deans per college (so chairman can evaluate dean too)
        if ($collegeIds->isEmpty()) {
            $deansByCollege = collect();
            $deanIds        = collect();
        } else {
            // Build once so we can both group and pluck IDs
            $deanAssignments = DB::table('dean_assignments')
                ->join('users', 'users.id', '=', 'dean_assignments.user_id')
                ->whereIn('dean_assignments.college_id', $collegeIds)
                ->select(
                    'dean_assignments.user_id as dean_id',
                    'users.name as dean_name',
                    'dean_assignments.college_id'
                )
                ->orderBy('users.name')
                ->get();

            // For the view: grouped by college
            $deansByCollege = $deanAssignments->groupBy('college_id');

            // For filtering instructors: flat list of dean user IDs
            $deanIds = $deanAssignments->pluck('dean_id')->unique();
        }

        // 3) Instructors teaching any section in those periods (EXCLUDING this chairman)
        // AND excluding anyone who is also a dean (Option A)
        $instructorsByPeriod = Section::query()
            ->join('users', 'users.id', '=', 'sections.instructor_user_id')
            ->leftJoin('instructor_profiles', 'instructor_profiles.user_id', '=', 'users.id')
            ->whereIn('sections.academic_period_id', $periodIds)
            ->where('users.id', '!=', $user->id) // chairman must not evaluate himself as instructor
            ->when($deanIds->isNotEmpty(), function ($q) use ($deanIds) {
                $q->whereNotIn('users.id', $deanIds); // EXCLUDE deans from instructor list
            })
            ->select(
                'sections.academic_period_id',
                'users.id as instructor_id',
                'users.name as instructor_name',
                'instructor_profiles.instructor_uid'
            )
            ->groupBy(
                'sections.academic_period_id',
                'users.id',
                'users.name',
                'instructor_profiles.instructor_uid'
            )
            ->orderBy('users.name')
            ->get()
            ->groupBy('academic_period_id');

        // 4) Existing evaluations by this chairman (for status — both instructors & dean)
        $evaluations = SuperiorEvaluation::where('user_id', $user->id)
            ->where('evaluated_as', 'chairman')
            ->whereIn('academic_period_id', $periodIds)
            ->get();

        // Map for quick lookup: "periodId-subjectUserId" => true
        $evaluationStatus = $evaluations->mapWithKeys(function ($eval) {
            $key = $eval->academic_period_id . '-' . $eval->instructor_user_id;
            return [$key => true];
        });

        return view('dashboards.instructor.chairman.index', [
            'periods'             => $periods,
            'instructorsByPeriod' => $instructorsByPeriod,
            'evaluationStatus'    => $evaluationStatus,
            'deansByCollege'      => $deansByCollege,
        ]);
    }
}
