<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Student Evaluation Page') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            View the courses and instructors you can evaluate this term. Pick a pending entry to submit feedback or review completed evaluations.
        </p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('Click on a course/instructor to start evaluating. You can only evaluate once per instructor.') }}
                    </div>

                    @php
$filters = ['q' => trim((string) request('q', ''))];
$studentId = auth()->id();

$periodIdsForStudent = \App\Models\Section::whereHas('students', function ($q) use ($studentId) {
        $q->where('student_user_id', $studentId);
    })
    ->pluck('academic_period_id')
    ->filter()
    ->unique();

$latestPeriodId = null;
if ($periodIdsForStudent->isNotEmpty()) {
    $latestPeriodId = \App\Models\AcademicPeriod::whereIn('id', $periodIdsForStudent)
        ->orderByDesc('year_start')
        ->orderByDesc('term')
        ->value('id');
}

$sectionsQuery = \App\Models\Section::whereHas('students', function ($q) use ($studentId) {
        $q->where('student_user_id', $studentId);
    })
    ->when($latestPeriodId, function ($q) use ($latestPeriodId) {
        $q->where('academic_period_id', $latestPeriodId);
    })
    ->with(['course', 'instructor', 'program', 'period'])
    ->when($filters['q'] !== '', function ($q) use ($filters) {
        $q->where(function ($sub) use ($filters) {
            $sub->whereHas('course', function ($cq) use ($filters) {
                $cq->where('course_code', 'like', '%' . $filters['q'] . '%')
                   ->orWhere('course_name', 'like', '%' . $filters['q'] . '%');
            })->orWhereHas('instructor', function ($iq) use ($filters) {
                $iq->where('name', 'like', '%' . $filters['q'] . '%')
                   ->orWhere('email', 'like', '%' . $filters['q'] . '%');
            })->orWhere('section_label', 'like', '%' . $filters['q'] . '%');
        });
    });

$sections = $sectionsQuery->paginate(40)->withQueryString();

$sectionStudentRecords = \App\Models\SectionStudent::where('student_user_id', auth()->id())
    ->whereIn('section_id', $sections->pluck('id'))
    ->with('evaluationRecord')
    ->get()
    ->keyBy('section_id');
                    @endphp

                    <div class="mt-6">
                        <h3 class="font-medium text-lg text-gray-800 dark:text-gray-100 mb-3">Enrolled Courses & Instructors</h3>

                        <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap mb-4">
                            <div class="w-full lg:flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                                <input
                                    type="text"
                                    name="q"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Search course, instructor, or section"
                                    class="mt-1 w-full rounded border-gray-300"
                                >
                            </div>
                            <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                                <a href="{{ route('dashboard.student') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                            </div>
                        </form>

                        @if($sections->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                @if($filters['q'] ?? false)
                                    No sections match your search.
                                @else
                                    You are not enrolled in any sections.
                                @endif
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($sections as $section)
                                            @php
                                                // Get the section_student record for this section
                                                $sectionStudent = $sectionStudentRecords->get($section->id);
                                                $hasEvaluated = $sectionStudent && $sectionStudent->evaluationRecord;
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $sectionStudent ? 'cursor-pointer' : '' }}"
                                                @if($sectionStudent)
                                                    onclick="window.location='{{ route('student.evaluation.show', $sectionStudent->id) }}'"
                                                @endif>
                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                                    {{ $section->course->course_code ?? '-' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                                    {{ $section->course->course_name ?? '-' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                                    {{ $section->section_label ?? '-' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                                    @if($sectionStudent)
                                                        <span class="text-blue-600 dark:text-blue-400 hover:underline">
                                                            {{ optional($section->instructor)->name ?? 'TBA' }}
                                                        </span>
                                                    @else
                                                        {{ optional($section->instructor)->name ?? 'TBA' }}
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                                    {{ $section->program->name ?? '-' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm">
                                                    @if($hasEvaluated)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Evaluated (tap to review)
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Pending
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <x-table-footer :paginator="$sections" />

                            <!-- Legend -->
                            <div class="mt-4 flex items-center gap-6 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                    </svg>
                                    <span>Click on pending rows to evaluate</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                                    <span>Evaluation completed</span>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
