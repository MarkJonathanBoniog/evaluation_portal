<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

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
}
