<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if($user->hasRole('student'))
            <div>
                <x-input-label for="student_number" :value="__('Student Number')" />
                <input
                    id="student_number"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-200 bg-gray-100 text-gray-700"
                    value="{{ $user->studentProfile?->student_number ?? 'Not assigned' }}"
                    readonly
                    tabindex="-1"
                >
                <p class="mt-1 text-xs text-gray-500">{{ __('Student numbers are managed by administrators.') }}</p>
            </div>
        @endif

        @if($user->hasRole('instructor'))
            <div>
                <x-input-label for="faculty_rank" :value="__('Faculty Rank')" />
                <x-text-input
                    id="faculty_rank"
                    name="faculty_rank"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('faculty_rank', $facultyRank)"
                    autocomplete="organization-title"
                />
                <x-input-error class="mt-2" :messages="$errors->get('faculty_rank')" />
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="college_select" :value="__('College')" />
                <select
                    id="college_select"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700"
                >
                    <option value="">-- {{ __('Select College') }} --</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" @selected($selectedCollegeId === $college->id)>
                            {{ $college->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Choose your college to filter departments.') }}</p>
            </div>
            <div>
                <x-input-label for="department_id" :value="__('Department')" />
                <select
                    id="department_id"
                    name="department_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-700"
                    required
                >
                    <option value="">-- {{ __('Select Department') }} --</option>
                    @foreach($departments as $dept)
                        <option
                            value="{{ $dept->id }}"
                            data-college="{{ $dept->college?->id }}"
                            @selected($selectedDepartmentId === $dept->id)
                        >
                            {{ $dept->college?->name ? $dept->college->name.' — '.$dept->name : $dept->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <script>
        (function() {
            const collegeSelect = document.getElementById('college_select');
            const departmentSelect = document.getElementById('department_id');
            if (!collegeSelect || !departmentSelect) return;

            const filterDepartments = () => {
                const selectedCollege = collegeSelect.value;
                Array.from(departmentSelect.options).forEach((opt) => {
                    if (!opt.value) return; // skip placeholder
                    const match = !selectedCollege || opt.dataset.college === selectedCollege;
                    opt.hidden = !match;
                });

                // If current selection is hidden, reset to placeholder
                if (departmentSelect.selectedOptions.length) {
                    const current = departmentSelect.selectedOptions[0];
                    if (current.hidden) {
                        departmentSelect.value = '';
                    }
                }
            };

            collegeSelect.addEventListener('change', filterDepartments);
            filterDepartments();
        })();
    </script>
</section>
