<x-app-layout>
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 pt-4">
        <a href="{{ route('dashboard.evaluation-summary', ['period_id' => $selectedPeriodId]) }}"
           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200">
            &larr; Back to evaluation list
        </a>
    </div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Faculty Evaluation Record
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Review evaluation metrics, comments, and coverage for {{ $instructor->name }}.
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
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Load</button>
                </div>
            </form>
        @endif
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(! $period)
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    No evaluation data is available for this instructor yet.
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6" x-data="{ tab: '{{ $activeTab }}' }">

                <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                    <nav class="-mb-px flex flex-wrap gap-2" aria-label="Evaluation tabs">
                        <button type="button"
                                @click="tab = 'individual'"
                                :class="tab === 'individual' ? 'border-blue-600 text-blue-600 bg-white' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-slate-50'"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium border-b-2 rounded-t-md">
                            Individual Faculty Evaluation Report
                        </button>
                        <button type="button"
                                @click="tab = 'summary'"
                                :class="tab === 'summary' ? 'border-blue-600 text-blue-600 bg-white' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-slate-50'"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium border-b-2 rounded-t-md">
                            Faculty Evaluation and Developmen Acknowledgement Form
                        </button>
                    </nav>
                </div>

                <div x-show="tab === 'individual'" x-cloak class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-100 dark:border-gray-700">
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
                                    {{ $period->department->name ?? '-' }}
                                    @if($period->college)
                                        / {{ $period->college->name }}
                                    @endif
                                </dd>
                            </div>

                            <div class="flex">
                                <dt class="w-40 text-gray-500 dark:text-gray-400">Current Faculty Rank</dt>
                                <dd class="flex-1 text-gray-900 dark:text-gray-100">
                                    {{ $facultyRank }}
                                </dd>
                            </div>

                            <div class="flex">
                                <dt class="w-40 text-gray-500 dark:text-gray-400">Semester / Academic Year</dt>
                                <dd class="flex-1 text-gray-900 dark:text-gray-100">
                                    {{ \Illuminate\Support\Str::title($period->term) }} Semester /
                                    {{ $period->year_start }}-{{ $period->year_end }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-2">
                            B. Summary of Average SET Rating
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                            Step 1: Get the average raw SET score for each class (sum of item scores divided by number of submissions). <br>
                            Step 2: Multiply the number of students in each class with its average SET rating to get the Weighted SET Score per class. <br>
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
                                    <th class="px-2 py-2 border border-gray-200 dark:border-gray-700">Weighted SET Score (3 x 4)</th>
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

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-2">
                            C. SET and SEF Ratings
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                            Calculate the Overall SET Rating by dividing the total Weighted SET Score by the total number of students.
                            The SEF Rating is the rating given by the supervisor (chair/dean) using the SEF instrument.
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
                                    <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                        OVERALL RATING
                                    </td>
                                    <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                        {{ $overallSetRating !== null ? number_format($overallSetRating, 2) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-center">
                                        {{ $sefRating !== null ? number_format($sefRating, 2) : '-' }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500">
                            Note: SET rating is derived from student evaluations; SEF rating is provided by the supervisor.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-100 dark:border-gray-700 space-y-6">
                        <h3 class="text-lg font-semibold mt-2 mb-2">D. Summary of Qualitative Comments and Suggestions</h3>

                        <div>
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
                        </div>

                        <div>
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
                    </div>
                </div>

                <div x-show="tab === 'summary'" x-cloak class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border border-gray-100 dark:border-gray-700">
                    <div class="space-y-6 text-sm text-gray-800 dark:text-gray-100">
                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-4">
                            <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-100">A. Faculty Member Information</h4>
                            <p><span class="font-semibold">Name of Faculty:</span> {{ $instructor->name }}</p>
                            <p><span class="font-semibold">Department/College:</span> {{ $period->department->name ?? '-' }} / {{ $period->college->name ?? '-' }}</p>
                            <p><span class="font-semibold">Current Faculty Rank:</span> {{ $facultyRank }}</p>
                            <p><span class="font-semibold">Semester/Term &amp; Academic Year:</span> {{ \Illuminate\Support\Str::title($period->term) }} Semester / {{ $period->year_start }}-{{ $period->year_end }}</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-4">
                            <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-100">B. Faculty Evaluation Summary</h4>
                            <div class="overflow-x-auto max-w-md">
                                <table class="min-w-full text-xs border border-gray-300 dark:border-gray-700">
                                    <thead class="bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center" colspan="2">Overall Rating</th>
                                        </tr>
                                        <tr>
                                            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center">Student Evaluation of Teachers (SET)</th>
                                            <th class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center">Supervisor's Evaluation of Faculty (SAF)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center">{{ $overallSetRating !== null ? number_format($overallSetRating, 2) : '-' }}</td>
                                            <td class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center">{{ $sefRating !== null ? number_format($sefRating, 2) : '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="development-plan" class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-4 scroll-mt-8">
                            <h4 class="font-semibold mb-3 text-gray-800 dark:text-gray-100">C. Development Plan (Supervisor & Faculty)</h4>
                            @if($canEditPlan)
                                <form method="POST" action="{{ route('dashboard.evaluation-summary.plan', ['instructor' => $instructor->id]) }}" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="period_id" value="{{ $selectedPeriodId }}">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Areas for Improvement</label>
                                        <textarea name="areas_for_improvement" rows="3" class="w-full rounded border-gray-300">{{ old('areas_for_improvement', $developmentPlan->areas_for_improvement ?? '') }}</textarea>
                                        @error('areas_for_improvement') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Proposed Learning and Development Activities</label>
                                        <textarea name="proposed_activities" rows="3" class="w-full rounded border-gray-300">{{ old('proposed_activities', $developmentPlan->proposed_activities ?? '') }}</textarea>
                                        @error('proposed_activities') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Action Plan</label>
                                        <textarea name="action_plan" rows="3" class="w-full rounded border-gray-300">{{ old('action_plan', $developmentPlan->action_plan ?? '') }}</textarea>
                                        @error('action_plan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="pt-2">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                            Save Development Plan
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <p class="font-semibold">Areas for Improvement</p>
                                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $developmentPlan->areas_for_improvement ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold">Proposed Learning and Development Activities</p>
                                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $developmentPlan->proposed_activities ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="font-semibold">Action Plan</p>
                                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $developmentPlan->action_plan ?? '—' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-4">
                            <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">
                                I acknowledge that I have received and reviewed the faculty evaluation conducted for the period mentioned above.
                                I understand that my signature below does not necessarily indicate agreement with the evaluation but confirms that
                                I have been given the opportunity to discuss it with my supervisor.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-xs">
                                <div>
                                    <div class="font-semibold bg-gray-200 dark:bg-gray-800 px-2 py-1 border border-gray-300 dark:border-gray-700">Supervisor</div>
                                    <div class="border border-gray-300 dark:border-gray-700 p-2 space-y-2">
                                        <p>Signature: __________________________</p>
                                        <p>Name: {{ auth()->user()->name }}</p>
                                        <p>Date Signed: ____________________</p>
                                    </div>
                                </div>
                                <div>
                                    <div class="font-semibold bg-gray-200 dark:bg-gray-800 px-2 py-1 border border-gray-300 dark:border-gray-700">Faculty</div>
                                    <div class="border border-gray-300 dark:border-gray-700 p-2 space-y-2">
                                        <p>Signature: __________________________</p>
                                        <p>Name: {{ $instructor->name }}</p>
                                        <p>Date Signed: ____________________</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
