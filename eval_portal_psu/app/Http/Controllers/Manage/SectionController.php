<?php

// app/Http/Controllers/Manage/SectionController.php
namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

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
}
