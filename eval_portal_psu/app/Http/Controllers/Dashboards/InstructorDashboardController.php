<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionStudent;
use App\Models\EvaluationRecord;
use App\Models\SuperiorEvaluation;
use Illuminate\Support\Facades\Auth;

class InstructorDashboardController extends Controller
{
    public function index()
    {
        $instructor = Auth::user();

        // 1) All sections this instructor teaches, newest period first
        $sections = Section::with([
                'course',
                'program',
                'period.college',
                'period.department',
                'students',
            ])
            ->where('instructor_user_id', $instructor->id)
            ->orderByDesc('academic_period_id')
            ->get();

        if ($sections->isEmpty()) {
            // no teaching assignment at all
            return view('dashboards.instructor.index', [
                'instructor'          => $instructor,
                'period'              => null,
                'rows'                => collect(),
                'totalStudents'       => 0,
                'totalWeightedScore'  => 0,
                'studentComments'     => collect(),
                'supervisorComments'  => collect(),
            ]);
        }

        // 2) Focus on the latest academic period where they teach
        $latestPeriodId = $sections->first()->academic_period_id;
        $periodSections = $sections->where('academic_period_id', $latestPeriodId)->values();
        $period = $periodSections->first()->period;

        // 3) Load section_student + evaluation_records for these sections
        $sectionIds = $periodSections->pluck('id');

        $sectionStudents = SectionStudent::whereIn('section_id', $sectionIds)
            ->with('evaluationRecord') // hasOne(EvaluationRecord::class, 'section_student_fk')
            ->get()
            ->groupBy('section_id');

        $rows = [];
        $totalStudents = 0;
        $totalWeightedScore = 0;
        $seq = 1;

        foreach ($periodSections as $section) {
            $studentsForSection = $sectionStudents->get($section->id, collect());

            $numStudents = $studentsForSection->count();

            $evals = $studentsForSection
                ->pluck('evaluationRecord')
                ->filter(); // drop nulls (students who didn’t evaluate)

            $avgSetRating = $evals->count()
                ? round($evals->avg(fn (EvaluationRecord $er) => $er->computed_rating), 2)
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
                'course_code'    => $section->course->course_code ?? '—',
                'year_section'   => $section->section_label,
                'num_students'   => $numStudents,
                'avg_set_rating' => $avgSetRating,
                'weighted_score' => $weightedScore,
            ];
        }

        // 4) Comments: Students
        $sectionStudentIds = $sectionStudents->flatten()->pluck('id');

        $studentComments = EvaluationRecord::whereIn('section_student_fk', $sectionStudentIds)
            ->where('evaluated_as', 'student')
            ->whereNotNull('comment')
            ->pluck('comment')
            ->filter()
            ->values();

        // 5) Comments: Supervisors (chairman/dean/ced)
        $supervisorComments = SuperiorEvaluation::where('instructor_user_id', $instructor->id)
            ->where('academic_period_id', $latestPeriodId)
            ->whereNotNull('comment')
            ->pluck('comment')
            ->filter()
            ->values();

        $roleRank = 'Faculty';

        // Priority order
        if ($instructor->hasRole('ced')) {
            $roleRank = 'CED';
        } elseif ($instructor->hasRole('dean')) {
            $roleRank = 'Dean';
        } elseif ($instructor->hasRole('chairman')) {
            $roleRank = 'Chairman';
        } elseif ($instructor->hasRole('instructor')) {
            $roleRank = 'Instructor';
        }

        return view('dashboards.instructor.index', [
            'instructor'         => $instructor,
            'period'             => $period,
            'rows'               => collect($rows),
            'totalStudents'      => $totalStudents,
            'totalWeightedScore' => $totalWeightedScore,
            'studentComments'    => $studentComments,
            'supervisorComments' => $supervisorComments,
            'roleRank'           => $roleRank,
        ]);
    }
}
