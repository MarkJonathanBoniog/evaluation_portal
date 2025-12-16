<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Evaluation Summary
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Browse instructors with teaching assignments for the selected academic period and open their records.
        </p>

        @if($periods->isNotEmpty())
            <form method="GET" class="mt-3 flex flex-col sm:flex-row sm:items-end gap-2 sm:gap-3">
                <div class="w-full sm:w-96">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Academic Period</label>
                    <select name="period_id"
                            class="mt-1 w-full rounded border-gray-300"
                            onchange="this.form.submit()">
                        @foreach($periods as $option)
                            <option value="{{ $option->id }}" {{ (int)$selectedPeriodId === (int)$option->id ? 'selected' : '' }}>
                                AY {{ $option->year_start }}-{{ $option->year_end }}, {{ ucfirst($option->term) }} Sem
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:pb-1">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Apply</button>
                </div>
            </form>
        @endif
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if($periods->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    There are no academic periods with sections assigned to instructors yet.
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:flex-nowrap mb-4">
                    <input type="hidden" name="period_id" value="{{ $selectedPeriodId }}">

                    <div class="w-full lg:flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name or Email</label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Search instructor name or email"
                            class="mt-1 w-full rounded border-gray-300"
                        >
                    </div>

                    @if($isSystemAdmin)
                        <div class="w-full lg:w-60">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">College</label>
                            <select name="college_id" class="mt-1 w-full rounded border-gray-300">
                                <option value="">All colleges</option>
                                @foreach($colleges as $college)
                                    <option value="{{ $college->id }}" {{ (int)$filters['college_id'] === (int)$college->id ? 'selected' : '' }}>
                                        {{ $college->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full lg:w-72">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                            <select name="department_id" class="mt-1 w-full rounded border-gray-300">
                                <option value="">All departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ (int)$filters['department_id'] === (int)$department->id ? 'selected' : '' }}>
                                        {{ $department->name }} ({{ $department->college->name ?? 'No College' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                        <a href="{{ route('dashboard.evaluation-summary', ['period_id' => $selectedPeriodId]) }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500">
                        <tr>
                            <th class="py-2 px-2">Instructor Name</th>
                            <th class="py-2 px-2">Email</th>
                            <th class="py-2 px-2">Faculty Rank</th>
                            <th class="py-2 px-2">College</th>
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
                                <td class="py-2 px-2">{{ $college->name ?? '-' }}</td>
                                <td class="py-2 px-2">{{ $dept->name ?? '-' }}</td>
                                <td class="py-2 px-2 text-right">
                                    <a href="{{ route('dashboard.evaluation-summary.show', ['instructor' => $instructor->id, 'period_id' => $selectedPeriodId]) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded text-sm font-medium bg-blue-600 text-white hover:bg-blue-700">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-gray-500">
                                    No instructors with teaching assignments for this academic period.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <x-table-footer :paginator="$instructors" />
            </div>
        @endif
    </div>
</x-app-layout>
