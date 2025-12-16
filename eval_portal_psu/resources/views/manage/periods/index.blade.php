<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
            <div class="flex-1">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    Academic Periods
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Review and manage academic periods along with their college and department scope. Create new periods or refine existing ones to keep evaluations aligned with the current term.
                </p>
            </div>

            @hasanyrole('chairman|dean|ced|systemadmin')
                <a href="{{ route('manage.periods.create') }}"
                   class="shrink-0 inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap lg:self-end">
                    + New Academic Period
                </a>
            @endhasanyrole
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            @if(session('status'))
                <div class="mb-4 text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap mb-6">
                <div class="w-full lg:flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Search college or department"
                        class="mt-1 w-full rounded border-gray-300"
                    >
                </div>
                @unlessrole('chairman')
                    <div class="w-full lg:w-60">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                        <select name="college_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">All colleges</option>
                            @foreach($colleges as $college)
                                <option value="{{ $college->id }}" @selected((string)$college->id === (string)($filters['college_id'] ?? ''))>
                                    {{ $college->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full lg:w-72">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <select name="department_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">All departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((string)$dept->id === (string)($filters['department_id'] ?? ''))>
                                    {{ $dept->name }} @if($dept->college_id) ({{ $colleges->firstWhere('id', $dept->college_id)->name ?? 'No College' }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunlessrole
                <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                    <a href="{{ route('manage.periods.index') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            @if($periods->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @if($filters['q'] ?? $filters['college_id'] ?? $filters['department_id'] ?? false)
                        No academic periods match your search.
                    @else
                        No academic periods have been created yet.
                    @endif
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                            <th class="px-4 py-2 text-left">College</th>
                            <th class="px-4 py-2 text-left">Department</th>
                            <th class="px-4 py-2 text-left">Academic Year</th>
                            <th class="px-4 py-2 text-left">Term</th>
                            <th class="px-4 py-2 text-left">Created By</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($periods as $period)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                <td class="px-4 py-2">
                                    {{ $period->college->name ?? '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $period->department->name ?? '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $period->year_start }}–{{ $period->year_end }}
                                </td>
                                <td class="px-4 py-2 capitalize">
                                    {{ $period->term }}
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $period->creator->name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <a href="{{ route('manage.programs.index', $period) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900/60">
                                        Programs
                                    </a>

                                    <form action="{{ route('manage.periods.destroy', $period) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Delete this academic period? This cannot be undone.')"
                                                class="inline-flex items-center px-2.5 py-1.5 border border-red-300 rounded text-xs text-red-700 hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <x-table-footer :paginator="$periods" />
            @endif
        </div>
    </div>
</x-app-layout>
