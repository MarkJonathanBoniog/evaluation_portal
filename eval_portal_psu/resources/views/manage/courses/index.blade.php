<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name . ' | ' . $period->year_start.'-'.$period->year_end . ' ' . $period->term . ' semester' => route('manage.periods.index'),
            $program->name . ' - ' . $program->major => route('manage.programs.index', $period),
            'Courses' => '#'
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Courses') }}</h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('manage.courses.store', [$period, $program]) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <input name="course_code" placeholder="XXX-000" class="border-gray-300 rounded" required>
                <input name="course_name" placeholder="Course Title Here..." class="border-gray-300 rounded" required>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Add Course</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">Code</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($courses as $c)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2 font-semibold">{{ $c->course_code }}</td>
                            <td class="py-2">{{ $c->course_name }}</td>
                            <td class="py-2 text-left">
                                <a href="{{ route('manage.sections.index', [$period, $program, $c]) }}"
                   class="text-blue-600 hover:underline">Sections</a> 
                                <form action="{{ route('manage.courses.destroy', [$period, $program, $c]) }}" method="POST" class="inline ms-3">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-500">No courses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
