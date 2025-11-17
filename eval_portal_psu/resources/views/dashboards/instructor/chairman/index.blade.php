<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Chairman Dashboard – Superior Evaluations
        </h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if($periods->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    There are no academic periods under your department yet.
                </p>
            </div>
        @endif

        @foreach($periods as $period)
            @php
                $instructors = $instructorsByPeriod[$period->id] ?? collect();
                $collegeDeans = $deansByCollege[$period->college_id] ?? collect();
                $dean = $collegeDeans->first(); // typically one dean per college
            @endphp

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">
                            {{ $period->college->name ?? '—' }} /
                            {{ $period->department->name ?? '—' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            AY {{ $period->year_start }}–{{ $period->year_end }} –
                            <span class="capitalize">{{ $period->term }}</span> semester
                        </p>
                    </div>
                </div>

                {{-- 1) Instructors to evaluate --}}
                @if($instructors->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        No instructors with sections under this academic period.
                    </p>
                @else
                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200 mb-2">
                        Instructors to Evaluate
                    </h4>

                    <div class="overflow-x-auto mb-4">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    <th class="px-4 py-2 text-left">Instructor</th>
                                    <th class="px-4 py-2 text-left">UID</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($instructors as $inst)
                                    @php
                                        $key   = $period->id.'-'.$inst->instructor_id;
                                        $done  = $evaluationStatus[$key] ?? false;
                                        $label = $done ? 'Done' : 'Pending';
                                        $badgeClasses = $done
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700';
                                    @endphp

                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                        <td class="px-4 py-2">
                                            {{ $inst->instructor_name }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-slate-600 dark:text-slate-300">
                                            {{ $inst->instructor_uid ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{-- TODO: wire this to your actual evaluation route --}}
                                            <a href="#"
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

                {{-- 2) Dean to evaluate (per academic period / college) --}}
                @if($dean)
                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200 mb-2 mt-4">
                        Dean to Evaluate
                    </h4>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                    <th class="px-4 py-2 text-left">Dean</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @php
                                    $deanKey   = $period->id.'-'.$dean->dean_id;
                                    $deanDone  = $evaluationStatus[$deanKey] ?? false;
                                    $deanLabel = $deanDone ? 'Done' : 'Pending';
                                    $deanBadgeClasses = $deanDone
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700';
                                @endphp

                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                    <td class="px-4 py-2">
                                        {{ $dean->dean_name }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $deanBadgeClasses }}">
                                            {{ $deanLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        {{-- TODO: wire this to your actual evaluation route --}}
                                        <a href="#"
                                           class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                            Evaluate
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
