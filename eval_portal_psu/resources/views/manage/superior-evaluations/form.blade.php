{{-- resources/views/manage/superior-evaluations/form.blade.php --}}
<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
            <a href="{{ route($backRoute) }}"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200">
                &larr; Back to evaluation list
            </a>
        </div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    @if($evaluatedAs === 'chairman')
                        Chairman Evaluation of Faculty
                    @elseif($evaluatedAs === 'dean')
                        Dean Evaluation of Chairmen / Faculty
                    @elseif($evaluatedAs === 'ced')
                        CED Evaluation of Academic Heads
                    @else
                        Superior Evaluation
                    @endif
                </h1>

                <div class="text-sm text-gray-600 space-y-1">
                    <p>
                        <strong>Subject of Evaluation:</strong>
                        {{ $subjectUser->name ?? 'N/A' }}
                    </p>
                    <p>
                        <strong>Academic Year / Term:</strong>
                        AY {{ $period->year_start }}–{{ $period->year_end }}
                        – <span class="capitalize">{{ $period->term }}</span> semester
                    </p>
                    <p>
                        <strong>Evaluator Role:</strong>
                        {{ ucfirst($evaluatedAs) }}
                    </p>
                </div>
            </div>

            <!-- Rating Scale Legend -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-3">Rating Scale</h2>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
                    <div class="bg-white p-2 rounded">
                        <span class="font-bold text-blue-600">5 - Always</span>
                        <p class="text-gray-600 mt-1">91-100% of instances</p>
                    </div>
                    <div class="bg-white p-2 rounded">
                        <span class="font-bold text-blue-600">4 - Often</span>
                        <p class="text-gray-600 mt-1">61-90% of instances</p>
                    </div>
                    <div class="bg-white p-2 rounded">
                        <span class="font-bold text-blue-600">3 - Sometimes</span>
                        <p class="text-gray-600 mt-1">31-60% of instances</p>
                    </div>
                    <div class="bg-white p-2 rounded">
                        <span class="font-bold text-blue-600">2 - Seldom</span>
                        <p class="text-gray-600 mt-1">11-30% of instances</p>
                    </div>
                    <div class="bg-white p-2 rounded">
                        <span class="font-bold text-blue-600">1 - Never/Rarely</span>
                        <p class="text-gray-600 mt-1">0-10% of instances</p>
                    </div>
                </div>
            </div>

            <!-- Evaluation Form -->
            <form id="evaluationForm"
                  action="{{ route('manage.superior-evaluations.store', [$period, $subjectUser]) }}"
                  method="POST">
                @csrf
                <input type="hidden" name="is_read_only" value="{{ $isReadOnly ? 1 : 0 }}">

                <!-- Section A: Management of Teaching and Learning -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        A. Management of Teaching and Learning
                    </h2>
                    <p class="text-sm text-gray-600 mb-4 italic">
                        Management of Teaching and Learning refers to the intentional and organized handling of
                        classroom presence, clear communication of academic expectations, efficient use of time,
                        and the purposeful use of student-centered activities.
                    </p>

                    @php
                        $sectionA = [
                            'a1' => 'Comes to class / sessions on time.',
                            'a2' => 'Explains learning outcomes expectations, grading system, and various requirements of the subject/course.',
                            'a3' => 'Maximizes the allocated time learning hours effectively.',
                            'a4' => 'Facilitates students to think critically and creatively by providing appropriate learning activities.',
                            'a5' => 'Guides students to learn on their own, reflect on new ideas and experiences, and make decisions in accomplishing given tasks.',
                            'a6' => 'Communicates constructive feedback to students for their academic growth.',
                        ];
                    @endphp

                    @foreach($sectionA as $key => $question)
                        <div class="mb-6 pb-6 border-b border-gray-200 last:border-0">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                {{ $loop->iteration }}. {{ $question }}
                            </label>
                            <div class="flex flex-wrap gap-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio"
                                               name="{{ $key }}"
                                               value="{{ $i }}"
                                               required
                                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                               {{ $isReadOnly ? 'disabled' : '' }}
                                               @checked(old($key, optional($evaluation)->{$key}) == $i) >
                                        <span class="ml-2 text-sm text-gray-700">{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error($key)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <!-- Section B: Content Knowledge, Pedagogy and Technology -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        B. Content Knowledge, Pedagogy and Technology
                    </h2>
                    <p class="text-sm text-gray-600 mb-4 italic">
                        Content Knowledge, Pedagogy, and Technology refer to a teacher's ability to demonstrate
                        a strong grasp of subject matter, present complex concepts clearly, and integrate appropriate
                        teaching strategies and tools.
                    </p>

                    @php
                        $sectionB = [
                            'b7'  => 'Demonstrates extensive and broad knowledge of the subject/course.',
                            'b8'  => 'Simplifies complex ideas in the lesson for ease of understanding.',
                            'b9'  => 'Relates the subject matter to contemporary issues and developments in the discipline and/or daily life activities.',
                            'b10' => 'Promotes active learning and student engagement by using appropriate teaching and learning resources including ICT tools and platforms.',
                            'b11' => 'Uses appropriate assessments (projects, exams, quizzes, assignments, etc.) aligned with the learning outcomes.',
                            'b12' => 'Demonstrates effective classroom management and creates a conducive learning environment.',
                        ];
                    @endphp

                    @foreach($sectionB as $key => $question)
                        <div class="mb-6 pb-6 border-b border-gray-200 last:border-0">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                {{ 6 + $loop->iteration }}. {{ $question }}
                            </label>
                            <div class="flex flex-wrap gap-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio"
                                               name="{{ $key }}"
                                               value="{{ $i }}"
                                               required
                                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                               {{ $isReadOnly ? 'disabled' : '' }}
                                               @checked(old($key, optional($evaluation)->{$key}) == $i) >
                                        <span class="ml-2 text-sm text-gray-700">{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error($key)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <!-- Section C: Commitment and Transparency -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        C. Commitment and Transparency
                    </h2>
                    <p class="text-sm text-gray-600 mb-4 italic">
                        Commitment and Transparency refer to the teacher's consistent dedication to supporting
                        student learning, acknowledging learner diversity, and maintaining fairness and openness
                        in academic processes.
                    </p>

                    @php
                        $sectionC = [
                            'c12' => 'Recognizes and values the unique diversity and individual differences among students.',
                            'c13' => 'Assists students with their learning challenges during consultation hours.',
                            'c14' => 'Provides immediate feedback on student outputs and performance.',
                            'c15' => 'Provides transparent and clear criteria in rating student\'s performance.',
                        ];
                    @endphp

                    @foreach($sectionC as $key => $question)
                        <div class="mb-6 pb-6 border-b border-gray-200 last:border-0">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                {{ 12 + $loop->iteration }}. {{ $question }}
                            </label>
                            <div class="flex flex-wrap gap-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio"
                                               name="{{ $key }}"
                                               value="{{ $i }}"
                                               required
                                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                               {{ $isReadOnly ? 'disabled' : '' }}
                                               @checked(old($key, optional($evaluation)->{$key}) == $i) >
                                        <span class="ml-2 text-sm text-gray-700">{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error($key)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <!-- Comments Section -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">
                        Other Comments and Suggestions (Optional)
                    </h2>
                    <textarea name="comment" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $isReadOnly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                        placeholder="Please provide any additional comments or suggestions..."
                        {{ $isReadOnly ? 'disabled' : '' }}>{{ old('comment', optional($evaluation)->comment) }}</textarea>
                    @error('comment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                @if(! $isReadOnly)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-start space-x-3 mb-4">
                            <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                      clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-gray-600">
                                <strong>Important:</strong> Once submitted, you cannot edit your evaluation.
                                Please review your responses carefully before submitting.
                            </p>
                        </div>
                        <button type="button"
                                onclick="showConfirmModal()"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Submit Evaluation
                        </button>
                    </div>
                @else
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-600">
                            You already submitted this evaluation. You can review your responses below; editing is disabled.
                        </p>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Finalize Your Evaluation?</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to submit this evaluation? You can only evaluate this person once
                        for this academic period and cannot make changes after submission.
                    </p>
                    <div class="text-left text-sm text-gray-700 mt-3 space-y-1" id="confirmationSummary">
                        <p><strong>Part A Average:</strong> <span id="avgA">-</span></p>
                        <p><strong>Part B Average:</strong> <span id="avgB">-</span></p>
                        <p><strong>Part C Average:</strong> <span id="avgC">-</span></p>
                        <p><strong>Overall Average:</strong> <span id="avgOverall">-</span></p>
                    </div>
                </div>
                <div class="items-center px-4 py-3 space-y-2">
                    <button onclick="submitForm()"
                            class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Yes, Submit Evaluation
                    </button>
                    <button onclick="hideConfirmModal()"
                            class="px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-md w-full border border-gray-300 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const sectionAKeys = ['a1','a2','a3','a4','a5','a6'];
        const sectionBKeys = ['b7','b8','b9','b10','b11','b12'];
        const sectionCKeys = ['c12','c13','c14','c15'];

        function computeAverages() {
            const getValue = (name) => {
                const selected = document.querySelector(`input[name=\"${name}\"]:checked`);
                return selected ? Number(selected.value) : null;
            };

            const sumSection = (keys) => keys.reduce((sum, key) => {
                const val = getValue(key);
                return val ? sum + val : sum;
            }, 0);

            const countSection = (keys) => keys.filter((key) => getValue(key) !== null).length;

            const sumA = sumSection(sectionAKeys);
            const countA = countSection(sectionAKeys);
            const sumB = sumSection(sectionBKeys);
            const countB = countSection(sectionBKeys);
            const sumC = sumSection(sectionCKeys);
            const countC = countSection(sectionCKeys);

            const avgA = countA ? (sumA / countA).toFixed(2) : '-';
            const avgB = countB ? (sumB / countB).toFixed(2) : '-';
            const avgC = countC ? (sumC / countC).toFixed(2) : '-';

            const totalSum = sumA + sumB + sumC;
            const totalCount = countA + countB + countC;
            const avgOverall = totalCount ? (totalSum / totalCount).toFixed(2) : '-';

            document.getElementById('avgA').textContent = avgA;
            document.getElementById('avgB').textContent = avgB;
            document.getElementById('avgC').textContent = avgC;
            document.getElementById('avgOverall').textContent = avgOverall;
        }

        function showConfirmModal() {
            const form = document.getElementById('evaluationForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            computeAverages();
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function submitForm() {
            document.getElementById('evaluationForm').submit();
        }

        // Close modal when clicking outside
        document.getElementById('confirmModal')?.addEventListener('click', function (e) {
            if (e.target === this) {
                hideConfirmModal();
            }
        });
    </script>
</x-app-layout>
