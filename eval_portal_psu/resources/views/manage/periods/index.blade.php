<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Academic Periods
            </h2>

            @hasanyrole('chairman|dean|ced|systemadmin')
                <a href="{{ route('manage.periods.create') }}"
                   class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + New Academic Period
                </a>
            @endhasanyrole
        </div>
    </x-slot>

    <div class="py-6 max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            @if(session('status'))
                <div class="mb-4 text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            @if($periods->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    No academic periods have been created yet.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                            <th class="px-4 py-2 text-left">College</th>
                            <th class="px-4 py-2 text-left">Department</th>
                            <th class="px-4 py-2 text-left">Academic Year</th>
                            <th class="px-4 py-2 text-left">Term</th>
                            <th class="px-4 py-2 text-left">Created By</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($periods as $period)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors">
                                <td class="px-4 py-2">
                                    {{ $period->college->name ?? '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $period->department->name ?? '-' }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ $period->year_start }}–{{ $period->year_end }}
                                </td>
                                <td class="px-4 py-2 capitalize">
                                    {{ $period->term }}
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $period->creator->name ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <a href="{{ route('manage.programs.index', $period) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900/60">
                                        Programs
                                    </a>

                                    <form action="{{ route('manage.periods.destroy', $period) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Delete this academic period? This cannot be undone.')"
                                                class="inline-flex items-center px-2.5 py-1.5 border border-red-300 rounded text-xs text-red-700 hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $periods->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
