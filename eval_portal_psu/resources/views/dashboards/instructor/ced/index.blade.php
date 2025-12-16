<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            CED Evaluation: Deans
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Oversee dean evaluations across your assigned colleges and periods. Track completion status and open pending evaluations directly from the tables below.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if($periods->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    There are no academic periods under your oversight yet.
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
                    placeholder="Search dean or college"
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
                <a href="{{ route('dashboard.ced') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
            </div>
        </form>

        @foreach($periods as $period)
            @php
                $collegeDeans = $deansByCollege[$period->college_id] ?? collect();
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

                @if($collegeDeans->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        No dean assignments found under this college.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    <th class="px-4 py-2 text-left">Dean</th>
                                    <th class="px-4 py-2 text-left">College</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($collegeDeans as $dean)
                                    @php
                                        $key   = $period->id.'-'.$dean->dean_id;
                                        $done  = $evaluationStatus[$key] ?? false;
                                        $label = $done ? 'Done' : 'Pending';
                                        $badgeClasses = $done
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700';
                                    @endphp

                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                        <td class="px-4 py-2">
                                            {{ $dean->dean_name }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-slate-600 dark:text-slate-300">
                                            {{ $dean->college_name }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <a href="{{ route('manage.superior-evaluations.edit', [$period, $dean->dean_id]) }}"
                                               class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                                Evaluate
                                            </a>
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
