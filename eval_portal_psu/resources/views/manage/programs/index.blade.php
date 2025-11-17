<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name . ' | ' . $period->year_start . '-' . $period->year_end . ' ' . $period->term . ' semester' => route('manage.periods.index'),
            'Programs' => '#'
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Programs & Majors for Period') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <div class="text-md text-gray-900 dark:text-gray-300">
                {{ $period->college->name }} - {{ $period->department->name }} || 
                AY {{ $period->year_start }}–{{ $period->year_end }} • Term: <span class="capitalize">{{ $period->term }}</span>
            </div>

<form method="POST" action="{{ route('manage.programs.store', $period) }}"
      class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    @csrf

    {{-- Program Name --}}
    <div class="flex flex-col">
        <label class="text-sm text-gray-600 dark:text-gray-300 mb-1">
            Program Name
        </label>
        <input
            name="name"
            placeholder="e.g., BS IT"
            class="border-gray-300 rounded"
            required
        >
        @error('name')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Major --}}
    <div class="flex flex-col">
        <label class="text-sm text-gray-600 dark:text-gray-300 mb-1">
            Major / Specialization
        </label>
        <input
            name="major"
            placeholder="e.g., Web & Mobile Dev"
            class="border-gray-300 rounded"
        >
        @error('major')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Submit --}}
    <div class="flex items-end">
        <button class="px-4 py-2 bg-blue-600 text-white rounded w-full sm:w-auto">
            Add Program
        </button>
    </div>
</form>

        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">Program</th>
                        <th class="py-2">Major</th>
                        <th class="py-2 text-center">Number of Courses</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($programs as $program)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2">{{ $program->name }}</td>
                            <td class="py-2">{{ $program->major ?: '—' }}</td>
                            <td class="py-2 text-center">{{ $program->courses_count }}</td>
                            <td class="py-2 text-left">
                                <a href="{{ route('manage.courses.index', [$period, $program]) }}" class="text-blue-600 hover:underline">
                                    Courses
                                </a>
                                <form action="{{ route('manage.programs.destroy', [$period, $program], $program) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ms-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-500">No programs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
