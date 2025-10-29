<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name => route('manage.periods.index'),
            $program->name . ' - ' . $program->major => route('manage.programs.index', $period),
            $course->course_code . ' - ' . $course->course_name => route('manage.courses.index', [$period, $program]),
            'Section: ' . $section->section_label => route('manage.sections.index', [$period, $program, $course]),
            'Class Roster' => '#',
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Class Roster') }}</h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST"
                  action="{{ route('manage.roster.store', [$period, $program, $course, $section]) }}"
                  class="flex gap-3">
                @csrf
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
                    <!-- Search field -->
                    <input 
                        type="text"
                        x-model="query"
                        x-on:focus="open = true"
                        x-on:blur="setTimeout(() => open = false, 100)"
                        placeholder="Search by name or student number..."
                        class="border-gray-300 rounded w-full"
                    >

                    <!-- Hidden input for form -->
                    <input type="hidden" name="student_user_id" x-model="selected">

                    <!-- Dropdown results -->
                    <ul 
                        x-show="open" 
                        class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto w-full"
                    >
                        <template x-for="st in filtered()" :key="st.id">
                            <li 
                                @click="
                                    selected = st.id; 
                                    query = (st.student_profile.student_number ? `${st.student_profile.student_number} — ` : '') + st.name;
                                    open = false;
                                "
                                class="px-3 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-gray-700"
                            >
                                <span 
                                    x-text="st.student_profile.student_number 
                                        ? `${st.student_profile.student_number}: ${st.name}` 
                                        : st.name">
                                </span>
                            </li>
                        </template>

                        <li 
                            x-show="filtered().length === 0"
                            class="px-3 py-2 text-gray-500"
                        >
                            No matches found
                        </li>
                    </ul>
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Add</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500">
                <tr>
                    <th class="py-2">Student Number</th>
                    <th class="py-2">Name</th>
                    <th class="py-2">Email</th>
                    <th class="py-2">Evaluation Status</th>
                    <th class="py-2">Actions</th>
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
                        <td class="py-2 text-left">
                            <form method="POST"
                                action="{{ route('manage.roster.destroy', [$period, $program, $course, $section, $st->id]) }}"
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
    </div>
</x-app-layout>
