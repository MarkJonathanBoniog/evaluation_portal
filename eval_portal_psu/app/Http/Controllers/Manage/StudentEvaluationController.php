<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;

use App\Models\EvaluationRecord;
use App\Models\SectionStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentEvaluationController extends Controller
{
    /**
     * Show the evaluation form
     */
    public function show($sectionStudentId)
    {
        // Get the authenticated student
        $studentUserId = Auth::id();

        // Find the section_student record
        $sectionStudent = SectionStudent::with(['section', 'user'])
            ->where('id', $sectionStudentId)
            ->where('student_user_id', $studentUserId)
            ->firstOrFail();

        // Check if already evaluated
        $existingEvaluation = EvaluationRecord::where('section_student_fk', $sectionStudentId)
            ->where('evaluated_as', 'student')
            ->first();

        // Get instructor information from the section
        $instructor = $sectionStudent->section->instructor ?? null;

        return view('student.evaluation.form', [
            'sectionStudent'     => $sectionStudent,
            'instructor'         => $instructor,
            'evaluationRecord'   => $existingEvaluation,
            'isReadOnly'         => (bool) $existingEvaluation,
        ]);
    }

    /**
     * Store the evaluation
     */
    public function store(Request $request, $sectionStudentId)
    {
        // Validate the request
        $validated = $request->validate([
            'a1' => 'required|integer|min:1|max:5',
            'a2' => 'required|integer|min:1|max:5',
            'a3' => 'required|integer|min:1|max:5',
            'a4' => 'required|integer|min:1|max:5',
            'a5' => 'required|integer|min:1|max:5',
            'a6' => 'required|integer|min:1|max:5',
            'b7' => 'required|integer|min:1|max:5',
            'b8' => 'required|integer|min:1|max:5',
            'b9' => 'required|integer|min:1|max:5',
            'b10' => 'required|integer|min:1|max:5',
            'b11' => 'required|integer|min:1|max:5',
            'b12' => 'required|integer|min:1|max:5',
            'c12' => 'required|integer|min:1|max:5',
            'c13' => 'required|integer|min:1|max:5',
            'c14' => 'required|integer|min:1|max:5',
            'c15' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $studentUserId = Auth::id();

        // Verify the section_student belongs to the authenticated user
        $sectionStudent = SectionStudent::where('id', $sectionStudentId)
            ->where('student_user_id', $studentUserId)
            ->firstOrFail();

        // Check if already evaluated
        $existingEvaluation = EvaluationRecord::where('section_student_fk', $sectionStudentId)
            ->where('evaluated_as', 'student')
            ->first();

        if ($existingEvaluation) {
            return redirect()->back()->with('error', 'You have already submitted an evaluation for this instructor.');
        }

        DB::beginTransaction();
        try {
            // Create the evaluation record
            EvaluationRecord::create([
                'section_student_fk' => $sectionStudentId,
                'evaluated_as' => 'student',
                'a1' => $validated['a1'],
                'a2' => $validated['a2'],
                'a3' => $validated['a3'],
                'a4' => $validated['a4'],
                'a5' => $validated['a5'],
                'a6' => $validated['a6'],
                'b7' => $validated['b7'],
                'b8' => $validated['b8'],
                'b9' => $validated['b9'],
                'b10' => $validated['b10'],
                'b11' => $validated['b11'],
                'b12' => $validated['b12'],
                'c12' => $validated['c12'],
                'c13' => $validated['c13'],
                'c14' => $validated['c14'],
                'c15' => $validated['c15'],
                'comment' => $validated['comment'],
            ]);

            // Update the evaluated_at timestamp in section_student
            $sectionStudent->update([
                'evaluated_at' => now()
            ]);

            DB::commit();

            return redirect()->route('dashboard.student')
                ->with('success', 'Evaluation submitted successfully! Thank you for your feedback.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred while submitting your evaluation. Please try again.')
                ->withInput();
        }
    }
}
