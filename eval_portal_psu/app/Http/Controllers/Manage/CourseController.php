<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    // GET /manage/periods/{period}/programs/{program}/courses
    public function index(Request $request, AcademicPeriod $period, Program $program)
    {
        $filters = [
            'q' => trim((string) $request->get('q', '')),
        ];

        $courses = $program->courses()
            ->withCount(['sections as sections_count' => function ($q) use ($period, $program) {
                $q->where('academic_period_id', $period->id)
                  ->where('program_id', $program->id);
            }])
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('course_code', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('course_name', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->orderBy('course_code')
            ->paginate(40)
            ->withQueryString();

        return view('manage.courses.index', compact('period', 'program', 'courses', 'filters'));
    }

    // POST /manage/periods/{period}/programs/{program}/courses
    public function store(Request $request, AcademicPeriod $period, Program $program)
    {
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:50'],
            'course_name' => ['required', 'string', 'max:255'],
        ]);

        // find-or-create by code
        $course = Course::firstOrCreate(
            ['course_code' => $data['course_code']],
            ['course_name' => $data['course_name']]
        );

        // if course exists and name changed, update label
        if (!$course->wasRecentlyCreated && $course->course_name !== $data['course_name']) {
            $course->update(['course_name' => $data['course_name']]);
        }

        // link to this program (don’t detach other programs)
        $program->courses()->syncWithoutDetaching([$course->id]);

        return redirect()
            ->route('manage.courses.index', [$period, $program])
            ->with('status', 'Course linked to program.');
    }

    // DELETE /manage/periods/{period}/programs/{program}/courses/{course}
    public function destroy(AcademicPeriod $period, Program $program, Course $course)
    {
        $program->courses()->detach($course->id);

        return redirect()
            ->route('manage.courses.index', [$period, $program])
            ->with('status', 'Course unlinked from program.');
    }

public function export(AcademicPeriod $period, Program $program): StreamedResponse
{
    $courses = $program->courses()
        ->orderBy('course_code')
        ->get(['course_code', 'course_name']);

    // Build filename
    $filename = sprintf(
        'Courses_%s_%s_%s-%s_%s.csv',
        str($program->name)->slug('_')->upper(),
        str($program->major)->slug('-')->title(),
        $period->year_start,
        $period->year_end,
        str($period->term)->replace(' ', '-')->title()
    );

    return response()->streamDownload(function () use ($courses, $program, $period) {
        $out = fopen('php://output', 'w');

        // Write metadata lines (human-readable)
        fputs($out, "Program: {$program->name}\n");
        fputs($out, "Major: {$program->major}\n");
        fputs($out, "Academic Year: {$period->year_start}–{$period->year_end} | Term: " . ucfirst($period->term) . " Semester\n\n");

        // Column headers
        fputcsv($out, ['Course Code', 'Course Title']);

        if ($courses->isEmpty()) {
            fputcsv($out, ['SAMPLE101', 'Sample Course Title']);
        } else {
            foreach ($courses as $c) {
                fputcsv($out, [$c->course_code, $c->course_name]);
            }
        }

        fclose($out);
    }, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}


    public function import(Request $request, AcademicPeriod $period, Program $program)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');

        // Parse CSV -> array of ['course_code' => '...', 'course_name' => '...']
        $rows = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
$line = 0;
while (($data = fgetcsv($handle)) !== false) {
    $line++;

    // Skip empty or metadata lines
    if ($line <= 3 || count($data) < 2) {
        continue;
    }

    // First real line after metadata is the header
    if ($line === 5) {
        $h0 = strtolower(trim($data[0]));
        $h1 = strtolower(trim($data[1]));
        if (!in_array($h0, ['course code', 'course_code', 'code'], true) ||
            !in_array($h1, ['course title', 'course_name', 'name'], true)) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Invalid header. Expected "Course Code, Course Title" after metadata rows.']);
        }
        continue;
    }
                // Normalize row
                $code = isset($data[0]) ? trim($data[0]) : '';
                $name = isset($data[1]) ? trim($data[1]) : '';

                if ($code === '' && $name === '') {
                    continue; // allow blank trailing lines
                }
                if ($code === '' || $name === '') {
                    fclose($handle);
                    return back()->withErrors(['csv_file' => "Line {$line}: course_code and course_name are required."]);
                }

                // Optional: normalize code style (e.g., uppercase)
                $code = strtoupper($code);

                // Keep last occurrence if duplicated in CSV
                $rows[$code] = $name;
            }
            fclose($handle);
        } else {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded CSV.']);
        }

        // Nothing parsed?
        if (empty($rows)) {
            return back()->withErrors(['csv_file' => 'CSV is empty.']);
        }

        // Sync logic in a transaction
        $summary = [
            'created_courses' => 0,
            'updated_names'   => 0,
            'attached'        => 0,
            'detached'        => 0,
        ];

        DB::transaction(function () use ($rows, $program, &$summary) {
            // Current links in this program
            $current = $program->courses()->pluck('courses.id', 'courses.course_code'); // [code => id]
            $keepIds = [];

            foreach ($rows as $code => $name) {
                // Create or fetch by unique course_code
                $course = \App\Models\Course::firstOrCreate(
                    ['course_code' => $code],
                    ['course_name' => $name]
                );

                if ($course->wasRecentlyCreated) {
                    $summary['created_courses']++;
                } elseif ($course->course_name !== $name) {
                    $course->update(['course_name' => $name]);
                    $summary['updated_names']++;
                }

                $keepIds[] = $course->id;
            }

            // Sync this program’s links to exactly match CSV
            $changes = $program->courses()->sync($keepIds);
            $summary['attached'] = count($changes['attached'] ?? []);
            $summary['detached'] = count($changes['detached'] ?? []);
            // (changes['updated'] is typically empty for simple pivot)
        });

        // Nicely formatted status message
        $msg = collect([
            $summary['created_courses'] ? "{$summary['created_courses']} created" : null,
            $summary['updated_names']   ? "{$summary['updated_names']} updated"   : null,
            $summary['attached']        ? "{$summary['attached']} attached"       : null,
            $summary['detached']        ? "{$summary['detached']} detached"       : null,
        ])->filter()->values()->implode(' • ');

        return redirect()
            ->route('manage.courses.index', [$period, $program])
            ->with('status', $msg ?: 'No changes detected.');
    }
}
