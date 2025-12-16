<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request, AcademicPeriod $period)
    {
        $filters = [
            'q' => trim((string) $request->get('q', '')),
        ];

        $programs = Program::where('academic_period_id', $period->id)
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('major', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->withCount('courses')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        return view('manage.programs.index', compact('period', 'programs', 'filters'));
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

    public function destroy(AcademicPeriod $period, Program $program)
    {
        $program->delete();
        return back()->with('status', 'Program deleted.');
    }
}
