<div class="bg-white p-4 rounded shadow-sm min-w-full">
    <div class="flex flex-wrap justify-between mb-3 gap-4">
        <div class="flex-auto">
            <p class="text-2xl font-bold"><i class="bi bi-backpack"></i>{{ __('messages.All Test') }}</p>
            <form action="{{ route('summary.export') }}" method="get" target="_blank">
                @csrf
                <input type="hidden" name="filterData" value="{{ json_encode($filterData) }}">
                <button
                    class="p-2.5 ms-2 text-sm font-medium text-white bg-gray-500 rounded-lg border border-blue-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                    <i class="bi bi-file-earmark-pdf"></i> ออกรายงาน
                </button>
            </form>
        </div>
        <div class="flex-auto">
            <form wire:submit.prevent="searchTest">
                <div class="flex flex-wrap flex-md-nowrap gap-2 mb-2">
                    <select id="filterBrn" wire:model="formData.filter_brn" aria-label="Filter brn" required
                        class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                        <option value="" selected>เลือกสาขา</option>
                        @foreach ($branches as $key => $brn)
                            <option value="{{ $key }}">{{ $brn }}</option>
                        @endforeach
                    </select>
                    <select id="filterType" wire:model="formData.filter_type" aria-label="Filter type" required
                        class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                        <option value="" selected>เลือกประเภท</option>
                        @foreach ($course_types as $key => $course_type)
                            <option value="{{ $key }}">{{ $course_type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap flex-md-nowrap gap-2">
                    <select id="quizFilter" wire:model="formData.filter_quiz" aria-label="Filter quiz"
                        class="block w-full p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                        <option value="" selected>แบบทดสอบทั้งหมด</option>
                        @foreach ($quizzes as $quiz)
                            <option value="{{ $quiz->id }}">{{ $quiz->title }}</option>
                        @endforeach
                    </select>
                    <input type="date" id="startDate" wire:model="formData.filter_date"
                        value="{{ $formData['filter_date'] }}" required
                        class="block w-full w-md-5/6 p-2 text-gray-900 border border-gray-300 rounded-lg bg-gray-50 text-xs focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Start date" />
                    {{-- <input type="date" id="endDate" wire:model="formData.filter_edate"
                            class="block w-full p-2 text-gray-900 border border-gray-300 rounded-lg bg-gray-50 text-xs focus:ring-blue-500 focus:border-blue-500"
                            placeholder="End date" /> --}}
                    {{-- @if ($is_loading)
                        <button type="button" disabled
                            class="p-2.5 ms-2 w-full w-md-1/6 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    @else
                        <button type="submit"
                            class="p-2.5 ms-2 w-full w-md-1/6 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                            <i class="bi bi-search"></i> ค้นหา
                        </button>
                    @endif --}}

                    <!-- When loading -->
                    <button type="button" disabled wire:loading wire:target="searchTest"
                        class="p-2.5 ms-2 w-full w-md-1/6 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>

                    <!-- When not loading -->
                    <button type="submit" wire:loading.remove wire:target="searchTest"
                        class="p-2.5 ms-2 w-full w-md-1/6 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>

                </div>
            </form>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table table-hover " id="test-datatable">
            <thead class="table-dark text-start">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"> {{ __('messages.Quiz') }}</th>
                    <th scope="col">{{ __('messages.User') }}</th>
                    <th scope="col">{{ __('messages.Branch') }}</th>
                    <th scope="col">คะแนนที่ดีที่สุด</th>
                    <th scope="col">จำนวนครั้งที่ทำ</th>
                    <th>ค่าเฉลี่ย</th>
                </tr>
            </thead>
            <tbody class="text-start">
                @if (count($tests ?? []) > 0 || count($user_unfound ?? []) > 0)
                    @foreach ($tests as $index => $test)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-nowrap" data-toggle="tooltip" data-placement="top"
                                title="{{ optional($test->getQuiz)->title }}">{{ optional($test->getQuiz)->title }}
                            </td>
                            <td>{{ optional($test->getTester)->name }}</td>
                            <td>{{ optional(optional($test->getTester)->getBrn)->name ?? "-" }}</td>
                            <td>{{ $test->best_score }}</td>
                            <td>{{ $test->times_tested }}</td>
                            <td>{{ $test->average_score }}</td>
                        </tr>
                    @endforeach
                    @foreach ($user_untest as $index => $user_name)
                        <tr>
                            <td>{{ count($tests ?? []) + $index + 1 }}</td>
                            <td class="text-nowrap">-ไม่พบ-</td>
                            <td>{{ $user_name }}</td>
                            <td>{{ $branches[$formData['filter_brn']] ?? "-" }}</td>
                            <td colspan="3" class="text-center text-danger">-ไม่พบประวัติการทำแบบทดสอบ-</td>
                        </tr>
                    @endforeach
                    @foreach ($user_unfound as $index => $user)
                        <tr>
                            <td>{{ count($tests ?? []) + count($user_untest ?? []) + $index + 1 }}</td>
                            <td class="text-nowrap">-ไม่พบ-</td>
                            <td>{{ $user['std_name'] . " " . $user['std_last_name'] }}</td>
                            <td>{{ $branches[$formData['filter_brn']] ?? "-" }}</td>
                            <td colspan="3" class="text-center text-danger">-ไม่พบประวัติการใช้งานระบบ-</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center"><span
                                class="bg-pink-100 text-pink-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-pink-400">ไม่พบข้อมูล</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
        {{-- {{ $tests->links() }} --}}
    </div>
</div>
