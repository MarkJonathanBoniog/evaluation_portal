<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">{{ __('Create Period') }}</h2></x-slot>

    <div class="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
            <form method="POST" action="{{ route('manage.periods.store') }}" class="space-y-4">
                @csrf
                <div
                    x-data="{
                        selectedCollege: '{{ old('college_id') }}',
                        selectedDepartment: '{{ old('department_id') }}',
                        // keyed by college_id -> [{id, department_name, college_id}, ...]
                        departmentsByCollege: @js($departments->groupBy('college_id')->map->values()),
                        get filteredDepartments() {
                            return this.departmentsByCollege[this.selectedCollege] || [];
                        },
                        onCollegeChange() {
                            // reset department if it doesn't belong to the new college
                            if (! this.filteredDepartments.find(d => String(d.id) === String(this.selectedDepartment))) {
                                this.selectedDepartment = '';
                            }
                        }
                    }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                >
                    <!-- College -->
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300">College</label>
                        <select
                            name="college_id"
                            class="mt-1 w-full border-gray-300 rounded"
                            x-model="selectedCollege"
                            @change="onCollegeChange()"
                            required
                        >
                            <option value="">-- Select College --</option>
                            @foreach($colleges as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('college_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Department (enabled only after college is chosen) -->
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300">Department</label>
                        <select
                            name="department_id"
                            class="mt-1 w-full border-gray-300 rounded"
                            x-model="selectedDepartment"
                            :disabled="!selectedCollege"
                            required
                        >
                            <option value="">
                                <template x-if="!selectedCollege">-- Select College first --</template>
                                <template x-if="selectedCollege">-- Select Department --</template>
                            </option>
                            <template x-for="dept in filteredDepartments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.name"></option>
                            </template>
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm">Year Start</label>
                        <input name="year_start" type="number" class="mt-1 w-full border-gray-300 rounded" required>
                    </div>
                    <div>
                        <label class="block text-sm">Year End</label>
                        <input name="year_end" type="number" class="mt-1 w-full border-gray-300 rounded" required>
                    </div>
                    <div>
                        <label class="block text-sm">Term</label>
                        <select name="term" class="mt-1 w-full border-gray-300 rounded" required>
                            <option value="">Select</option>
                            <option value="first">1st</option>
                            <option value="second">2nd</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a href="{{ route('manage.periods.index') }}" class="px-4 py-2 text-sm me-3">Cancel</a>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Create & Continue</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
