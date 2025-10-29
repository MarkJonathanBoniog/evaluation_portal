<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use App\Models\College;
use App\Models\Department;

class AcademicPeriodController extends Controller
{
    public function index()
    {
        $periods = AcademicPeriod::with(['college','department'])
            ->latest()->paginate(10);

        return view('manage.periods.index', compact('periods'));
    }

    public function create()
    {
        $colleges = College::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name', 'college_id']);

        return view('manage.periods.create', compact('colleges', 'departments'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'college_id'    => ['required','exists:colleges,id'],
            'department_id' => ['required','exists:departments,id'],
            'year_start'    => ['required','integer','min:2000','max:2099'],
            'year_end'      => ['required','integer','min:2000','max:2099'],
            'term'          => ['required','in:first,second,summer'],
        ]);

        $data['created_by'] = $r->user()->id;

        $period = AcademicPeriod::create($data);

        return redirect()
            ->route('manage.programs.index', $period)
            ->with('status', 'Academic period created. Now add Programs & Majors.');
    }

    public function show(AcademicPeriod $period)
    {
        // redirect to programs under this period
        return redirect()->route('manage.programs.index', $period);
    }

    public function destroy(AcademicPeriod $period)
    {
        $period->delete();
        return back()->with('status','Academic period deleted.');
    }
}
