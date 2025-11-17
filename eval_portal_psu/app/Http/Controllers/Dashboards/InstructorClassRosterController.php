<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class InstructorClassRosterController extends Controller
{
    /**
     * List all sections this instructor teaches.
     */
    public function index()
    {
        $instructor = Auth::user();

        $sections = Section::with(['course', 'program', 'period'])
            ->where('instructor_user_id', $instructor->id)
            ->withCount([
                'students', // students_count
                'students as evaluated_students_count' => function ($q) {
                    $q->whereNotNull('section_student.evaluated_at');
                },
            ])
            ->orderByDesc('academic_period_id')
            ->orderBy('section_label')
            ->get();

            $totalSections   = $sections->count();
            $totalStudents   = $sections->sum('students_count');
            $totalEvaluated  = $sections->sum('evaluated_students_count');
            $evaluationRate  = $totalStudents > 0
                ? round(($totalEvaluated / $totalStudents) * 100, 1)
                : 0;

        return view('dashboards.instructor.class-rosters.index', [
            'sections'        => $sections,
            'totalSections'   => $totalSections,
            'totalStudents'   => $totalStudents,
            'totalEvaluated'  => $totalEvaluated,
            'evaluationRate'  => $evaluationRate,
        ]);
    }

    /**
     * Show/manage roster for a specific section (only if it belongs to this instructor).
     */
  public function show(Section $section)
{
    $user = Auth::user();

    if ($section->instructor_user_id !== $user->id) {
        abort(403, 'You are not allowed to manage this section.');
    }

    $section->load(['period', 'program', 'course']);

    $period  = $section->period;
    $program = $section->program;
    $course  = $section->course;

    // Already-enrolled students
    $students = $section->students()
        ->with('studentProfile:id,user_id,student_number')
        ->orderBy('users.name')
        ->get();

    // === NEW: per-section summary ===
    $totalStudents  = $students->count();
    $totalEvaluated = $students->filter(fn ($s) => !is_null($s->pivot->evaluated_at))->count();
    $evaluationRate = $totalStudents > 0
        ? round(($totalEvaluated / $totalStudents) * 100, 1)
        : 0;

    $enrolledIds = $students->pluck('id');

    // Candidates = students not yet in this section
    $candidates = User::role('student')
        ->with('studentProfile:id,user_id,student_number')
        ->whereNotIn('id', $enrolledIds)
        ->orderBy('name')
        ->get(['id', 'name']);

    return view('dashboards.instructor.class-rosters.show', compact(
        'period',
        'program',
        'course',
        'section',
        'students',
        'candidates',
        'totalStudents',
        'totalEvaluated',
        'evaluationRate',
    ));
}


    /**
     * Manual add student to this section.
     */
    public function store(Request $request, Section $section)
    {
        $user = Auth::user();
        if ($section->instructor_user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'student_user_id' => ['required', 'exists:users,id'],
        ]);

        $section->students()->syncWithoutDetaching([$data['student_user_id']]);

        return redirect()
            ->route('instructor.class-rosters.show', $section)
            ->with('status', 'Student added successfully.');
    }

    /**
     * Remove student from roster.
     */
    public function destroy(Section $section, $studentId)
    {
        $user = Auth::user();
        if ($section->instructor_user_id !== $user->id) {
            abort(403);
        }

        $section->students()->detach($studentId);

        return redirect()
            ->route('instructor.class-rosters.show', $section)
            ->with('status', 'Student removed from roster.');
    }

    /* ================= Optional: CSV download/upload (same logic as RosterController) ================ */

    public function downloadTemplate(Section $section)
    {
        $user = Auth::user();
        if ($section->instructor_user_id !== $user->id) {
            abort(403);
        }

        $section->load(['period', 'program', 'course']);
        $period  = $section->period;
        $program = $section->program;
        $course  = $section->course;

        $students = $section->students()
            ->with(['studentProfile:id,user_id,student_number'])
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $rows   = [];
        $rows[] = ['Program: ' . $program->name];
        $rows[] = ['Major: ' . ($program->major ?? '')];
        $rows[] = ['Course: ' . $course->course_code . ' — ' . $course->course_name];

        $termLabel = match ($period->term) {
            'first'  => 'First Semester',
            'second' => 'Second Semester',
            'summer' => 'Summer Term',
            default  => Str::title($period->term) . ' Semester',
        };
        $rows[] = ['A.Y.: ' . $period->year_start . '–' . $period->year_end . ' | Term: ' . $termLabel . ' | Section: ' . $section->section_label];
        $rows[] = [''];

        $header = ['Student Number', 'Student Email', 'Student Name (read-only)'];
        $rows[] = $header;

        foreach ($students as $u) {
            $rows[] = [
                $u->studentProfile?->student_number ?? '',
                $u->email ?? '',
                $u->name ?? '',
            ];
        }

        $filename = 'Roster_' . $course->course_code . '_Sec' . $section->section_label . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

public function uploadCsv(Request $request, Section $section)
{
    // make sure this section belongs to the logged-in instructor
    $user = Auth::user();
    if ($section->instructor_user_id !== $user->id) {
        abort(403, 'You are not allowed to manage this section.');
    }

    // load related models so we can reuse the same checks
    $section->load(['period', 'program', 'course']);
    $period  = $section->period;
    $program = $section->program;
    $course  = $section->course;

    $data = $request->validate([
        'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        'mode'     => ['nullable', 'in:merge,sync'],
    ]);
    $mode = $data['mode'] ?? 'merge';

    $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
    if (!$handle) {
        return back()->withErrors(['csv_file' => 'Unable to read uploaded file.']);
    }

    $line = 0;
    $read = function () use (&$line, $handle) {
        $line++;
        return fgetcsv($handle);
    };

    // ---- Metadata & header
    $m1 = $read(); // Program:
    $m2 = $read(); // Major:
    $m3 = $read(); // Course:
    $m4 = $read(); // AY/Term/Section
    $spacer = $read();
    $header = $read();

    $errors = [];
    if (!$m1 || !Str::startsWith(($m1[0] ?? ''), 'Program: ')) {
        $errors[] = "Line 1 must start with 'Program: '.";
    }
    if (!$m2 || !Str::startsWith(($m2[0] ?? ''), 'Major: ')) {
        $errors[] = "Line 2 must start with 'Major: '.";
    }
    if (!$m3 || !Str::startsWith(($m3[0] ?? ''), 'Course: ')) {
        $errors[] = "Line 3 must start with 'Course: '.";
    }
    if (!$m4 || !Str::startsWith(($m4[0] ?? ''), 'A.Y.: ')) {
        $errors[] = "Line 4 must start with 'A.Y.: '.";
    }

    $expectedHeader = ['Student Number','Student Email','Student Name (read-only)'];
    if (!$header || array_map('trim', $header) !== $expectedHeader) {
        $errors[] = "Line 6 header must be exactly: ".implode(', ', $expectedHeader).".";
    }

    // Soft check: Program name must match the page's program
    $csvProgram = trim(Str::after($m1[0] ?? '', 'Program: '));
    if ($csvProgram !== $program->name) {
        $errors[] = "Program mismatch (CSV: '{$csvProgram}', Page: '{$program->name}').";
    }

    if ($errors) {
        fclose($handle);
        return back()->withErrors($errors);
    }

    // ---- Read data rows
    $incoming    = []; // student_number => true
    $numberLines = []; // student_number => [lines]

    while (($row = $read()) !== false) {
        // skip empty
        if (!array_filter($row, fn ($v) => trim((string) $v) !== '')) {
            continue;
        }

        $studentNumber = trim((string)($row[0] ?? ''));
        $email         = trim((string)($row[1] ?? '')); // informational
        $name          = trim((string)($row[2] ?? '')); // informational

        if ($studentNumber === '') {
            $errors[] = "Line {$line}: Student Number is required.";
            continue;
        }

        if (isset($incoming[$studentNumber])) {
            $numberLines[$studentNumber][] = $line;
        } else {
            $incoming[$studentNumber]      = true;
            $numberLines[$studentNumber]   = [$line];
        }
    }
    fclose($handle);

    foreach ($numberLines as $num => $lines) {
        if (count($lines) > 1) {
            $errors[] = "Student Number '{$num}' appears multiple times (lines ".implode(', ', $lines).").";
        }
    }
    if ($errors) {
        return back()->withErrors($errors);
    }

    // ---- Resolve to user IDs by Student Number
    $toAttach = [];
    $missing  = [];
    foreach (array_keys($incoming) as $studNum) {
        $uid = $this->resolveStudentIdByNumber($studNum);
        if (!$uid) {
            $missing[] = "Student not found for Student Number '{$studNum}'.";
            continue;
        }
        $toAttach[] = (int) $uid;
    }

    if ($missing) {
        return back()->withErrors($missing);
    }

    // ---- Build existing snapshot of roster
    $existingUserIds = $section->students()->pluck('users.id')->map(fn ($v) => (int) $v)->all();
    $incomingUserIds = $toAttach;

    $attachIds = array_values(array_diff($incomingUserIds, $existingUserIds));
    $detachIds = [];
    if ($mode === 'sync') {
        $detachIds = array_values(array_diff($existingUserIds, $incomingUserIds));
    }

    // ---- Apply atomically
    DB::transaction(function () use ($section, $attachIds, $detachIds) {
        if (!empty($attachIds)) {
            $section->students()->attach($attachIds);
        }
        if (!empty($detachIds)) {
            $section->students()->detach($detachIds);
        }
    });

    $summary = sprintf(
        'CSV applied. Added: %d%s',
        count($attachIds),
        $mode === 'sync' ? ', Removed: '.count($detachIds) : ''
    );

    return redirect()
        ->route('instructor.class-rosters.show', $section)
        ->with('status', $summary);
}

/** ==================== HELPERS (same as before) ==================== */

private function resolveStudentIdByNumber(string $studentNumber): ?int
{
    $sp = StudentProfile::where('student_number', $studentNumber)->first();
    return $sp?->user_id ? (int) $sp->user_id : null;
}

}
