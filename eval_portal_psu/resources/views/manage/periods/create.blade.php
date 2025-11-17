<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Create Academic Period
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
            @php
                $singleCollegeId = $colleges->count() === 1 ? $colleges->first()->id : '';
                $singleDeptId    = $departments->count() === 1 ? $departments->first()->id : '';
            @endphp

            <form method="POST" action="{{ route('manage.periods.store') }}" class="space-y-6">
                @csrf

                <div
                    x-data="{
                        selectedCollege: '{{ old('college_id', $singleCollegeId) }}',
                        selectedDepartment: '{{ old('department_id', $singleDeptId) }}',
                        departmentsByCollege: @js($departments->groupBy('college_id')->map->values()),
                        get filteredDepartments() {
                            return this.departmentsByCollege[this.selectedCollege] || [];
                        },
                        onCollegeChange() {
                            if (! this.filteredDepartments.find(d => String(d.id) === String(this.selectedDepartment))) {
                                this.selectedDepartment = '';
                            }
                        }
                    }"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                >
                    {{-- College --}}
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300">College</label>

                        @if($colleges->count() === 1)
                            @php $college = $colleges->first(); @endphp

                            <input type="hidden" name="college_id" value="{{ $college->id }}">

                            <div class="mt-1 w-full border border-gray-200 dark:border-gray-700 rounded px-3 py-2 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-200 text-sm">
                                {{ $college->name }}
                            </div>
                        @else
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
                        @endif

                        @error('college_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300">Department</label>

                        @if($departments->count() === 1)
                            @php $dept = $departments->first(); @endphp

                            <input type="hidden" name="department_id" value="{{ $dept->id }}">

                            <div class="mt-1 w-full border border-gray-200 dark:border-gray-700 rounded px-3 py-2 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-200 text-sm">
                                {{ $dept->name }}
                            </div>
                        @else
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
                        @endif

                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Academic year + term --}}
                @php
                    // You can tweak this range however you like
                    $startYear = 2010;
                    $endYear   = 2099;
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-gray-600 dark:text-gray-300">
                            Academic Year
                        </label>
                        <select
                            name="academic_year"
                            class="mt-1 w-full border-gray-300 rounded"
                            required
                        >
                            <option value="">Select academic year</option>
                            @for($y = $startYear; $y <= $endYear; $y++)
                                @php $pair = $y.'-'.($y + 1); @endphp
                                <option value="{{ $pair }}" @selected(old('academic_year') === $pair)>
                                    {{ $pair }}
                                </option>
                            @endfor
                        </select>
                        @error('academic_year')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300">Term</label>
                        <select name="term" class="mt-1 w-full border-gray-300 rounded" required>
                            <option value="">Select</option>
                            <option value="first"  @selected(old('term') === 'first')>1st</option>
                            <option value="second" @selected(old('term') === 'second')>2nd</option>
                            <option value="summer" @selected(old('term') === 'summer')>Summer</option>
                        </select>
                        @error('term')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('manage.periods.index') }}" class="px-4 py-2 text-sm me-3">
                        Cancel
                    </a>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        Create &amp; Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
