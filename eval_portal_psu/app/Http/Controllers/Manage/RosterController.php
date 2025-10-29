<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RosterController extends Controller
{
    public function index(AcademicPeriod $period, Program $program, Course $course, Section $section)
    {
        // Already-enrolled students (via relation), include pivot (evaluated_at)
        $students = $section->students()
            ->with('studentProfile:id,user_id,student_number') // if you have StudentProfile relation on User
            ->orderBy('users.name')
            ->get();

        // Collect current IDs to exclude from candidate list
        $enrolledIds = $students->pluck('id');

        // Candidates = all students not yet in this section
        $candidates = User::role('student')
            ->with('studentProfile:id,user_id,student_number')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get(['id','name']); // student number via relation

        return view('manage.roster.index', compact('period','program','course','section','students','candidates'));
    }

    public function store(Request $request, AcademicPeriod $period, Program $program, Course $course, Section $section)
    {
        $data = $request->validate([
            'student_user_id' => ['required','exists:users,id'],
        ]);

        $section->students()->syncWithoutDetaching([$data['student_user_id']]);

        return redirect()
            ->route('manage.roster.index', [$period, $program, $course, $section])
            ->with('status','Student added successfully.');
    }

    public function destroy(AcademicPeriod $period, Program $program, Course $course, Section $section, $studentId)
    {
        $section->students()->detach($studentId);

        return redirect()
            ->route('manage.roster.index', [$period, $program, $course, $section])
            ->with('status','Student removed from roster.');
    }

     public function downloadTemplate(
        AcademicPeriod $period,
        Program $program,
        Course $course,
        Section $section
    ) {
        // Current roster with student profile
        $students = $section->students()
            ->with(['studentProfile:id,user_id,student_number'])
            ->orderBy('name')
            ->get(['users.id','users.name','users.email']);

        $rows = [];
        // Metadata (lines 1-5 style)
        $rows[] = ['Program: '.$program->name];
        $rows[] = ['Major: '.($program->major ?? '')];
        $rows[] = ['Course: '.$course->course_code.' — '.$course->course_name];
        $termLabel = match ($period->term) {
            'first'  => 'First Semester',
            'second' => 'Second Semester',
            'summer' => 'Summer Term',
            default  => Str::title($period->term).' Semester',
        };
        $rows[] = ['A.Y.: '.$period->year_start.'–'.$period->year_end.' | Term: '.$termLabel.' | Section: '.$section->section_label];
        $rows[] = ['']; // spacer

        // Header (strict)
        $header = ['Student Number','Student Email','Student Name (read-only)'];
        $rows[] = $header;

        foreach ($students as $u) {
            $rows[] = [
                $u->studentProfile?->student_number ?? '',
                $u->email ?? '',
                $u->name ?? '',
            ];
        }

        $filename = $this->rosterCsvFilename($program, $course, $period, $section);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** ==================== CSV: UPLOAD (MERGE / SYNC) ==================== */
    public function uploadCsv(
        Request $request,
        AcademicPeriod $period,
        Program $program,
        Course $course,
        Section $section
    ) {
        $data = $request->validate([
            'csv_file' => ['required','file','mimes:csv,txt','max:2048'],
            'mode'     => ['nullable','in:merge,sync'],
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

        // Soft checks (optional but helpful)
        $csvProgram = trim(Str::after($m1[0] ?? '', 'Program: '));
        if ($csvProgram !== $program->name) {
            $errors[] = "Program mismatch (CSV: '{$csvProgram}', Page: '{$program->name}').";
        }
        // Could also parse Course / Section if you want stricter checks

        if ($errors) {
            fclose($handle);
            return back()->withErrors($errors);
        }

        // ---- Read data rows
        $incoming = [];           // student_number => true
        $numberLines = [];        // student_number => [lines]
        while (($row = $read()) !== false) {
            // skip empty
            if (!array_filter($row, fn($v) => trim((string)$v) !== '')) {
                continue;
            }

            $studentNumber = trim((string)($row[0] ?? ''));
            $email = trim((string)($row[1] ?? ''));   // informational
            $name  = trim((string)($row[2] ?? ''));   // informational

            if ($studentNumber === '') {
                $errors[] = "Line {$line}: Student Number is required.";
                continue;
            }

            if (isset($incoming[$studentNumber])) {
                $numberLines[$studentNumber][] = $line;
            } else {
                $incoming[$studentNumber] = true;
                $numberLines[$studentNumber] = [$line];
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
        $toAttach = [];   // user_id[]
        $missing  = [];   // messages
        foreach (array_keys($incoming) as $studNum) {
            $uid = $this->resolveStudentIdByNumber($studNum);
            if (!$uid) {
                $missing[] = "Student not found for Student Number '{$studNum}'.";
                continue;
            }
            $toAttach[] = (int)$uid;
        }

        if ($missing) {
            return back()->withErrors($missing);
        }

        // ---- Build existing snapshot of roster
        $existingUserIds = $section->students()->pluck('users.id')->map(fn($v)=>(int)$v)->all();
        $incomingUserIds = $toAttach;

        $attachIds = array_values(array_diff($incomingUserIds, $existingUserIds));
        $detachIds = [];
        if ($mode === 'sync') {
            $detachIds = array_values(array_diff($existingUserIds, $incomingUserIds));
        }

        // ---- Apply atomically
        DB::transaction(function () use ($section, $attachIds, $detachIds) {
            if (!empty($attachIds)) {
                // attach without disturbing evaluated_at; default nulls
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
            ->route('roster.index', [$section->academic_period_id, $section->program_id, $section->course_id, $section])
            ->with('status', $summary);
    }

    /** ==================== HELPERS ==================== */

    private function resolveStudentIdByNumber(string $studentNumber): ?int
    {
        $sp = StudentProfile::where('student_number', $studentNumber)->first();
        return $sp?->user_id ? (int)$sp->user_id : null;
    }

    private function rosterCsvFilename(
        Program $program, Course $course, AcademicPeriod $period, Section $section
    ): string {
        $format = function ($str, $forceUpper = false) {
            $str = str_replace(['-', '_'], ' ', trim($str));
            $words = collect(explode(' ', $str))
                ->filter(fn($w) => $w !== '')
                ->map(function ($w) use ($forceUpper) {
                    if ($forceUpper || ctype_upper($w)) return strtoupper($w);
                    return Str::title($w);
                });
            return $words->implode('_');
        };

        return implode('_', array_filter([
            'Roster',
            $format($program->name),
            $program->major ? $format($program->major) : null,
            $format($course->course_code, true), // uppercase course code
            'Sec'.$format($section->section_label, true), // keep section labels uppercase-friendly
            "{$period->year_start}-{$period->year_end}",
            Str::title($period->term),
        ])).'.csv';
    }
}
