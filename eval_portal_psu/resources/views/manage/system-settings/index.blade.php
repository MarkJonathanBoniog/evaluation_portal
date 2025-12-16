<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                System Settings
            </h2>
            <div class="text-sm text-gray-500">System Admin only</div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Configure foundational data such as colleges and departments used across the platform. Use the tabs below to add, update, or retire entries safely.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-600 p-4 rounded text-blue-900 dark:text-blue-100">
                {{ session('status') }}
            </div>
        @endif

        @php
            $active = $tab ?? 'colleges';
            $tabs = [
                'colleges' => 'Manage Colleges',
                'departments' => 'Manage Departments',
            ];
        @endphp

        <div class="border-b border-slate-200 mb-2">
            <nav class="-mb-px flex flex-wrap gap-2" aria-label="System settings tabs">
                @foreach ($tabs as $key => $label)
                    @php $isActive = $active === $key; @endphp
                    <a
                        href="{{ route('manage.system-settings.index', ['tab' => $key]) }}"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium border-b-2 rounded-t-md
                            {{ $isActive
                                ? 'border-[#0052CC] text-[#0052CC] bg-white'
                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-slate-50'
                            }}"
                    >
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($active === 'colleges')
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Colleges</h3>
                        <p class="text-sm text-gray-500">Create, update, and remove colleges.</p>
                    </div>
                    <form method="POST" action="{{ route('manage.system-settings.colleges.store') }}" class="flex flex-col sm:flex-row gap-2 sm:items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College Name</label>
                            <input name="name" class="mt-1 rounded border-gray-300" required placeholder="e.g., College of Arts">
                        </div>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add College</button>
                    </form>
                </div>

                <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap">
                    <input type="hidden" name="tab" value="colleges">
                    <div class="w-full lg:flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input
                            type="text"
                            name="college_q"
                            value="{{ $collegeFilters['q'] ?? '' }}"
                            placeholder="Search college name"
                            class="mt-1 w-full rounded border-gray-300"
                        >
                    </div>
                    <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                        <a href="{{ route('manage.system-settings.index', ['tab' => 'colleges']) }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2 px-2">College</th>
                            <th class="py-2 px-2 w-48 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($colleges as $college)
                            <tr class="text-gray-800 dark:text-gray-100">
                                <td class="py-2 px-2">
                                    <form method="POST" action="{{ route('manage.system-settings.colleges.update', $college) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" value="{{ old('name', $college->name) }}" class="w-full rounded border-gray-300" required>
                                        <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs">Save</button>
                                    </form>
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <form method="POST" action="{{ route('manage.system-settings.colleges.destroy', $college) }}" onsubmit="return confirm('Delete this college?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1 bg-red-50 text-red-700 rounded hover:bg-red-100 text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-6 text-center text-gray-500">No colleges found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-footer :paginator="$colleges" />
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Departments</h3>
                        <p class="text-sm text-gray-500">Create, update, and remove departments.</p>
                    </div>
                    <form method="POST" action="{{ route('manage.system-settings.departments.store') }}" class="flex flex-col sm:flex-row gap-2 sm:items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department Name</label>
                            <input name="name" class="mt-1 rounded border-gray-300" required placeholder="e.g., Computer Science">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                            <select name="college_id" class="mt-1 rounded border-gray-300" required>
                                <option value="">Select college</option>
                                @foreach ($collegeOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Department</button>
                    </form>
                </div>

                <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap">
                    <input type="hidden" name="tab" value="departments">
                    <div class="w-full lg:flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input
                            type="text"
                            name="department_q"
                            value="{{ $departmentFilters['q'] ?? '' }}"
                            placeholder="Search department or college"
                            class="mt-1 w-full rounded border-gray-300"
                        >
                    </div>
                    <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                        <a href="{{ route('manage.system-settings.index', ['tab' => 'departments']) }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-2 px-2">Department</th>
                            <th class="py-2 px-2">College</th>
                            <th class="py-2 px-2 w-52 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($departments as $department)
                            <tr class="text-gray-800 dark:text-gray-100">
                                <td class="py-2 px-2">
                                    <form method="POST" action="{{ route('manage.system-settings.departments.update', $department) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2 items-center">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" value="{{ old('name', $department->name) }}" class="w-full rounded border-gray-300" required>
                                        <select name="college_id" class="w-full rounded border-gray-300" required>
                                            @foreach ($collegeOptions as $option)
                                                <option value="{{ $option->id }}" @selected($option->id === $department->college_id)>{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="sm:col-span-2">
                                            <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs">Save</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="py-2 px-2">
                                    {{ $department->college?->name ?? '-' }}
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <form method="POST" action="{{ route('manage.system-settings.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1 bg-red-50 text-red-700 rounded hover:bg-red-100 text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-gray-500">No departments found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <x-table-footer :paginator="$departments" />
            </div>
        @endif
    </div>
</x-app-layout>
