<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name => route('manage.periods.index'),
            $program->name . ' - ' . ($program->major ?? '') => route('manage.programs.index', $period),
            $course->course_code . ' - ' . $course->course_name => route('manage.courses.index', [$period, $program]),
            'Sections' => '#'
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Sections</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Create and manage sections for this course, including instructor assignments. Keep labels and instructors accurate to support rosters and evaluations.
        </p>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        {{-- Alerts --}}
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

        {{-- Header summary + actions (styled like Courses page) --}}
        <div class="flex flex-col sm:flex-row justify-between gap-3 sm:items-center bg-white dark:bg-gray-800 p-4 sm:rounded-lg shadow-sm">
            <div>
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Manage Sections for Course:</h3>
                <h3 class="font-normal text-lg text-gray-800 dark:text-gray-100">{{ $course->course_code }} - {{ $course->course_name }}</h3>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('manage.sections.download-template', [$period, $program, $course]) }}"
                   class="px-4 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/40 transition">
                    Download CSV
                </a>

                <button x-data @click="$dispatch('open-upload-sections')"
                        class="px-4 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/40 transition">
                    Upload CSV
                </button>

                <button x-data @click="$dispatch('open-add-section')"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Add Section
                </button>
            </div>
        </div>

        {{-- Sections Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="flex flex-col gap-3 sm:flex-col lg:flex-row lg:items-end lg:flex-nowrap mb-4">
                <div class="w-full lg:flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Search section label, instructor name, email, or UID"
                        class="mt-1 w-full rounded border-gray-300"
                    >
                </div>
                <div class="w-full lg:w-auto flex items-end gap-2 justify-end lg:justify-start">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                    <a href="{{ route('manage.sections.index', [$period, $program, $course]) }}" class="px-3 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50">Reset</a>
                </div>
            </form>

            @if($sections->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-3 px-2 w-36">Section Label</th>
                            <th class="py-3 px-2 w-40">Instructor UID</th>
                            <th class="py-3 px-2 w-56">Instructor Email</th>
                            <th class="py-3 px-2">Instructor Name</th>
                            <th class="py-3 px-2">Class Size</th>
                            <th class="py-3 px-2 w-48 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sections as $s)
                            <tr class="text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="py-2 px-2 font-semibold">{{ $s->section_label }}</td>
                                <td class="py-2 px-2">{{ $s->instructor?->instructorProfile?->instructor_uid ?? '—' }}</td>
                                <td class="py-2 px-2">{{ $s->instructor?->email ?? '—' }}</td>
                                <td class="py-2 px-2">{{ $s->instructor?->name ?? '—' }}</td>
                                <td class="py-2 px-2 text-center">
                                    {{ $s->students->count() }}
                                </td>
                                <td class="py-2 px-2 text-right space-x-3">
                                    <a href="{{ route('manage.roster.index', [$period, $program, $course, $s]) }}"
                                       class="text-blue-600 hover:underline">Roster</a>

                                    <form action="{{ route('manage.sections.destroy', [$period, $program, $course, $s]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Delete this section? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <x-table-footer :paginator="$sections" />
            @else
                <div class="text-center text-gray-500 py-12">
                    @if($filters['q'] ?? false)
                        <p class="mt-2">No sections match your search.</p>
                    @else
                        <p class="mt-2">No sections yet. You can add one manually or manage via CSV.</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ===== Modals (same behavior, cleaned visuals) ===== --}}

        {{-- A) Add Section (manual; preserves Alpine search dropdown) --}}
        <div x-data="{ open: false }"
             x-on:open-add-section.window="open = true"
             x-on:keydown.escape.window="open = false"
             x-show="open"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             style="display:none">
            <div @click.outside="open = false"
                 class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-lg p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Add Section</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    Enter the <b>Section Label</b> and pick an <b>Instructor</b>. Both are saved together.
                </p>

                {{-- IMPORTANT: x-data defines both "section" and "selected" here to avoid undefined refs --}}
                <form method="POST"
                      action="{{ route('manage.sections.store', [$period, $program, $course]) }}"
                      class="grid grid-cols-1 gap-4"
                      x-data="{ section: '', selected: null }"
                      x-on:submit.prevent="if (section && selected) $el.submit();">
                    @csrf

                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Section Label</label>
                        <input name="section_label" x-model.trim="section"
                               placeholder="A / B / 3A"
                               class="w-full border-gray-300 rounded" required>
                    </div>

                    <div x-data="{
                            openDD: false,
                            query: '',
                            selectedId: null,
                            list: @js($instructors),
                            filtered() {
                                const q = this.query.toLowerCase();
                                return this.list.filter(i => i.name.toLowerCase().includes(q)).slice(0, 10);
                            },
                            choose(i) {
                                this.selectedId = i.id; this.query = i.name; this.openDD = false;
                                $dispatch('set-instructor', { id: i.id });
                            }
                        }"
                         x-on:set-instructor.window="selected = $event.detail.id" class="relative">
                        <label class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Instructor</label>
                        <input type="text" x-model="query" @focus="openDD = true"
                               @blur="setTimeout(()=> openDD = false, 120)"
                               placeholder="Search instructor by name…"
                               class="w-full border-gray-300 rounded" autocomplete="off">
                        <input type="hidden" name="instructor_user_id" x-model.number="selected">

                        <ul x-show="openDD"
                            class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto w-full">
                            <template x-for="ins in filtered()" :key="ins.id">
                                <li @click="choose(ins)"
                                    class="px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700"
                                    x-text="ins.name"></li>
                            </template>
                            <li x-show="filtered().length === 0" class="px-3 py-2 text-gray-500">No matches</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-2">
                        <button type="button" @click="open=false"
                                class="px-3 py-2 text-sm rounded border border-gray-300">Cancel</button>
                        <button :disabled="!(section && selected)"
                                class="px-3 py-2 text-sm rounded text-white"
                                :class="(section && selected) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'">
                            Save Section
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- B) Upload Sections CSV --}}
        <div x-data="{ open: false }"
             x-on:open-upload-sections.window="open = true"
             x-on:keydown.escape.window="open = false"
             x-show="open"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
             style="display:none">
            <div @click.outside="open = false"
                 class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-lg p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Upload Sections CSV</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
                </div>

                <ol class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4 list-decimal ms-5">
                    <li><b>Download the template</b> first to see the correct headers and current data.</li>
                    <li>Each row is <b>one Section + its Instructor assignment</b>.</li>
                    <li>We resolve instructors by <b>Instructor UID</b> (preferred) or <b>Email</b>.</li>
                    <li><b>Mode = Merge</b> adds/updates rows only. <b>Mode = Sync</b> also deletes sections missing from the CSV.</li>
                    <li>Changes apply immediately to the database. Remove rows only if you truly want them deleted.</li>
                </ol>

                <div class="mb-3">
                    <a href="{{ route('manage.sections.download-template', [$period, $program, $course]) }}"
                       class="inline-flex items-center gap-2 text-blue-600 hover:underline">
                        ⬇️ Download current template
                    </a>
                </div>

                <form method="POST"
                      action="{{ route('manage.sections.upload-csv', [$period, $program, $course]) }}"
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
                            <option value="merge" selected>Merge (add + update only)</option>
                            <option value="sync">Sync (add + update + delete missing)</option>
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
