<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Instructors Account Management</p>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Instructor Accounts</h2>
                <p class="text-sm text-gray-600">View, add, edit, or remove instructor accounts.</p>
            </div>
            <div class="flex items-center gap-3">
                @role('chairman|systemadmin')
                    <button
                        x-data
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#1520a6] text-white rounded shadow hover:bg-indigo-800"
                        @click="window.dispatchEvent(new CustomEvent('open-instructor-modal', { detail: { mode: 'create' } }))"
                    >
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Add Instructor</span>
                    </button>
                @endrole
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8"
         x-data="instructorAccounts({
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
            flashMessage: @js(session('status'))
         })"
         x-on:open-instructor-modal.window="openModal($event.detail?.mode || 'create', $event.detail?.payload || null)">

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
                           placeholder="Search name, email, faculty rank"
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
                    <a href="{{ route('manage.instructors.index') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2 px-2">Name</th>
                        <th class="py-2 px-2">Email</th>
                        <th class="py-2 px-2">Faculty Rank</th>
                        <th class="py-2 px-2">Role</th>
                        <th class="py-2 px-2">Department</th>
                        <th class="py-2 px-2 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($instructors as $instructor)
                        @php
                            $dept = $instructor->instructorProfile?->department;
                            $college = $dept?->college;
                        @endphp
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2 px-2">{{ $instructor->name }}</td>
                            <td class="py-2 px-2">{{ $instructor->email }}</td>
                            <td class="py-2 px-2">{{ $instructor->instructorProfile?->faculty_rank ?? '-' }}</td>
                            <td class="py-2 px-2">
                                @php
                                    $roles = [];
                                    if ($instructor->hasRole('ced')) $roles[] = 'CED';
                                    if ($instructor->hasRole('dean')) $roles[] = 'Dean';
                                    if ($instructor->hasRole('chairman')) $roles[] = 'Chairman';
                                    if (empty($roles)) $roles[] = 'Instructor';
                                @endphp
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ implode(' / ', $roles) }}
                                </div>
                            </td>
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
                                    @click="openModal('edit', {
                                        id: {{ $instructor->id }},
                                        name: @js($instructor->name),
                                        email: @js($instructor->email),
                                        faculty_rank: @js($instructor->instructorProfile?->faculty_rank),
                                        department_id: @js($dept?->id),
                                        college_id: @js($college?->id),
                                        action: @js(route('manage.instructors.update', $instructor)),
                                    })"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('manage.instructors.destroy', $instructor) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-1 px-3 py-1 text-red-700 bg-red-50 hover:bg-red-100 rounded" onclick="return confirm('Delete this instructor? This cannot be undone.');">
                                        <i class="bi bi-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No instructors found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$instructors" />
        </div>

        {{-- Modal --}}
        <div x-show="state.open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" style="display:none">
            <div class="bg-white dark:bg-gray-900 w-full max-w-xl rounded-lg shadow-lg p-6" @click.outside="state.open=false">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100" x-text="state.mode === 'create' ? 'Add Instructor' : 'Edit Instructor'"></h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="state.open=false">&times;</button>
                </div>

                <form :action="state.action" method="POST" class="space-y-4">
                    @csrf
                    <template x-if="state.mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Faculty Rank</label>
                            <input type="text" name="faculty_rank" x-model="state.faculty_rank" class="mt-1 w-full rounded border-gray-300" required>
                        </div>
                        @if(auth()->user()->hasRole('chairman') && ! auth()->user()->hasRole('systemadmin'))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                <input type="text" class="mt-1 w-full rounded border-gray-200 bg-gray-100" :value="state.department_name" disabled>
                                <input type="hidden" name="department_id" :value="state.department_id">
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                                <select class="mt-1 w-full rounded border-gray-300" x-model="state.college_id" name="college_id" @change="state.department_id=''" required>
                                    <option value="">Select college</option>
                                    <template x-for="college in colleges" :key="college.id">
                                        <option :value="String(college.id)" x-text="college.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                <select class="mt-1 w-full rounded border-gray-300" x-model="state.department_id" name="department_id" required>
                                    <option value="">Select department</option>
                                    <template x-for="dept in filteredDepartments()" :key="dept.id">
                                        <option :value="String(dept.id)" x-text="`${dept.name} (${dept.college_name ?? 'No College'})`"></option>
                                    </template>
                                </select>
                            </div>
                        @endif
                    </div>

                    @role('systemadmin')
                        <div class="border rounded-lg p-3 bg-gray-50">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Role Assignment</p>
                            <div class="flex flex-col gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="role_assignment" value="" x-model="state.role_assignment">
                                    <span>None (keep as regular instructor)</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="role_assignment" value="chairman" x-model="state.role_assignment">
                                    <span>Set as New Chairman (selected department)</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="role_assignment" value="dean" x-model="state.role_assignment">
                                    <span>Set as New Dean (department&rsquo;s college)</span>
                                </label>
                            </div>
                        </div>
                    @endrole

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-2 text-sm rounded border border-gray-300" @click="state.open=false">Cancel</button>
                        <button class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700" x-text="state.mode === 'create' ? 'Add Instructor' : 'Save Changes'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('instructorAccounts', ({ colleges, departments, filters, chairAssignment, isChairman, isSystemAdmin, flashMessage }) => ({
            colleges,
            departments,
            filters: {
                q: filters?.q ?? '',
                college_id: filters?.college_id ?? '',
                department_id: filters?.department_id ?? '',
            },
            state: {
                open: false,
                mode: 'create',
                action: '{{ route('manage.instructors.store') }}',
                name: '',
                email: '',
                faculty_rank: '',
                college_id: chairAssignment?.college_id ? String(chairAssignment.college_id) : '',
                department_id: chairAssignment?.department_id ? String(chairAssignment.department_id) : '',
                department_name: chairAssignment?.department_name ?? '',
                role_assignment: '',
            },
            chairAssignment: chairAssignment ?? null,
            isChairman: !!isChairman,
            isSystemAdmin: !!isSystemAdmin,
            flashMessage: flashMessage ?? '',
            filteredDepartmentsForFilter() {
                if (!this.filters.college_id) return this.departments;
                return this.departments.filter(d => String(d.college_id) === String(this.filters.college_id));
            },
            filteredDepartments() {
                if (this.isChairman && this.chairAssignment?.department_id) {
                    return this.departments.filter(d => String(d.id) === String(this.chairAssignment.department_id));
                }
                if (!this.state.college_id) return this.departments;
                return this.departments.filter(d => String(d.college_id) === String(this.state.college_id));
            },
            openModal(mode = 'create', payload = null) {
                this.state.mode = mode;
                this.state.open = true;
                this.state.action = mode === 'edit' && payload?.action ? payload.action : '{{ route('manage.instructors.store') }}';

                const defaults = {
                    name: '',
                    email: '',
                    faculty_rank: '',
                    college_id: this.chairAssignment?.college_id ? String(this.chairAssignment.college_id) : '',
                    department_id: this.chairAssignment?.department_id ? String(this.chairAssignment.department_id) : '',
                    department_name: this.chairAssignment?.department_name ?? '',
                    role_assignment: '',
                };

                this.state = {
                    ...this.state,
                    ...defaults,
                    ...(payload || {}),
                    open: true,
                    mode,
                    action: this.state.action,
                };

                if (this.isChairman && this.chairAssignment) {
                    this.state.department_id = String(this.chairAssignment.department_id);
                    this.state.department_name = this.chairAssignment.department_name ?? '';
                    this.state.college_id = String(this.chairAssignment.college_id ?? '');
                }

                if (mode === 'create') {
                    this.state.role_assignment = '';
                }
            },
        }));
    });
</script>
