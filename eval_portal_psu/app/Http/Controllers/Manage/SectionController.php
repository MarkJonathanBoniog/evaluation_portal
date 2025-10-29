<?php

// app/Http/Controllers/Manage/SectionController.php
namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Models\InstructorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SectionController extends Controller
{
    // GET /manage/periods/{period}/programs/{program}/courses/{course}/sections
    public function index(AcademicPeriod $period, Program $program, Course $course)
    {
        $sections = Section::with('instructor.instructorProfile')
            ->where('academic_period_id', $period->id)
            ->where('program_id', $program->id)
            ->where('course_id', $course->id)
            ->orderBy('section_label')
            ->get();

        $instructors = User::role('instructor')
            ->orderBy('name')
            ->get(['id','name']);

        return view('manage.sections.index', compact('period','program','course','sections','instructors'));
    }

    // POST /manage/periods/{period}/programs/{program}/courses/{course}/sections
public function store(Request $r, AcademicPeriod $period, Program $program, Course $course)
{
    $data = $r->validate([
        'section_label'      => ['required','string','max:16'],
        'instructor_user_id' => ['required','integer','exists:users,id'],
    ]);

    // (Optional) ensure selected user is actually an instructor
    if (! \App\Models\User::find($data['instructor_user_id'])?->hasRole('instructor')) {
        return back()->withErrors(['instructor_user_id' => 'Selected user is not an instructor.'])->withInput();
    }

    $payload = [
        'academic_period_id' => $period->id,
        'program_id'         => $program->id,
        'course_id'          => $course->id,
        'section_label'      => $data['section_label'],
        'instructor_user_id' => $data['instructor_user_id'],
    ];

    try {
        // If you want to forbid exact duplicates of (period, program, course, label):
        \App\Models\Section::create($payload);
    } catch (\Illuminate\Database\QueryException $e) {
        // Unique-index collision for sections_unique_combo
        if (str_contains($e->getMessage(), 'sections_unique_combo')) {
            return back()
                ->withErrors(['section_label' => 'This section already exists for the selected period/program/course.'])
                ->withInput();
        }
        throw $e;
    }

    return redirect()
        ->route('manage.sections.index', [$period, $program, $course])
        ->with('status', 'Section created and instructor assigned.');
}


    // DELETE /manage/periods/{period}/programs/{program}/courses/{course}/sections/{section}
    public function destroy(AcademicPeriod $period, Program $program, Course $course, Section $section)
    {
        $section->delete();

        return redirect()
            ->route('manage.sections.index', [$period, $program, $course])
            ->with('status', 'Section deleted.');
    }

public function downloadTemplate(AcademicPeriod $period, Program $program, Course $course)
{
    $sections = Section::query()
        ->with(['instructor:id,name,email', 'instructor.instructorProfile:id,user_id,instructor_uid'])
        ->where('academic_period_id', $period->id)
        ->where('program_id', $program->id)
        ->where('course_id', $course->id)
        ->orderBy('section_label')
        ->get();

    $rows = [];
    $rows[] = ['Program: '.$program->name];
    $rows[] = ['Major: '.($program->major ?? '')];
    $termLabel = match ($period->term) {
        'first'  => 'First Semester',
        'second' => 'Second Semester',
        'summer' => 'Summer Term',
        default  => Str::title($period->term).' Semester',
    };
    $rows[] = ['Academic Year: '.$period->year_start.'–'.$period->year_end.' | Term: '.$termLabel];
    $rows[] = ['']; // spacer
    $header = ['Section Label','Instructor UID','Instructor Email','Instructor Name (read-only)'];
    $rows[] = $header;

    foreach ($sections as $s) {
        $rows[] = [
            $s->section_label,
            $s->instructor?->instructorProfile?->instructor_uid ?? '',
            $s->instructor?->email ?? '',
            $s->instructor?->name ?? '',
        ];
    }

    $filename = $this->sectionsCsvFilename($program, $course, $period);

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
}

public function uploadCsv(Request $request, AcademicPeriod $period, Program $program, Course $course)
{
    $data = $request->validate([
        'csv_file' => ['required','file','mimes:csv,txt','max:2048'],
        'mode'     => ['nullable','in:merge,sync'],
    ]);
    $mode = $data['mode'] ?? 'merge';

    // Open file
    $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
    if (!$handle) {
        return back()->withErrors(['csv_file' => 'Unable to read uploaded file.']);
    }

    $line = 0;
    // FIX: use a normal closure instead of an arrow fn with comma expression
    $read = function () use (&$line, $handle) {
        $line++;
        return fgetcsv($handle);
    };

    // ---- Metadata & header (lines 1–5) ----
    $m1     = $read(); // Program: ...
    $m2     = $read(); // Major: ...
    $m3     = $read(); // Academic Year: ...
    $spacer = $read(); // blank
    $header = $read(); // header on line 5

    $errors = [];
    if (!$m1 || !Str::startsWith(($m1[0] ?? ''), 'Program: ')) {
        $errors[] = "Line 1 must start with 'Program: '.";
    }
    if (!$m2 || !Str::startsWith(($m2[0] ?? ''), 'Major: ')) {
        $errors[] = "Line 2 must start with 'Major: '.";
    }
    if (!$m3 || !Str::startsWith(($m3[0] ?? ''), 'Academic Year: ')) {
        $errors[] = "Line 3 must start with 'Academic Year: '.";
    }
    $expectedHeader = ['Section Label','Instructor UID','Instructor Email','Instructor Name (read-only)'];
    if (!$header || array_map('trim', $header) !== $expectedHeader) {
        $errors[] = "Line 5 header must be exactly: ".implode(', ', $expectedHeader).".";
    }

    // Soft check: Program name match
    $csvProgram = trim(Str::after($m1[0] ?? '', 'Program: '));
    if ($csvProgram !== $program->name) {
        $errors[] = "Line 1 Program mismatch (CSV: '{$csvProgram}', Page: '{$program->name}').";
    }

    if ($errors) {
        fclose($handle);
        return back()->withErrors($errors);
    }

    // ---- Read data rows ----
    $incoming = [];         // label => ['label','uid','email']
    $labelLines = [];       // label => [line numbers]
    while (($row = $read()) !== false) {
        if (!array_filter($row, fn($v) => trim((string)$v) !== '')) {
            continue; // skip empty
        }
        $label = trim($row[0] ?? '');
        $uid   = trim($row[1] ?? '');
        $email = trim($row[2] ?? '');

        if ($label === '') {
            $errors[] = "Line {$line}: Section Label is required.";
            continue;
        }
        if ($uid === '' && $email === '') {
            $errors[] = "Line {$line}: Provide at least Instructor UID or Instructor Email.";
            continue;
        }

        if (isset($incoming[$label])) {
            $labelLines[$label][] = $line;
        } else {
            $incoming[$label] = compact('label','uid','email');
            $labelLines[$label] = [$line];
        }
    }
    fclose($handle);

    foreach ($labelLines as $lab => $lines) {
        if (count($lines) > 2 || (count($lines) === 2)) {
            $errors[] = "Section Label '{$lab}' appears multiple times (lines ".implode(', ', $lines).").";
        }
    }
    if ($errors) {
        return back()->withErrors($errors);
    }

    // ---- Existing snapshot ----
    $existing = Section::query()
        ->where('academic_period_id', $period->id)
        ->where('program_id', $program->id)
        ->where('course_id', $course->id)
        ->get()
        ->keyBy('section_label');

    // ---- Resolve instructors + compute diff ----
    $toCreate = [];
    $toUpdate = [];
    $missing = [];

    foreach ($incoming as $label => $row) {
        $instructorId = $this->resolveInstructorId($row['uid'], $row['email']);
        if (!$instructorId) {
            $missing[] = "Section '{$label}': Instructor not found (UID='{$row['uid']}', Email='{$row['email']}').";
            continue;
        }
        // role guard
        if (!User::where('id',$instructorId)->role('instructor')->exists()) {
            $missing[] = "Section '{$label}': Resolved user is not an instructor.";
            continue;
        }

        if (!isset($existing[$label])) {
            $toCreate[$label] = $instructorId;
        } else {
            if ((int)$existing[$label]->instructor_user_id !== (int)$instructorId) {
                $toUpdate[$label] = $instructorId;
            }
        }
    }

    if ($missing) {
        return back()->withErrors($missing);
    }

    $toDelete = [];
    if ($mode === 'sync') {
        $incomingLabels = array_keys($incoming);
        foreach ($existing as $label => $s) {
            if (!in_array($label, $incomingLabels, true)) {
                $toDelete[$label] = $s->id;
            }
        }
    }

    // ---- Apply changes atomically ----
    DB::transaction(function () use ($period,$program,$course,$toCreate,$toUpdate,$toDelete) {
        foreach ($toCreate as $label => $instructorId) {
            Section::create([
                'academic_period_id' => $period->id,
                'program_id'         => $program->id,
                'course_id'          => $course->id,
                'section_label'      => $label,
                'instructor_user_id' => $instructorId,
            ]);
        }
        foreach ($toUpdate as $label => $instructorId) {
            Section::where([
                'academic_period_id' => $period->id,
                'program_id'         => $program->id,
                'course_id'          => $course->id,
                'section_label'      => $label,
            ])->update(['instructor_user_id' => $instructorId]);
        }
        if (!empty($toDelete)) {
            Section::whereIn('id', array_values($toDelete))->delete();
        }
    });

    $summary = sprintf(
        'CSV applied. Created: %d, Updated: %d%s',
        count($toCreate),
        count($toUpdate),
        $mode === 'sync' ? ', Deleted: '.count($toDelete) : ''
    );

    return redirect()
        ->route('manage.sections.index', [$period,$program,$course])
        ->with('status', $summary);
}

private function resolveInstructorId(?string $uid, ?string $email): ?int
{
    $uid = trim((string)$uid);
    $email = trim((string)$email);

    if ($uid !== '') {
        $ip = InstructorProfile::where('instructor_uid', $uid)->first();
        if ($ip?->user_id) return (int)$ip->user_id;
    }
    if ($email !== '') {
        $u = User::where('email', $email)->first();
        if ($u?->id) return (int)$u->id;
    }
    return null;
}

private function sectionsCsvFilename(Program $program, Course $course, AcademicPeriod $period): string
{
    $format = function ($str, $forceUpper = false) {
        // Normalize spacing and separators
        $str = str_replace(['-', '_'], ' ', trim($str));

        // Split into words and preserve formatting
        $words = collect(explode(' ', $str))
            ->filter(fn($w) => $w !== '')
            ->map(function ($w) use ($forceUpper) {
                // If forced uppercase (for course codes) or already all uppercase → keep it uppercase
                if ($forceUpper || ctype_upper($w)) {
                    return strtoupper($w);
                }
                return Str::title($w);
            });

        return $words->implode('_');
    };

    $parts = [
        'Section',
        $format($program->name),
        $program->major ? $format($program->major) : null,
        $format($course->course_code, true), // <-- force full uppercase for course codes
        "{$period->year_start}-{$period->year_end}",
        Str::title($period->term),
    ];

    $parts = array_filter($parts);
    return implode('_', $parts).'.csv';
}

}
