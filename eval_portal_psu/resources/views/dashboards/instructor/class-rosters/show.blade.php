<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Class Roster - {{ $course->course_code }} {{ $section->section_label }}
            <span class="text-sm text-gray-500">
                ({{ $course->course_name }})
            </span>
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Manage the roster for this section, including enrollment and evaluation status. Add or remove students and keep the list current for accurate tracking.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-600 p-4 rounded text-blue-900 dark:text-blue-100">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 p-4 rounded text-red-900 dark:text-red-100">
                <strong>Import Issues:</strong>
                <ul class="list-disc list-inside text-sm mt-2">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header actions (CSV) --}}
                <div class="flex flex-col sm:flex-row justify-between gap-3 sm:items-start bg-white dark:bg-gray-800 p-4 sm:rounded-lg shadow-sm">
            <div class="text-sm text-gray-700 dark:text-gray-200 space-y-0.5">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Manage Class Roster for Section {{ $section->section_label }}</h3>
                <div><span class="font-medium"><strong>Course:</strong></span> {{ $course->course_code }} — {{ $course->course_name }} </div>
                <div><span class="font-medium"><strong>Program:</strong></span> {{ $program->name }} @if($program->major) - {{ $program->major }} @endif</div>
                <div><span class="font-medium">A.Y.:</span> {{ $period->year_start }}–{{ $period->year_end }} — {{ \Illuminate\Support\Str::title($period->term) }} Semester</div>
            </div>

            <div class="flex gap-2 justify-start items-start">
                <a href="{{ route('manage.roster.download-template', [$period, $program, $course, $section]) }}"
                   class="px-4 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/40 transition">
                    Download CSV
                </a>

                <button x-data @click="$dispatch('open-upload-roster')"
                        class="px-4 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/40 transition">
                    Upload CSV
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- 1. Total students --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Total Students
                </span>
                <span class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $totalStudents }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Enrolled in this section
                </span>
            </div>

            {{-- 2. Students who evaluated --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Completed Evaluations
                </span>
                <span class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ $totalEvaluated }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Students who have submitted their SET
                </span>
            </div>

            {{-- 3. Evaluation percentage --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 flex flex-col">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">
                    Evaluation Coverage
                </span>
                <span class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $totalStudents > 0 ? $evaluationRate . '%' : '—' }}
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Of students in this section have evaluated you
                </span>
            </div>
        </div>



        {{-- Manual add (UNCHANGED core logic) --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST"
      action="{{ route('instructor.class-rosters.store', $section) }}"
      class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
    @csrf

    <div class="w-full sm:w-auto flex-1">
        <label for="student_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Manual Adding of Students
        </label>

        <div
            x-data="{
                open: false,
                query: '',
                selected: null,
                students: @js($candidates),
                filtered() {
                    return this.students.filter(st =>
                        (st.name.toLowerCase().includes(this.query.toLowerCase()) ||
                        (st.student_profile.student_number ?? '').toLowerCase().includes(this.query.toLowerCase()))
                    ).slice(0, 10);
                }
            }"
            class="relative w-full"
        >
            <input
                id="student_search"
                type="text"
                x-model="query"
                x-on:focus="open = true"
                x-on:blur="setTimeout(() => open = false, 100)"
                placeholder="Enter name or student number..."
                class="border-gray-300 rounded w-full"
            >
            <input type="hidden" name="student_user_id" x-model="selected">

            <ul x-show="open"
                class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto w-full">
                <template x-for="st in filtered()" :key="st.id">
                    <li @click="
                            selected = st.id;
                            query = (st.student_profile.student_number ? `${st.student_profile.student_number} — ` : '') + st.name;
                            open = false;"
                        class="px-3 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-gray-700">
                        <span
                            x-text="st.student_profile.student_number
                                ? `${st.student_profile.student_number}: ${st.name}`
                                : st.name">
                        </span>
                    </li>
                </template>
                <li x-show="filtered().length === 0" class="px-3 py-2 text-gray-500">No matches found</li>
            </ul>
        </div>
    </div>

    <button class="px-4 py-2 bg-blue-600 text-white rounded mt-1 sm:mt-0">
        Add
    </button>
</form>
        </div>

        {{-- Roster table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap mb-4">
                <div class="w-full lg:flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search for students</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Search student name, email, or number"
                        class="mt-1 w-full rounded border-gray-300"
                    >
                </div>
                <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                    <a href="{{ route('instructor.class-rosters.show', $section) }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">Student Number</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Date Evaluated</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($students as $st)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2">{{ $st->studentProfile->student_number ?? '—' }}</td>
                            <td class="py-2">{{ $st->name }}</td>
                            <td class="py-2">{{ $st->email }}</td>
                            <td class="py-2">
                                @if($st->pivot->evaluated_at)
                                    <span class="text-green-600">Evaluated</span>
                                @else
                                    <span class="text-red-600">Not yet</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if($st->pivot->evaluated_at)
                                    <span class="text-green-600">{{ \Carbon\Carbon::parse($st->pivot->evaluated_at)->toDayDateTimeString() }}</span>
                                @else
                                    <span class="text-red-600">-</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <form method="POST"
                                      action="{{ route('instructor.class-rosters.destroy', [$section, $st->id]) }}"
                                      class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No students yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-footer :paginator="$students" />
        </div>

        {{-- Upload Roster CSV Modal --}}
        <div x-data="{ open: false }"
             x-on:open-upload-roster.window="open = true"
             x-on:keydown.escape.window="open = false"
             x-show="open"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             style="display:none">
            <div @click.outside="open = false"
                 class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-lg p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Upload Roster CSV</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
                </div>

                <ol class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4 list-decimal ms-5">
                    <li><b>Download the template</b> first to see the correct headers and current roster.</li>
                    <li>Each row represents <b>one Student</b>. We match by <b>Student Number</b> only.</li>
                    <li><b>Merge</b> adds students not currently in the roster. <b>Sync</b> also removes students missing from the CSV.</li>
                </ol>

                <div class="mb-3">
                    <a href="{{ route('instructor.class-rosters.download-template', [$period, $program, $course, $section]) }}"
                       class="inline-flex items-center gap-2 text-blue-600 hover:underline">
                        ⬇Download current template
                    </a>
                </div>

                <form method="POST"
                      action="{{ route('instructor.class-rosters.upload-csv', [$period, $program, $course, $section]) }}"
                      enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm mb-1">CSV File</label>
                        <input type="file" name="csv_file" accept=".csv"
                               class="w-full border-gray-300 rounded p-1" required>
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Mode</label>
                        <select name="mode" class="w-full border-gray-300 rounded">
                            <option value="merge" selected>Merge (add only or update set)</option>
                            <option value="sync">Sync (add + remove missing)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="open=false"
                                class="px-3 py-2 text-sm rounded border border-gray-300">Cancel</button>
                        <button class="px-3 py-2 text-sm rounded text-white bg-blue-600 hover:bg-blue-700">
                            Upload CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
