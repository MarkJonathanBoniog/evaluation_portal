<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Students Account Management</p>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Student Accounts</h2>
                <p class="text-sm text-gray-600">View, edit, or remove student accounts. Only chairmen and system admins can access this page.</p>
            </div>
            <div class="flex items-center gap-3">
                @role('chairman|systemadmin')
                    <button
                        x-data
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#1520a6] text-white rounded shadow hover:bg-indigo-800"
                        x-on:click="window.dispatchEvent(new CustomEvent('open-generator'))"
                    >
                        <i class="bi bi-people-fill"></i>
                        <span>Generate Accounts</span>
                    </button>
                @endrole
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8"
         x-data="studentAccounts({
        colleges: @js($colleges),
        departments: @js($departments->map(fn($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'college_id' => $d->college_id,
            'college_name' => $d->college?->name,
        ])),
        filters: {
            q: @js(request('q')),
            college_id: @js((string)request('college_id')),
            department_id: @js((string)request('department_id')),
        },
        chairAssignment: @js($chairmanDepartment ?? null),
        isChairman: @js(auth()->user()->hasRole('chairman') && !auth()->user()->hasRole('systemadmin')),
        isSystemAdmin: @js(auth()->user()->hasRole('systemadmin')),
        flashMessage: @js(session('status')),
    })"
         x-on:open-generator.window="generator.open = true">
        <template x-if="flashMessage">
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-600 p-4 rounded text-blue-900 dark:text-blue-100" x-text="flashMessage"></div>
        </template>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 p-4 rounded text-red-900 dark:text-red-100">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4 space-y-4">
            {{-- Filters --}}
            <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap">
                <div class="w-full lg:flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input type="text"
                           name="q"
                           x-model="filters.q"
                           placeholder="Search name, email, student number"
                           value="{{ request('q') }}"
                           class="mt-1 w-full rounded border-gray-300"
                    >
                </div>
                @unlessrole('chairman')
                    <div class="w-full lg:w-60">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                        <select name="college_id" x-model="filters.college_id" @change="filters.department_id=''" class="mt-1 w-full rounded border-gray-300">
                            <option value="">All colleges</option>
                            <template x-for="college in colleges" :key="college.id">
                                <option :value="String(college.id)" x-text="college.name" :selected="String(college.id) === String(filters.college_id)"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-full lg:w-72">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <select name="department_id" x-model="filters.department_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">All departments</option>
                            <template x-for="dept in filteredDepartmentsForFilter()" :key="dept.id">
                                <option :value="String(dept.id)" x-text="`${dept.name} (${dept.college_name ?? 'No College'})`" :selected="String(dept.id) === String(filters.department_id)"></option>
                            </template>
                        </select>
                    </div>
                @endunlessrole
                <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                    <a href="{{ route('manage.students.index') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2 px-2">Student #</th>
                        <th class="py-2 px-2">Name</th>
                        <th class="py-2 px-2">Email</th>
                        <th class="py-2 px-2">Department</th>
                        <th class="py-2 px-2 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($students as $student)
                        @php
                            $dept = $student->studentProfile?->department;
                            $college = $dept?->college;
                        @endphp
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2 px-2">{{ $student->studentProfile?->student_number ?? '—' }}</td>
                            <td class="py-2 px-2">{{ $student->name }}</td>
                            <td class="py-2 px-2">{{ $student->email }}</td>
                            <td class="py-2 px-2">
                                @if($dept)
                                    <div class="font-medium">{{ $dept->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $college?->name }}</div>
                                @else
                                    <span class="text-gray-500">Unassigned</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right space-x-2">
                                <button
                                    class="inline-flex items-center gap-1 px-3 py-1 text-blue-700 bg-blue-50 hover:bg-blue-100 rounded"
                                    @click="openEdit({
                                        id: {{ $student->id }},
                                        name: @js($student->name),
                                        email: @js($student->email),
                                        student_number: @js($student->studentProfile?->student_number),
                                        department_id: @js($dept?->id),
                                        college_id: @js($college?->id),
                                        action: @js(route('manage.students.update', $student)),
                                    })"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('manage.students.destroy', $student) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-1 px-3 py-1 text-red-700 bg-red-50 hover:bg-red-100 rounded" onclick="return confirm('Delete this student? This cannot be undone.');">
                                        <i class="bi bi-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$students" />
        </div>

        {{-- Edit Modal --}}
        <div x-show="state.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" style="display:none">
            <div class="bg-white dark:bg-gray-900 w-full max-w-xl rounded-lg shadow-lg p-6" @click.outside="state.open=false">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Edit Student</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="state.open=false">&times;</button>
                </div>

                <form :action="state.action" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" name="name" x-model="state.name" class="mt-1 w-full rounded border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" x-model="state.email" class="mt-1 w-full rounded border-gray-300" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student Number</label>
                            <input type="text" name="student_number" x-model="state.student_number" class="mt-1 w-full rounded border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                            <select x-model="state.college_id" class="mt-1 w-full rounded border-gray-300">
                                <option value="">-- Select College --</option>
                                <template x-for="college in colleges" :key="college.id">
                                    <option :value="String(college.id)" x-text="college.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <select name="department_id" x-model="state.department_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">-- Select Department --</option>
                            <template x-for="dept in filteredDepartments()" :key="dept.id">
                                <option :value="String(dept.id)" x-text="`${dept.name} (${dept.college_name ?? 'No College'})`"></option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Selecting a college filters the department list.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm rounded border border-gray-300" @click="state.open=false">Cancel</button>
                        <button class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Generate Accounts Modal --}}
        <div
            x-show="generator.open"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            style="display:none"
            @click.self="closeGenerator()"
        >
            <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-lg shadow-lg p-6" @click.outside="closeGenerator()">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Generate Accounts</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Generates student accounts for the given number range, assigning them to the selected college and department.
                        </p>
                    </div>
                    <button class="text-gray-500 hover:text-gray-700" @click="closeGenerator()">&times;</button>
                </div>

                <form @submit.prevent="submitGenerate" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Student #</label>
                            <input type="text" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" title="Enter 4 digits (e.g. 0001)"
                                   class="mt-1 w-full rounded border-gray-300"
                                   x-model="generator.start_student_number"
                                   @input="formatStudentNumber('start_student_number')"
                                   required>
                            <p class="text-xs text-red-600 mt-1" x-text="generator.errors.start_student_number"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Student #</label>
                            <input type="text" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" title="Enter 4 digits (e.g. 0004)"
                                   class="mt-1 w-full rounded border-gray-300"
                                   x-model="generator.end_student_number"
                                   @input="formatStudentNumber('end_student_number')"
                                   required>
                            <p class="text-xs text-red-600 mt-1" x-text="generator.errors.end_student_number"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Password</label>
                            <input type="text" class="mt-1 w-full rounded border-gray-300"
                                   x-model="generator.password"
                                   required>
                            <p class="text-xs text-red-600 mt-1" x-text="generator.errors.password"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if(auth()->user()->hasRole('chairman') && ! auth()->user()->hasRole('systemadmin'))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                                <input type="text" class="mt-1 w-full rounded border-gray-200 bg-gray-100" :value="generator.college_name" disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                <input type="text" class="mt-1 w-full rounded border-gray-200 bg-gray-100" :value="generator.department_name" disabled>
                                <p class="text-xs text-red-600 mt-1" x-text="generator.errors.department_id"></p>
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                                <select class="mt-1 w-full rounded border-gray-300" x-model="generator.college_id" required>
                                    <option value="">Select college</option>
                                    <template x-for="college in colleges" :key="college.id">
                                        <option :value="String(college.id)" x-text="college.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-red-600 mt-1" x-text="generator.errors.college_id"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                <select class="mt-1 w-full rounded border-gray-300" x-model="generator.department_id" required>
                                    <option value="">Select department</option>
                                    <template x-for="dept in filteredDepartmentsForGenerator()" :key="dept.id">
                                        <option :value="String(dept.id)" x-text="`${dept.name} (${dept.college_name ?? 'No College'})`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-red-600 mt-1" x-text="generator.errors.department_id"></p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <template x-if="generator.errors.general">
                            <p class="text-sm text-red-600 mr-auto" x-text="generator.errors.general"></p>
                        </template>
                        <button type="button" class="px-3 py-2 text-sm rounded border border-gray-300" @click="closeGenerator()">Cancel</button>
                        <button class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('studentAccounts', ({ colleges, departments, filters, chairAssignment, isChairman, isSystemAdmin, flashMessage }) => ({
            colleges,
            departments,
            filters: {
                q: filters?.q ?? '',
                college_id: filters?.college_id ?? '',
                department_id: filters?.department_id ?? '',
            },
            state: {
                open: false,
                action: '',
                name: '',
                email: '',
                student_number: '',
                college_id: '',
                department_id: '',
            },
            generator: {
                open: false,
                start_student_number: '',
                end_student_number: '',
                password: 'password',
                college_id: chairAssignment?.college_id ? String(chairAssignment.college_id) : (filters?.college_id ?? ''),
                department_id: chairAssignment?.department_id ? String(chairAssignment.department_id) : (filters?.department_id ?? ''),
                college_name: chairAssignment?.college_name ?? '',
                department_name: chairAssignment?.department_name ?? '',
                errors: {},
            },
            chairAssignment: chairAssignment ?? null,
            isChairman: !!isChairman,
            isSystemAdmin: !!isSystemAdmin,
            flashMessage: flashMessage ?? '',
            init() {
                if (this.isChairman && this.chairAssignment) {
                    this.generator.college_name    = this.chairAssignment.college_name ?? '';
                    this.generator.department_name = this.chairAssignment.department_name ?? '';
                }
            },
            openEdit(payload) {
                this.state = {
                    ...this.state,
                    ...payload,
                    open: true,
                    college_id: payload.college_id || '',
                    department_id: payload.department_id || '',
                };
            },
            filteredDepartments() {
                if (!this.state.college_id) return this.departments;
                return this.departments.filter(d => String(d.college_id) === String(this.state.college_id));
            },
            filteredDepartmentsForFilter() {
                if (!this.filters.college_id) return this.departments;
                return this.departments.filter(d => String(d.college_id) === String(this.filters.college_id));
            },
            filteredDepartmentsForGenerator() {
                if (this.isChairman && this.chairAssignment?.department_id) {
                    return this.departments.filter(d => String(d.id) === String(this.chairAssignment.department_id));
                }
                if (!this.generator.college_id) return this.departments;
                return this.departments.filter(d => String(d.college_id) === String(this.generator.college_id));
            },
            formatStudentNumber(field) {
                const value = String(this.generator[field] || '').replace(/[^0-9]/g, '').slice(0, 4);
                this.generator[field] = value;
            },
            closeGenerator() {
                this.generator.open = false;
                this.generator.errors = {};
            },
            submitGenerate() {
                this.generator.errors = {};

                const errors = {};
                const start = this.generator.start_student_number || '';
                const end = this.generator.end_student_number || '';

                const isFour = v => /^\d{4}$/.test(v);
                if (!isFour(start)) errors.start_student_number = 'Start number must be 4 digits.';
                if (!isFour(end)) errors.end_student_number = 'End number must be 4 digits.';

                if (isFour(start) && isFour(end) && Number(end) < Number(start)) {
                    errors.end_student_number = 'End number must be greater than or equal to start.';
                }

                if (!this.generator.password) {
                    errors.password = 'Password is required.';
                }

                if (this.isSystemAdmin) {
                    if (!this.generator.college_id) errors.college_id = 'College is required.';
                    if (!this.generator.department_id) errors.department_id = 'Department is required.';
                } else if (this.isChairman) {
                    if (!this.chairAssignment?.department_id) {
                        errors.department_id = 'No department assignment found for your role.';
                    } else {
                        this.generator.department_id = String(this.chairAssignment.department_id);
                        this.generator.college_id = String(this.chairAssignment.college_id || '');
                        this.generator.department_name = this.chairAssignment.department_name ?? '';
                        this.generator.college_name = this.chairAssignment.college_name ?? '';
                    }
                }

                if (Object.keys(errors).length) {
                    this.generator.errors = errors;
                    return;
                }

                fetch('{{ route('manage.students.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        start_student_number: this.generator.start_student_number,
                        end_student_number: this.generator.end_student_number,
                        password: this.generator.password,
                        college_id: this.generator.college_id,
                        department_id: this.generator.department_id,
                    }),
                }).then(async (response) => {
                    if (response.ok) {
                        const data = await response.json();
                        this.flashMessage = data.message || 'Generation has been queued.';
                        this.closeGenerator();
                        this.generator.start_student_number = '';
                        this.generator.end_student_number = '';
                        this.generator.password = 'password';
                    } else if (response.status === 422) {
                        const data = await response.json();
                        this.generator.errors = Object.fromEntries(Object.entries(data.errors || {}).map(([k,v]) => [k, v[0]]));
                    } else {
                        this.generator.errors = { general: 'Something went wrong. Please try again.' };
                    }
                }).catch(() => {
                    this.generator.errors = { general: 'Request failed. Check your connection and try again.' };
                });
            },
        }));
    });
</script>
