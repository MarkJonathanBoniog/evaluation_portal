<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SuperiorEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeanDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = [
            'q'          => trim((string) $request->get('q', '')),
            'college_id' => $request->get('college_id'),
        ];

        // 1) Colleges this dean is assigned to
        $collegeOptions = $user->deanColleges()
            ->select('colleges.*')
            ->orderBy('colleges.name')
            ->get();
        $collegeIds = $collegeOptions->pluck('id');

        if ($filters['college_id']) {
            $collegeIds = $collegeIds->filter(fn ($id) => (int) $id === (int) $filters['college_id']);
        }

        if ($collegeIds->isEmpty()) {
            return view('dashboards.instructor.dean.index', [
                'periods'          => collect(),
                'chairsByCollege'  => collect(),
                'evaluationStatus' => collect(),
                'filters'          => $filters,
                'colleges'         => $collegeOptions,
            ]);
        }

        // 2) Academic periods in those colleges
        $periods = AcademicPeriod::with(['college', 'department'])
            ->whereIn('college_id', $collegeIds)
            ->when($filters['college_id'], fn ($q) => $q->where('college_id', $filters['college_id']))
            ->orderByDesc('year_start')
            ->orderByDesc('term')
            ->get()
            // Guard against accidental duplicate period rows per college/term/year
            ->unique(fn ($p) => $p->college_id . '-' . $p->year_start . '-' . $p->year_end . '-' . $p->term)
            ->values();

        // Keep only the latest period per filters
        $latestPeriod = $periods->first();
        $periods = $latestPeriod ? collect([$latestPeriod]) : collect();

        $periodIds = $periods->pluck('id');

        $periodIds = $periods->pluck('id');

        // 3) Chairmen under those colleges (via chairman_assignments + departments)
        $chairs = DB::table('chairman_assignments')
            ->join('users', 'users.id', '=', 'chairman_assignments.user_id')
            ->join('departments', 'departments.id', '=', 'chairman_assignments.department_id')
            ->whereIn('departments.college_id', $collegeIds)
            ->when($filters['college_id'], fn ($q) => $q->where('departments.college_id', $filters['college_id']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('users.name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('departments.name', 'like', '%' . $filters['q'] . '%');
                });
            })
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
            'filters'          => $filters,
            'colleges'         => $collegeOptions,
        ]);
    }
}
