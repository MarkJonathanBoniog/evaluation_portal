<x-app-layout>
    <x-slot name="header">
        {{-- optional breadcrumbs if you want --}}
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name => route('manage.periods.index'),
            $program->name . ' - ' . $program->major => route('manage.programs.index', $period),
            $course->course_code . ' - ' . $course->course_name => route('manage.courses.index', [$period, $program]),
            'Sections' => '#'
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Sections') }}</h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
<form method="POST"
      action="{{ route('manage.sections.store', [$period, $program, $course]) }}"
      class="grid grid-cols-1 sm:grid-cols-3 gap-4"
      x-data="{ section: '', selected: null }"  {{-- <- init here --}}
      x-on:submit.prevent="
          if (section && selected) $el.submit();
      "
>
    @csrf

    <input name="section_label"
           x-model.trim="section"
           placeholder="A / B / 3A"
           class="border-gray-300 rounded"
           required>

    <div
        x-data="{
            open: false,
            query: '',
            selectedId: null,
            instructors: @js($instructors),
            filtered() {
                const q = this.query.toLowerCase();
                return this.instructors.filter(i => i.name.toLowerCase().includes(q)).slice(0, 10);;
            },
            choose(i) {
                this.selectedId = i.id;
                this.query = i.name;
                this.open = false;
                // bubble up to parent form x-data
                $dispatch('set-instructor', { id: i.id });
            }
        }"
        x-on:set-instructor.window="selected = $event.detail.id"
        class="relative"
    >
        <input type="text"
               x-model="query"
               @focus="open = true"
               @blur="setTimeout(()=> open = false, 100)"
               placeholder="Search instructor..."
               class="border-gray-300 rounded w-full">

        {{-- IMPORTANT: force numeric binding --}}
        <input type="hidden" name="instructor_user_id" x-model.number="selected">

        <ul x-show="open"
            class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto w-full">
            <template x-for="ins in filtered()" :key="ins.id">
                <li @click="choose(ins)"
                    class="px-3 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-gray-700"
                    x-text="ins.name">
                </li>
            </template>
            <li x-show="filtered().length === 0" class="px-3 py-2 text-gray-500">No matches</li>
        </ul>
    </div>

    <button
        :disabled="!(section && selected)"
        class="px-4 py-2 rounded text-white"
        :class="(section && selected) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'">
        Add Section
    </button>
</form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">Section</th>
                        <th class="py-2">Instructor ID</th>
                        <th class="py-2">Instructor</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sections as $s)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2 font-semibold">{{ $s->section_label }}</td>
                            <td class="py-2">{{ $s->instructor?->instructorProfile?->instructor_uid ?? '—' }}</td>
                            <td class="py-2">{{ $s->instructor?->name ?? '—' }}</td>
                            <td class="py-2 text-left space-x-3">
                                {{-- Roster link (full chain) --}}
                                <a href="{{ route('manage.roster.index', [$period, $program, $course, $s]) }}"
                                class="text-blue-600 hover:underline">
                                Roster
                                </a>

                                {{-- Delete --}}
                                <form action="{{ route('manage.sections.destroy', [$period, $program, $course, $s]) }}"
                                      method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-500">No sections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
