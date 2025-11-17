<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SuperiorEvaluation;
use Illuminate\Support\Facades\DB;

class CedDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1) Colleges this CED oversees
        $collegeIds = $user->cedColleges()
            ->pluck('colleges.id');

        if ($collegeIds->isEmpty()) {
            return view('dashboards.instructor.ced.index', [
                'periods'          => collect(),
                'deansByCollege'   => collect(),
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

        // 3) Deans for those colleges (via dean_assignments)
        $deans = DB::table('dean_assignments')
            ->join('users', 'users.id', '=', 'dean_assignments.user_id')
            ->join('colleges', 'colleges.id', '=', 'dean_assignments.college_id')
            ->whereIn('dean_assignments.college_id', $collegeIds)
            ->select(
                'dean_assignments.user_id as dean_id',
                'users.name as dean_name',
                'colleges.id as college_id',
                'colleges.name as college_name'
            )
            ->orderBy('colleges.name')
            ->orderBy('users.name')
            ->get()
            ->groupBy('college_id');

        // 4) Existing evaluations by this CED
        $evaluations = SuperiorEvaluation::where('user_id', $user->id)
            ->where('evaluated_as', 'ced')
            ->whereIn('academic_period_id', $periodIds)
            ->get();

        $evaluationStatus = $evaluations->mapWithKeys(function ($eval) {
            $key = $eval->academic_period_id . '-' . $eval->instructor_user_id;
            return [$key => true];
        });

        return view('dashboards.instructor.ced.index', [
            'periods'          => $periods,
            'deansByCollege'   => $deans,
            'evaluationStatus' => $evaluationStatus,
        ]);
    }
}
