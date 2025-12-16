<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicPeriodController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = [
            'q'            => trim((string) $request->get('q', '')),
            'college_id'   => $request->get('college_id'),
            'department_id'=> $request->get('department_id'),
        ];

        $query = AcademicPeriod::with(['college', 'department', 'creator'])
            ->orderByDesc('year_start')
            ->orderByDesc('term');

        $colleges = collect();
        $departments = collect();

        if ($user->hasRole('ced') || $user->hasRole('systemadmin')) {
            $colleges = College::orderBy('name')->get(['id', 'name']);
            $departments = Department::orderBy('name')->get(['id', 'name', 'college_id']);
        } elseif ($user->hasRole('dean')) {
            $colleges = $user->deanColleges()
                ->select('colleges.*')
                ->orderBy('colleges.name')
                ->get();

            $departments = Department::whereIn('college_id', $colleges->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'name', 'college_id']);

            $query->whereIn('college_id', $colleges->pluck('id'));
        } elseif ($user->hasRole('chairman')) {
            $departments = $user->chairedDepartments()
                ->select('departments.*')
                ->orderBy('departments.name')
                ->get();

            $colleges = College::whereIn('id', $departments->pluck('college_id'))
                ->orderBy('name')
                ->get(['id', 'name']);

            $query->whereIn('department_id', $departments->pluck('id'));
        } else {
            abort(403, 'You are not allowed to view academic periods.');
        }

        if ($filters['q'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('college', function ($c) use ($filters) {
                    $c->where('name', 'like', '%' . $filters['q'] . '%');
                })->orWhereHas('department', function ($d) use ($filters) {
                    $d->where('name', 'like', '%' . $filters['q'] . '%');
                });
            });
        }

        if ($filters['college_id']) {
            $query->where('college_id', $filters['college_id']);
        }

        if ($filters['department_id']) {
            $query->where('department_id', $filters['department_id']);
        }

        $periods = $query->paginate(40)->withQueryString();

        return view('manage.periods.index', compact('periods', 'colleges', 'departments', 'filters'));
    }

    public function create()
    {
        $user = auth()->user();

        if ($user->hasRole('ced') || $user->hasRole('systemadmin')) {
            // CED: any college + any department
            $colleges    = College::orderBy('name')->get(['id', 'name']);
            $departments = Department::orderBy('name')->get(['id', 'name', 'college_id']);

        } elseif ($user->hasRole('dean')) {
            // Dean: fixed to their college(s), can choose departments under them
            $colleges = $user->deanColleges()
                ->select('colleges.*') // important to avoid ambiguous id
                ->orderBy('colleges.name')
                ->get();

            $departments = Department::whereIn('college_id', $colleges->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'name', 'college_id']);

        } elseif ($user->hasRole('chairman')) {
            // Chairman: fixed to their department(s); college derived from those departments
            $departments = $user->chairedDepartments()
                ->select('departments.*') // important to avoid ambiguous id
                ->orderBy('departments.name')
                ->get();

            $colleges = College::whereIn('id', $departments->pluck('college_id'))
                ->orderBy('name')
                ->get(['id', 'name']);

        } else {
            abort(403, 'You are not allowed to create academic periods.');
        }

        return view('manage.periods.create', compact('colleges', 'departments'));
    }

    public function store(Request $r)
    {
        $user = $r->user();

        /**
         * Compute ALLOWED IDs only (no need to build full $colleges/$departments here),
         * then use them in Rule::in().
         */
        if ($user->hasRole('ced') || $user->hasRole('systemadmin')) {
            $allowedCollegeIds = College::pluck('id');
            $allowedDeptIds    = Department::pluck('id');

        } elseif ($user->hasRole('dean')) {
            // Dean: allowed colleges = deanColleges, allowed departments = under those colleges
            $allowedCollegeIds = $user->deanColleges()
                ->pluck('colleges.id'); // note: table-qualified to avoid ambiguity

            $allowedDeptIds = Department::whereIn('college_id', $allowedCollegeIds)
                ->pluck('id');

        } elseif ($user->hasRole('chairman')) {
            // Chairman: allowed departments = chairedDepartments, allowed colleges = derived from them
            $allowedDeptIds = $user->chairedDepartments()
                ->pluck('departments.id'); // table-qualified

            $allowedCollegeIds = College::whereIn(
                'id',
                Department::whereIn('id', $allowedDeptIds)->pluck('college_id')
            )->pluck('id');

        } else {
            abort(403, 'You are not allowed to create academic periods.');
        }

        if ($allowedCollegeIds->isEmpty() || $allowedDeptIds->isEmpty()) {
            abort(403, 'You have no assigned college/department to create periods for.');
        }

        $data = $r->validate([
            'college_id'    => [
                'required',
                'integer',
                Rule::in($allowedCollegeIds),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::in($allowedDeptIds),
            ],
            'academic_year' => [
                'required',
                'regex:/^\d{4}-\d{4}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    [$start, $end] = array_map('intval', explode('-', $value));

                    if ($start < 2000 || $start > 2099) {
                        $fail('The starting year must be between 2000 and 2099.');
                    }

                    if ($end !== $start + 1) {
                        $fail('The academic year must be consecutive (e.g. 2025-2026).');
                    }
                },
            ],
            'term'          => ['required', 'in:first,second,summer'],
        ]);

        // Convert "2025-2026" → year_start, year_end
        [$start, $end] = array_map('intval', explode('-', $data['academic_year']));
        $data['year_start'] = $start;
        $data['year_end']   = $end;
        unset($data['academic_year']);

        $data['created_by'] = $user->id;

        $period = AcademicPeriod::create($data);

        return redirect()
            ->route('manage.programs.index', $period)
            ->with('status', 'Academic period created. Now add Programs & Majors.');
    }

    public function show(AcademicPeriod $period)
    {
        return redirect()->route('manage.programs.index', $period);
    }

    public function destroy(AcademicPeriod $period)
    {
        $user = auth()->user();

        if (!($user->hasRole('ced') || $user->hasRole('systemadmin'))) {
            if ($user->hasRole('dean')) {
                if (! $user->deanColleges->pluck('id')->contains($period->college_id)) {
                    abort(403, 'You cannot delete periods outside your college.');
                }
            } elseif ($user->hasRole('chairman')) {
                if (! $user->chairedDepartments->pluck('id')->contains($period->department_id)) {
                    abort(403, 'You cannot delete periods outside your department.');
                }
            } else {
                abort(403, 'You are not allowed to delete periods.');
            }
        }

        $period->delete();

        return back()->with('status', 'Academic period deleted.');
    }
}
