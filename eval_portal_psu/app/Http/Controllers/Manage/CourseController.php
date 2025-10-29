<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // GET /manage/periods/{period}/programs/{program}/courses
    public function index(AcademicPeriod $period, Program $program)
    {
        $courses = $program->courses()->orderBy('course_code')->get();

        return view('manage.courses.index', compact('period', 'program', 'courses'));
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
}
