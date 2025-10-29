<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Academic Periods') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div class="text-gray-700 dark:text-gray-200">{{ __('Create new period') }}</div>
                <a href="{{ route('manage.periods.create') }}" class="inline-flex items-center px-3 py-2 rounded bg-blue-600 text-white text-sm">+ {{ __('New Period') }}</a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th class="py-2">College</th>
                        <th class="py-2">Department</th>
                        <th class="py-2">Academic Year</th>
                        <th class="py-2">Term</th>
                        <th class="py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($periods as $p)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="py-2">{{ $p->college->name ?? '—' }}</td>
                            <td class="py-2">{{ $p->department->name ?? '—' }}</td>
                            <td class="py-2">{{ $p->year_start }}–{{ $p->year_end }}</td>
                            <td class="py-2 capitalize">{{ $p->term }}</td>
                            <td class="py-2 text-left">
                                <a href="{{ route('manage.programs.index', $p) }}" class="text-blue-600 hover:underline">Programs</a>
                                <form action="{{ route('manage.periods.destroy', $p) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ms-3">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No periods yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $periods->links() }}</div>
        </div>
    </div>
</x-app-layout>
