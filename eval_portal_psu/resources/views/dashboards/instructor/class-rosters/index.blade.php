<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Lit of Sections
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            See every section you handle along with class sizes and evaluation coverage. Open a roster to manage students or monitor who has completed evaluations.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-600 p-4 rounded text-blue-900 dark:text-blue-100">
                {{ session('status') }}
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            {{-- 1. Total sections --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Total Sections
                </span>
                <span class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $totalSections }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Sections currently assigned to you
                </span>
            </div>

            {{-- 2. Total students --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Total Students
                </span>
                <span class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $totalStudents }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Across all of your sections
                </span>
            </div>

            {{-- 3. Students who evaluated --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Completed Evaluations
                </span>
                <span class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ $totalEvaluated }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Students who have already evaluated you
                </span>
            </div>

            {{-- 4. Evaluation percentage --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Evaluation Coverage
                </span>
                <span class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $totalStudents > 0 ? $evaluationRate . '%' : '—' }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Of enrolled students have submitted evaluations
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap mb-4">
                <div class="w-full lg:flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Search course, program, or section"
                        class="mt-1 w-full rounded border-gray-300"
                    >
                </div>
                <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                    <a href="{{ route('instructor.class-rosters.index') }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            @if($sections->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @if($filters['q'] ?? false)
                        No sections match your search.
                    @else
                        You are not currently assigned to any sections.
                    @endif
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-3 px-2">Course</th>
                            <th class="py-3 px-2">Section</th>
                            <th class="py-3 px-2">Program</th>
                            <th class="py-3 px-2">A.Y. / Term</th>
                            <th class="py-3 px-2 text-center">Class Size</th>
                            <th class="py-3 px-2 text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sections as $section)
                            <tr class="text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="py-2 px-2">
                                    <div class="font-semibold">
                                        {{ $section->course->course_code ?? '—' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $section->course->course_name ?? '' }}
                                    </div>
                                </td>
                                <td class="py-2 px-2">
                                    {{ $section->section_label }}
                                </td>
                                <td class="py-2 px-2">
                                    {{ $section->program->name ?? '—' }}
                                    @if($section->program?->major)
                                        – {{ $section->program->major }}
                                    @endif
                                </td>
                                <td class="py-2 px-2 text-xs">
                                    AY {{ $section->period->year_start }}–{{ $section->period->year_end }}<br>
                                    <span class="capitalize">{{ $section->period->term }}</span> semester
                                </td>
                                <td class="py-2 px-2 text-center">
                                    {{ $section->students_count }}
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <a href="{{ route('instructor.class-rosters.show', $section) }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-blue-600 text-white hover:bg-blue-700">
                                        Manage roster
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <x-table-footer :paginator="$sections" />
            @endif
        </div>
    </div>
</x-app-layout>
