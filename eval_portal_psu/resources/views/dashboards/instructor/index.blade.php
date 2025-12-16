<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Instructor Evaluation Summary
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Review evaluation metrics, comments, and coverage for your classes this period. Use this summary to track progress and follow up where feedback is missing.
        </p>
    </x-slot>

    <div class="py-10 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(!$period)
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    You currently have no teaching assignments for any academic period.
                </p>
            </div>
        @else

            {{-- A. Faculty Information --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">
                    A. Faculty Information
                </h3>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                    <div class="flex">
                        <dt class="w-40 text-gray-500 dark:text-gray-400">Name of Faculty Evaluated</dt>
                        <dd class="flex-1 font-semibold text-gray-900 dark:text-gray-100">
                            {{ $instructor->name }}
                        </dd>
                    </div>

                    <div class="flex">
                        <dt class="w-40 text-gray-500 dark:text-gray-400">Department / College</dt>
                        <dd class="flex-1 text-gray-900 dark:text-gray-100">
                            {{ $period->department->name ?? '—' }}
                            @if($period->college)
                                / {{ $period->college->name }}
                            @endif
                        </dd>
                    </div>

                    <div class="flex">
                        <dt class="w-40 text-gray-500 dark:text-gray-400">Current Faculty Rank</dt>
                        <dd class="flex-1 text-gray-900 dark:text-gray-100">
                            {{ $roleRank }}
                        </dd>
                    </div>

                    <div class="flex">
                        <dt class="w-40 text-gray-500 dark:text-gray-400">Semester / Academic Year</dt>
                        <dd class="flex-1 text-gray-900 dark:text-gray-100">
                            {{ \Illuminate\Support\Str::title($period->term) }} Semester /
                            {{ $period->year_start }}–{{ $period->year_end }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- B. Summary of Average SET Rating --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-2">
                    B. Summary of Average SET Rating
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                    Step 1: Get the average SET rating for each class. <br>
                    Step 2: Multiply the number of students in each class with its average SET rating
                    to get the Weighted SET Score per class. <br>
                    Step 3: Get the total number of students and the total weighted SET score.
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Seq</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Course Code</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Year / Section</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">No. of Students</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Average SET Rating</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Weighted SET Score (3 × 4)</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-center">
                                    {{ $row['seq'] }}
                                </td>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700">
                                    {{ $row['course_code'] }}
                                </td>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700">
                                    {{ $row['year_section'] }}
                                </td>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-center">
                                    {{ $row['num_students'] }}
                                </td>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-center">
                                    {{ $row['avg_set_rating'] !== null ? number_format($row['avg_set_rating'], 2) : '-' }}
                                </td>
                                <td class="px-2 py-1 border border-gray-200 dark:border-gray-700 text-center">
                                    {{ $row['weighted_score'] !== null ? number_format($row['weighted_score'], 2) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-2 py-3 border border-gray-200 dark:border-gray-700 text-center text-gray-500">
                                    No evaluated classes yet for this period.
                                </td>
                            </tr>
                        @endforelse

                        {{-- Totals row --}}
                        <tr class="font-semibold bg-gray-50 dark:bg-gray-900">
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center" colspan="3">
                                TOTAL
                            </td>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                {{ $totalStudents }}
                            </td>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                TOTAL
                            </td>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                {{ $totalWeightedScore ? number_format($totalWeightedScore, 2) : '-' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- C. SET and SEF Ratings (placeholder) --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-2">
                    C. SET and SEF Ratings
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                    Overall rating computation to be finalized. Values are temporarily left blank as per guidelines.
                </p>

                <div class="overflow-x-auto max-w-md">
                    <table class="min-w-full text-xs border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700 w-32">Overall Rating</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">SET Rating</th>
                            <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">SEF Rating</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">–</td>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">–</td>
                            <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">–</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-[11px] text-gray-500">
                    Note: Final formulas and SEF instrument ratings will be applied once provided by the evaluation office.
                </p>
            </div>

            {{-- D. Summary of Qualitative Comments and Suggestions --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold mt-2 mb-2">D. Summary of Qualitative Comments and Suggestions</h3>

{{-- Student Comments --}}
<h4 class="text-md font-semibold mt-4 mb-2">Comments and Suggestions from Students</h4>

<table class="min-w-full text-sm border border-gray-300 dark:border-gray-700">
    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
        <tr>
            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700 w-12 text-center">#</th>
            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700">Comment</th>
        </tr>
    </thead>
    <tbody>
        @forelse($studentComments as $idx => $comment)
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 text-center">{{ $idx + 1 }}</td>
                <td class="px-3 py-2">{{ $comment }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                    No comments submitted.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>


{{-- Supervisor Comments --}}
<h4 class="text-md font-semibold mt-6 mb-2">Comments and Suggestions from the Supervisor</h4>

<table class="min-w-full text-sm border border-gray-300 dark:border-gray-700">
    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
        <tr>
            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700 w-12 text-center">#</th>
            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700">Comment</th>
        </tr>
    </thead>
    <tbody>
        @forelse($supervisorComments as $idx => $comment)
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td class="px-3 py-2 text-center">{{ $idx + 1 }}</td>
                <td class="px-3 py-2">{{ $comment }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                    No supervisor comments submitted.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
            </div>

        @endif
    </div>
</x-app-layout>
