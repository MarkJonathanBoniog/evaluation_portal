<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\College;
use App\Models\Department;
use App\Models\EvaluationRecord;
use App\Models\Section;
use App\Models\SectionStudent;
use App\Models\SuperiorEvaluation;
use App\Models\User;
use App\Models\DevelopmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EvaluationSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user          = $request->user();
        $isSystemAdmin = $user->hasRole('systemadmin');

        abort_unless($isSystemAdmin || $user->hasRole('chairman'), 403);

        $filters = [
            'q'             => trim((string) $request->get('q', '')),
            'college_id'    => $isSystemAdmin ? (int) $request->get('college_id') ?: null : null,
            'department_id' => $isSystemAdmin ? (int) $request->get('department_id') ?: null : null,
        ];

        $departmentScope = $isSystemAdmin
            ? collect()
            : $user->chairedDepartments()->pluck('departments.id');

        if (! $isSystemAdmin && $departmentScope->isEmpty()) {
            return view('dashboards.evaluation-summary.index', [
                'periods'          => collect(),
                'selectedPeriodId' => null,
                'instructors'      => collect(),
                'filters'          => $filters,
                'colleges'         => collect(),
                'departments'      => collect(),
                'isSystemAdmin'    => $isSystemAdmin,
            ]);
        }

        $periods = AcademicPeriod::with(['college', 'department'])
            ->whereHas('sections', function ($q) {
                $q->whereNotNull('instructor_user_id');
            })
            ->when(! $isSystemAdmin, function ($q) use ($departmentScope) {
                $q->whereIn('department_id', $departmentScope);
            })
            ->orderByDesc('year_start')
            ->orderByDesc('term')
            ->get();

        $selectedPeriodId = (int) $request->get('period_id');
        $period           = $periods->firstWhere('id', $selectedPeriodId) ?? $periods->first();

        if (! $period) {
            return view('dashboards.evaluation-summary.index', [
                'periods'          => $periods,
                'selectedPeriodId' => null,
                'instructors'      => collect(),
                'filters'          => $filters,
                'colleges'         => $isSystemAdmin ? College::orderBy('name')->get(['id', 'name']) : collect(),
                'departments'      => $isSystemAdmin ? Department::with('college')->orderBy('name')->get(['id', 'name', 'college_id']) : collect(),
                'isSystemAdmin'    => $isSystemAdmin,
            ]);
        }

        $specialIds = $this->specialIdsForChair($user, $departmentScope, $isSystemAdmin);

        $instructorQuery = User::role('instructor')
            ->with(['instructorProfile.department.college'])
            ->whereHas('taughtSections', function ($q) use ($period) {
                $q->where('academic_period_id', $period->id);
            })
            ->when(! $isSystemAdmin, function ($q) use ($departmentScope, $specialIds) {
                $q->where(function ($inner) use ($departmentScope, $specialIds) {
                    $inner->whereHas('instructorProfile', function ($sq) use ($departmentScope) {
                        $sq->whereIn('department_id', $departmentScope);
                    });

                    if ($specialIds->isNotEmpty()) {
                        $inner->orWhereIn('users.id', $specialIds);
                    }
                });
            })
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('users.name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('users.email', 'like', '%' . $filters['q'] . '%');
                });
            });

        if ($isSystemAdmin) {
            $instructorQuery
                ->when($filters['college_id'], function ($q) use ($filters) {
                    $q->whereHas('instructorProfile.department', function ($sq) use ($filters) {
                        $sq->where('college_id', $filters['college_id']);
                    });
                })
                ->when($filters['department_id'], function ($q) use ($filters) {
                    $q->whereHas('instructorProfile', function ($sq) use ($filters) {
                        $sq->where('department_id', $filters['department_id']);
                    });
                });
        }

        $instructors = $instructorQuery
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        $colleges    = $isSystemAdmin ? College::orderBy('name')->get(['id', 'name']) : collect();
        $departments = $isSystemAdmin
            ? Department::with('college')->orderBy('name')->get(['id', 'name', 'college_id'])
            : collect();

        return view('dashboards.evaluation-summary.index', [
            'periods'          => $periods,
            'selectedPeriodId' => $period->id,
            'instructors'      => $instructors,
            'filters'          => $filters,
            'colleges'         => $colleges,
            'departments'      => $departments,
            'isSystemAdmin'    => $isSystemAdmin,
        ]);
    }

    public function show(Request $request, User $instructor)
    {
        abort_unless($instructor->hasRole('instructor'), 404);

        $user          = $request->user();
        $isSystemAdmin = $user->hasRole('systemadmin');
        abort_unless($isSystemAdmin || $user->hasRole('chairman'), 403);

        $departmentScope = $isSystemAdmin
            ? collect()
            : $user->chairedDepartments()->pluck('departments.id');

        $specialIds = $this->specialIdsForChair($user, $departmentScope, $isSystemAdmin);

        if (! $isSystemAdmin) {
            $inDept = $departmentScope->isNotEmpty()
                && $instructor->instructorProfile
                && $departmentScope->contains($instructor->instructorProfile->department_id);

            if (! $inDept && ! $specialIds->contains($instructor->id)) {
                abort(403);
            }
        }

        $periods = AcademicPeriod::with(['college', 'department'])
            ->whereHas('sections', function ($q) use ($instructor) {
                $q->where('instructor_user_id', $instructor->id);
            })
            ->when(! $isSystemAdmin && $departmentScope->isNotEmpty(), function ($q) use ($departmentScope, $specialIds, $instructor) {
                if (! $specialIds->contains($instructor->id)) {
                    $q->whereIn('department_id', $departmentScope);
                }
            })
            ->orderByDesc('year_start')
            ->orderByDesc('term')
            ->get();

        $selectedPeriodId = (int) $request->get('period_id');
        $period           = $periods->firstWhere('id', $selectedPeriodId) ?? $periods->first();

        if (! $period) {
            return view('dashboards.evaluation-summary.show', [
                'instructor'         => $instructor,
                'period'             => null,
                'rows'               => collect(),
                'totalStudents'      => 0,
                'totalWeightedScore' => 0,
                'studentComments'    => collect(),
                'supervisorComments' => collect(),
                'roleRank'           => $this->determineRoleRank($instructor),
                'periods'            => $periods,
                'selectedPeriodId'   => null,
            ]);
        }

        $sections = Section::with(['course', 'period.college', 'period.department'])
            ->where('academic_period_id', $period->id)
            ->where('instructor_user_id', $instructor->id)
            ->get();

        $sectionIds      = $sections->pluck('id');
        $sectionStudents = SectionStudent::whereIn('section_id', $sectionIds)
            ->with('evaluationRecord')
            ->get()
            ->groupBy('section_id');

        $rows               = [];
        $totalStudents      = 0;
        $totalWeightedScore = 0;
        $overallSetRating   = null;
        $sefRating          = null;
        $seq                = 1;
        $facultyRank        = $instructor->instructorProfile->faculty_rank ?? $this->determineRoleRank($instructor);

        foreach ($sections as $section) {
            $studentsForSection = $sectionStudents->get($section->id, collect());
            $numStudents        = $studentsForSection->count();

            $evals = $studentsForSection
                ->pluck('evaluationRecord')
                ->filter();

            $avgRawScore = $evals->count()
                ? $evals->avg(fn (EvaluationRecord $er) => $er->total_score)
                : null;

            $avgSetRating = $avgRawScore !== null
                ? round($avgRawScore, 2)
                : null;

            $weightedScore = ($numStudents > 0 && $avgSetRating !== null)
                ? round($numStudents * $avgSetRating, 2)
                : null;

            if ($numStudents > 0 && $weightedScore !== null) {
                $totalStudents      += $numStudents;
                $totalWeightedScore += $weightedScore;
            }

            $rows[] = [
                'seq'            => $seq++,
                'course_code'    => $section->course->course_code ?? '-',
                'year_section'   => $section->section_label,
                'num_students'   => $numStudents,
                'avg_set_rating' => $avgSetRating,
                'weighted_score' => $weightedScore,
            ];
        }

        $sectionStudentIds = $sectionStudents->flatten()->pluck('id');

        $studentComments = $sectionStudentIds->isEmpty()
            ? collect()
            : EvaluationRecord::whereIn('section_student_fk', $sectionStudentIds)
                ->where('evaluated_as', 'student')
                ->whereNotNull('comment')
                ->pluck('comment')
                ->filter()
                ->values();

        $supervisorComments = SuperiorEvaluation::where('instructor_user_id', $instructor->id)
            ->where('academic_period_id', $period->id)
            ->whereNotNull('comment')
            ->pluck('comment')
            ->filter()
            ->values();

        if ($totalStudents > 0 && $totalWeightedScore > 0) {
            $overallSetRating = round($totalWeightedScore / $totalStudents, 2);
        }

        $sefEvaluations = SuperiorEvaluation::where('instructor_user_id', $instructor->id)
            ->where('academic_period_id', $period->id)
            ->get();

        if ($sefEvaluations->isNotEmpty()) {
            $avgSefRawScore = $sefEvaluations->avg(function (SuperiorEvaluation $evaluation) {
                return $evaluation->a1 + $evaluation->a2 + $evaluation->a3 + $evaluation->a4 + $evaluation->a5 + $evaluation->a6
                    + $evaluation->b7 + $evaluation->b8 + $evaluation->b9 + $evaluation->b10 + $evaluation->b11 + $evaluation->b12
                    + $evaluation->c12 + $evaluation->c13 + $evaluation->c14 + $evaluation->c15;
            });

            $sefRating = round($avgSefRawScore, 2);
        }

        return view('dashboards.evaluation-summary.show', [
            'instructor'         => $instructor,
            'period'             => $period,
            'rows'               => collect($rows),
            'totalStudents'      => $totalStudents,
            'totalWeightedScore' => $totalWeightedScore,
            'overallSetRating'   => $overallSetRating,
            'sefRating'          => $sefRating,
            'studentComments'    => $studentComments,
            'supervisorComments' => $supervisorComments,
            'roleRank'           => $this->determineRoleRank($instructor),
            'facultyRank'        => $facultyRank,
            'periods'            => $periods,
            'selectedPeriodId'   => $period->id,
            'developmentPlan'    => $this->loadDevelopmentPlan($period, $instructor, $user),
            'canEditPlan'        => $user->hasRole('chairman|systemadmin'),
            'activeTab'          => $request->get('tab', 'individual'),
        ]);
    }

    public function storeDevelopmentPlan(Request $request, User $instructor)
    {
        $user = $request->user();
        abort_unless($user->hasRole('chairman|systemadmin'), 403);

        $periodId = (int) $request->get('period_id');
        if (! $periodId) {
            return back()->with('error', 'Invalid period selected.');
        }

        $validated = $request->validate([
            'areas_for_improvement'  => 'nullable|string',
            'proposed_activities'    => 'nullable|string',
            'action_plan'            => 'nullable|string',
        ]);

        DevelopmentPlan::updateOrCreate(
            [
                'academic_period_id' => $periodId,
                'instructor_user_id' => $instructor->id,
                'chairman_user_id'   => $user->id,
            ],
            $validated
        );

        return redirect()
            ->route('dashboard.evaluation-summary.show', ['instructor' => $instructor->id, 'period_id' => $periodId, 'tab' => 'summary'])
            ->withFragment('development-plan')
            ->with('success', 'Development plan saved.');
    }

    protected function loadDevelopmentPlan(?AcademicPeriod $period, User $instructor, User $viewer): ?DevelopmentPlan
    {
        if (! $period || ! $viewer->hasRole('chairman|systemadmin')) {
            return null;
        }

        return DevelopmentPlan::where('academic_period_id', $period->id)
            ->where('instructor_user_id', $instructor->id)
            ->where('chairman_user_id', $viewer->id)
            ->first();
    }

    protected function determineRoleRank(User $user): string
    {
        if ($user->hasRole('ced')) {
            return 'CED';
        }
        if ($user->hasRole('dean')) {
            return 'Dean';
        }
        if ($user->hasRole('chairman')) {
            return 'Chairman';
        }

        return 'Instructor';
    }

    protected function specialIdsForChair(User $user, Collection $departmentScope, bool $isSystemAdmin): Collection
    {
        if ($isSystemAdmin) {
            return collect();
        }

        $ids = collect([$user->id]);

        if ($departmentScope->isEmpty()) {
            return $ids->unique();
        }

        $collegeIds = Department::whereIn('id', $departmentScope)
            ->pluck('college_id')
            ->filter()
            ->unique();

        if ($collegeIds->isNotEmpty()) {
            $deanIds = DB::table('dean_assignments')
                ->whereIn('college_id', $collegeIds)
                ->pluck('user_id');

            $ids = $ids->merge($deanIds);
        }

        return $ids->filter()->unique();
    }
}
