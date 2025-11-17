<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SuperiorEvaluation;
use Illuminate\Support\Facades\DB;

class DeanDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1) Colleges this dean is assigned to
        $collegeIds = $user->deanColleges()
            ->pluck('colleges.id');

        if ($collegeIds->isEmpty()) {
            return view('dashboards.instructor.dean.index', [
                'periods'          => collect(),
                'chairsByCollege'  => collect(),
                'evaluationStatus' => collect(),
            ]);
        }

        // 2) Academic periods in those colleges
        $periods = AcademicPeriod::with(['college', 'department'])
            ->whereIn('college_id', $collegeIds)
            ->orderByDesc('year_start')
            ->orderByDesc('term')
            ->get();

        $periodIds = $periods->pluck('id');

        // 3) Chairmen under those colleges (via chairman_assignments + departments)
        $chairs = DB::table('chairman_assignments')
            ->join('users', 'users.id', '=', 'chairman_assignments.user_id')
            ->join('departments', 'departments.id', '=', 'chairman_assignments.department_id')
            ->whereIn('departments.college_id', $collegeIds)
            ->select(
                'chairman_assignments.user_id as chairman_id',
                'users.name as chairman_name',
                'departments.id as department_id',
                'departments.name as department_name',
                'departments.college_id'
            )
            ->orderBy('departments.name')
            ->orderBy('users.name')
            ->get()
            ->groupBy('college_id'); // group chairs by college

        // 4) Existing evaluations by this dean (for status)
        $evaluations = SuperiorEvaluation::where('user_id', $user->id)
            ->where('evaluated_as', 'dean')
            ->whereIn('academic_period_id', $periodIds)
            ->get();

        // Map: "periodId-subjectUserId" => true
        $evaluationStatus = $evaluations->mapWithKeys(function ($eval) {
            $key = $eval->academic_period_id . '-' . $eval->instructor_user_id;
            return [$key => true];
        });

        return view('dashboards.instructor.dean.index', [
            'periods'          => $periods,
            'chairsByCollege'  => $chairs,
            'evaluationStatus' => $evaluationStatus,
        ]);
    }
}
