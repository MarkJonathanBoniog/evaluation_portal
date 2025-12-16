<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SuperiorEvaluation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuperiorEvaluationController extends Controller
{
    protected function resolveEvaluatorRoleAndDashboard(): array
    {
        $user = Auth::user();

        if ($user->hasRole('chairman')) {
            return ['chairman', 'dashboard.chairman'];
        }

        if ($user->hasRole('dean')) {
            return ['dean', 'dashboard.dean'];
        }

        if ($user->hasRole('ced')) {
            return ['ced', 'dashboard.ced'];
        }

        abort(403, 'You are not allowed to perform superior evaluations.');
    }

    public function edit(AcademicPeriod $period, User $subject)
    {
        [$evaluatedAs, $dashboardRoute] = $this->resolveEvaluatorRoleAndDashboard();
        $user = Auth::user();

        // Check if evaluation already exists
        $existing = SuperiorEvaluation::where('user_id', $user->id)
            ->where('instructor_user_id', $subject->id)
            ->where('academic_period_id', $period->id)
            ->where('evaluated_as', $evaluatedAs)
            ->first();

        return view('manage.superior-evaluations.form', [
            'period'      => $period,
            'subjectUser' => $subject,
            'evaluatedAs' => $evaluatedAs,
            'evaluation'  => $existing,
            'isReadOnly'  => (bool) $existing,
            'backRoute'   => $dashboardRoute,
        ]);
    }

    public function store(Request $request, AcademicPeriod $period, User $subject)
    {
        [$evaluatedAs, $dashboardRoute] = $this->resolveEvaluatorRoleAndDashboard();
        $user = Auth::user();

        $validated = $request->validate([
            'a1' => 'required|integer|min:1|max:5',
            'a2' => 'required|integer|min:1|max:5',
            'a3' => 'required|integer|min:1|max:5',
            'a4' => 'required|integer|min:1|max:5',
            'a5' => 'required|integer|min:1|max:5',
            'a6' => 'required|integer|min:1|max:5',

            'b7'  => 'required|integer|min:1|max:5',
            'b8'  => 'required|integer|min:1|max:5',
            'b9'  => 'required|integer|min:1|max:5',
            'b10' => 'required|integer|min:1|max:5',
            'b11' => 'required|integer|min:1|max:5',
            'b12' => 'required|integer|min:1|max:5',

            'c12' => 'required|integer|min:1|max:5',
            'c13' => 'required|integer|min:1|max:5',
            'c14' => 'required|integer|min:1|max:5',
            'c15' => 'required|integer|min:1|max:5',

            'comment' => 'nullable|string|max:1000',
        ]);

        // Safety check: duplicate prevention
        $existing = SuperiorEvaluation::where('user_id', $user->id)
            ->where('instructor_user_id', $subject->id)
            ->where('academic_period_id', $period->id)
            ->where('evaluated_as', $evaluatedAs)
            ->first();

        if ($existing) {
            return redirect()->route($dashboardRoute)
                ->with('error', 'You have already submitted an evaluation for this person for this academic period.');
        }

        try {
            DB::beginTransaction();

            SuperiorEvaluation::create([
                'user_id'            => $user->id,
                'instructor_user_id' => $subject->id,
                'academic_period_id' => $period->id,
                'evaluated_as'       => $evaluatedAs,

                'a1'  => $validated['a1'],
                'a2'  => $validated['a2'],
                'a3'  => $validated['a3'],
                'a4'  => $validated['a4'],
                'a5'  => $validated['a5'],
                'a6'  => $validated['a6'],
                'b7'  => $validated['b7'],
                'b8'  => $validated['b8'],
                'b9'  => $validated['b9'],
                'b10' => $validated['b10'],
                'b11' => $validated['b11'],
                'b12' => $validated['b12'],
                'c12' => $validated['c12'],
                'c13' => $validated['c13'],
                'c14' => $validated['c14'],
                'c15' => $validated['c15'],

                'comment' => $validated['comment'] ?? null,
            ]);

            DB::commit();

            return redirect()->route($dashboardRoute)
                ->with('success', 'Evaluation submitted successfully. Thank you for your feedback.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'An error occurred while submitting the evaluation. Please try again.')
                ->withInput();
        }
    }
}
