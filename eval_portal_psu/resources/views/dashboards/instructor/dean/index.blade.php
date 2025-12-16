<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Dean Evaluation: Chairmen
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Review chairmen across your colleges and submit dean evaluations for each period. Use the lists to see completion status and open the evaluation forms you still need to finish.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if($periods->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    There are no academic periods under your colleges yet.
                </p>
            </div>
        @endif

        <form method="GET" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap">
            <div class="w-full lg:flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search chairman or department"
                    class="mt-1 w-full rounded border-gray-300"
                >
            </div>
            <div class="w-full lg:w-64">
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
            <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                <a href="{{ route('dashboard.dean') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
            </div>
        </form>

        @foreach($periods as $period)
            @php
                $collegeChairs = $chairsByCollege[$period->college_id] ?? collect();
            @endphp

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">
                            {{ $period->college->name ?? '—' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            AY {{ $period->year_start }}–{{ $period->year_end }} –
                            <span class="capitalize">{{ $period->term }}</span> semester
                        </p>
                    </div>
                </div>

                @if($collegeChairs->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        No chairman assignments found under this college.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    <th class="px-4 py-2 text-left">Chairman</th>
                                    <th class="px-4 py-2 text-left">Department</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($collegeChairs as $chair)
                                    @php
                                        $key   = $period->id.'-'.$chair->chairman_id;
                                        $done  = $evaluationStatus[$key] ?? false;
                                        $label = $done ? 'Done' : 'Pending';
                                        $badgeClasses = $done
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700';
                                    @endphp

                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                        <td class="px-4 py-2">
                                            {{ $chair->chairman_name }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-slate-600 dark:text-slate-300">
                                            {{ $chair->department_name }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            @if(!$done)
                                                <a href="{{ route('manage.superior-evaluations.edit', [$period, $chair->chairman_id]) }}"
                                                class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                                    Evaluate
                                                </a>
                                            @else
                                                <a href="{{ route('manage.superior-evaluations.edit', [$period, $chair->chairman_id]) }}"
                                                   class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-slate-200 text-slate-700 hover:bg-slate-300">
                                                    View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
