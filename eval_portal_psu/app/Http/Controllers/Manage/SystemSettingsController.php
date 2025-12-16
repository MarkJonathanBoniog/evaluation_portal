<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemSettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'colleges');

        $collegeFilters = [
            'q' => trim((string) $request->get('college_q', '')),
        ];
        $departmentFilters = [
            'q' => trim((string) $request->get('department_q', '')),
        ];

        $colleges = College::query()
            ->when($collegeFilters['q'] !== '', function ($q) use ($collegeFilters) {
                $q->where('name', 'like', '%' . $collegeFilters['q'] . '%');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::query()
            ->with('college')
            ->when($departmentFilters['q'] !== '', function ($q) use ($departmentFilters) {
                $q->where(function ($sub) use ($departmentFilters) {
                    $sub->where('name', 'like', '%' . $departmentFilters['q'] . '%')
                        ->orWhereHas('college', function ($cq) use ($departmentFilters) {
                            $cq->where('name', 'like', '%' . $departmentFilters['q'] . '%');
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $collegeOptions = College::orderBy('name')->get(['id', 'name']);

        return view('manage.system-settings.index', [
            'tab'               => $tab,
            'colleges'          => $colleges,
            'departments'       => $departments,
            'collegeOptions'    => $collegeOptions,
            'collegeFilters'    => $collegeFilters,
            'departmentFilters' => $departmentFilters,
        ]);
    }

    public function storeCollege(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:colleges,name'],
        ]);

        College::create($data);

        return back()->with('status', 'College created.')->with('tab', 'colleges');
    }

    public function updateCollege(Request $request, College $college)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('colleges', 'name')->ignore($college->id),
            ],
        ]);

        $college->update($data);

        return back()->with('status', 'College updated.')->with('tab', 'colleges');
    }

    public function destroyCollege(College $college)
    {
        $college->delete();

        return back()->with('status', 'College deleted.')->with('tab', 'colleges');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:departments,name'],
            'college_id' => ['required', 'integer', 'exists:colleges,id'],
        ]);

        Department::create($data);

        return back()->with('status', 'Department created.')->with('tab', 'departments');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'college_id' => ['required', 'integer', 'exists:colleges,id'],
        ]);

        $department->update($data);

        return back()->with('status', 'Department updated.')->with('tab', 'departments');
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();

        return back()->with('status', 'Department deleted.')->with('tab', 'departments');
    }
}
