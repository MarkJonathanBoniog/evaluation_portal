<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            $period->college->name . ' - ' . $period->department->name . ' | ' . $period->year_start.'-'.$period->year_end . ' ' . ucfirst($period->term) . ' semester' => route('manage.periods.index'),
            $program->name . ' - ' . $program->major => route('manage.programs.index', $period),
            'Courses' => '#'
        ]" />
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Courses</h2>
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
                <strong>Validation Errors:</strong>
                <ul class="list-disc list-inside text-sm mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header actions --}}
        <div class="flex flex-col sm:flex-row justify-between gap-3 sm:items-center bg-white dark:bg-gray-800 p-4 sm:rounded-lg shadow-sm">
            <div>
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Manage Course List for {{ $program->name }} — {{ $program->major }}</h3>
            </div>
            <div class="flex gap-2">
                <button 
                    x-data 
                    @click="$dispatch('open-modal', 'upload-csv')"
                    class="px-4 py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 dark:hover:bg-blue-900/40 transition">
                    Upload CSV
                </button>

                <button 
                    x-data 
                    @click="$dispatch('open-modal', 'add-course')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Add Course
                </button>
            </div>
        </div>

        {{-- Courses Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            @if($courses->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-2 w-32">Code</th>
                                <th class="py-3 px-2">Course Name</th>
                                <th class="py-3 px-2 w-32 text-center">Number of Sections</th>
                                <th class="py-3 px-2 w-40 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($courses as $c)
                                <tr class="text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="py-2 px-2 font-semibold">{{ $c->course_code }}</td>
                                    <td class="py-2 px-2">{{ $c->course_name }}</td>
                                    <td class="py-2 px-2 text-center">
                                        {{ $c->sections_count }}
                                    </td>
                                    <td class="py-2 px-2 text-right space-x-3">
                                        <a href="{{ route('manage.sections.index', [$period, $program, $c]) }}" 
                                           class="text-blue-600 hover:underline">Sections</a>
                                        <form action="{{ route('manage.courses.destroy', [$period, $program, $c]) }}" 
                                              method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-12">
                    <p class="mt-2">No courses yet. You can add one manually or upload via CSV.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Course Modal --}}
    <x-modal name="add-course">
        <form method="POST" action="{{ route('manage.courses.store', [$period, $program]) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Add New Course</h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="course_code" value="Course Code" />
                    <x-text-input id="course_code" name="course_code" class="mt-1 block w-full" placeholder="e.g., IT101" required />
                </div>
                <div>
                    <x-input-label for="course_name" value="Course Name" />
                    <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" placeholder="e.g., System Integration & Analysis" required />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" type="button">Cancel</x-secondary-button>
                <x-primary-button>Add Course</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Upload CSV Modal --}}
    <x-modal name="upload-csv">
        <form method="POST" action="{{ route('manage.courses.import', [$period, $program]) }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Upload Courses via CSV</h2>

            <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                <p class="mb-2">Please <strong>download the current CSV template first</strong> before editing.</p>
                <p class="mb-2">Each row represents a <strong>course entry</strong>. When re-uploading:</p>
                <ul class="list-disc list-inside">
                    <li><strong>New</strong> course codes will be added automatically.</li>
                    <li><strong>Existing</strong> courses will be updated if names differ.</li>
                    <li><strong>Deleted</strong> rows in the CSV will also be removed from this program’s list.</li>
                </ul>
                <p class="mt-2 text-red-600 dark:text-red-400">Make sure the CSV reflects your final intended course list before uploading.</p>
            </div>

            <a href="{{ route('manage.courses.export', [$period, $program]) }}" 
               class="inline-block text-blue-600 hover:underline mt-3">
               Download Current CSV Template
            </a>

            <div>
                <x-input-label for="csv_file" value="Select CSV File" />
                <x-text-input id="csv_file" name="csv_file" type="file" accept=".csv" required class="mt-1 block w-full" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" type="button">Cancel</x-secondary-button>
                <x-primary-button>Upload & Sync</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
