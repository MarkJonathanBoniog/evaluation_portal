<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(AcademicPeriod $period)
    {
        // show programs for the same department as the selected period
        $programs = Program::where('academic_period_id', $period->id)
            ->orderBy('name')
            ->get();

        return view('manage.programs.index', compact('period', 'programs'));
    }

    public function store(Request $r, AcademicPeriod $period)
    {
        $data = $r->validate([
            'name'  => ['required','string','max:255'],
            'major' => ['nullable','string','max:255'],
        ]);

        $data['academic_period_id'] = $period->id;
        $data['department_id']      = $period->department_id;

        Program::create($data); 

        return back()->with('status', 'Program created.');
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return back()->with('status', 'Program deleted.');
    }
}
