<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="text-center mt-5 px-2">
            <p class="fs-1 fw-bold">{{ __('messages.Dashboard') }}</p>
            {{-- @php
                $seriesList = [];
                $dpmList= [];
                foreach ($dpms as $key => $dpm) {
                    $seriesList[] = App\Models\course::where('dpm', $dpm->id)->count();
                    $dpmList[] = $dpm->name;
                };

                $totaltest = count($tests ?? []);
                $passtest = 0;
                foreach ($tests as $key => $test) {
                    $passScore = ($test->totalScore) * (optional($test->getQuiz)->pass_score) / 100;
                    if ($test->score >= $passScore) {
                        $passtest++;
                    }
                }
                $testData = [$passtest, $totaltest - $passtest]

            @endphp --}}

            {{-- Tabs Bar --}}
            <div class="border-b border-gray-200 mb-4">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                    {{-- @can('dCourse')
                        <li class="me-2">
                            <a href="#course" id="course-tab" data-tabs-target="#course" type="button" role="tab" aria-controls="course" aria-selected="false" class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300  group">
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 20">
                                    <path d="M16 14V2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v15a3 3 0 0 0 3 3h12a1 1 0 0 0 0-2h-1v-2a2 2 0 0 0 2-2ZM4 2h2v12H4V2Zm8 16H3a1 1 0 0 1 0-2h9v2Z"/>
                                </svg>
                                {{ __('messages.Course') }}
                            </a>
                        </li>
                    @endcan
                    @can('dQuiz')
                        <li class="me-2">
                            <a href="#test" id="test-tab" data-tabs-target="#test" type="button" role="tab" aria-controls="test" aria-selected="false" class="inline-flex items-center justify-center p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active group" aria-current="page">
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16">
                                    <path d="M18 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2ZM5 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm0-3a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm0-3a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm10 6H9a1 1 0 0 1 0-2h6a1 1 0 0 1 0 2Zm0-3H9a1 1 0 0 1 0-2h6a1 1 0 1 1 0 2Zm0-3H9a1 1 0 0 1 0-2h6a1 1 0 1 1 0 2Z"/>
                                </svg>
                                {{ __('messages.Test') }}
                            </a>
                        </li>
                    @endcan --}}
                    @can('dLog')
                        <li class="me-2">
                            <a href="#log" id="log-tab" data-tabs-target="#log" type="button" role="tab" aria-controls="log" aria-selected="false" class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300  group">
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 15">
                                    <path d="M1 13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6H1v7Zm5.293-3.707a1 1 0 0 1 1.414 0L8 9.586V8a1 1 0 0 1 2 0v1.586l.293-.293a1 1 0 0 1 1.414 1.414l-2 2a1 1 0 0 1-1.416 0l-2-2a1 1 0 0 1 .002-1.414ZM17 0H1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1Z"/>
                                </svg>
                                {{ __('messages.Log') }}
                            </a>
                        </li>
                    @endcan
                    @can('dHistory')
                        <li class="me-2">
                            <a href="#history" id="history-tab" data-tabs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false" class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300  group">
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
                                </svg>
                                {{ __('messages.History') }}
                            </a>
                        </li>
                    @endcan
                    @hasanyrole(['admin', 'superAdmin'])
                        <li class="me-2">
                            <a href="#manage" id="manage-tab" data-tabs-target="#manage" type="button" role="tab" aria-controls="manage" aria-selected="false" class="inline-flex items-center justify-center p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300  group">
                                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M18 7.5h-.423l-.452-1.09.3-.3a1.5 1.5 0 0 0 0-2.121L16.01 2.575a1.5 1.5 0 0 0-2.121 0l-.3.3-1.089-.452V2A1.5 1.5 0 0 0 11 .5H9A1.5 1.5 0 0 0 7.5 2v.423l-1.09.452-.3-.3a1.5 1.5 0 0 0-2.121 0L2.576 3.99a1.5 1.5 0 0 0 0 2.121l.3.3L2.423 7.5H2A1.5 1.5 0 0 0 .5 9v2A1.5 1.5 0 0 0 2 12.5h.423l.452 1.09-.3.3a1.5 1.5 0 0 0 0 2.121l1.415 1.413a1.5 1.5 0 0 0 2.121 0l.3-.3 1.09.452V18A1.5 1.5 0 0 0 9 19.5h2a1.5 1.5 0 0 0 1.5-1.5v-.423l1.09-.452.3.3a1.5 1.5 0 0 0 2.121 0l1.415-1.414a1.5 1.5 0 0 0 0-2.121l-.3-.3.452-1.09H18a1.5 1.5 0 0 0 1.5-1.5V9A1.5 1.5 0 0 0 18 7.5Zm-8 6a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"/>
                                </svg>
                                {{ __('messages.Manage') }}
                            </a>
                        </li>
                    @endhasanyrole
                </ul>
            </div>
            {{-- End Tabs Bar --}}

            {{-- Tabs Contents --}}
            <div id="default-tab-content">

                {{-- course Content --}}
                {{-- <div class="hidden p-2 rounded-lg bg-gray-50" id="course" role="tabpanel" aria-labelledby="course-tab">
                    <div class="py-2">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <div class="sm:rounded-lg p-2">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-3">
                                        <p class="text-2xl font-bold"><i class="bi bi-journal-bookmark"></i>{{ __('messages.All Course') }}</p>
                                    </div>
                                    <canvas id="myChart"></canvas>
                                </div>
                                <div class="bg-white p-4 rounded shadow-sm min-w-full">
                                    <div class="flex flex-wrap justify-between mb-3">
                                        <p class="text-2xl font-bold"><i class="bi bi-backpack"></i>{{ __('messages.All Course') }}</p>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="table table-hover" id="allcourse-datatable">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">{{ __('messages.Code') }}</th>
                                                    <th scope="col" >{{ __('messages.Name') }}</th>
                                                    <th scope="col">{{ __('messages.Lecturer') }}</th>
                                                    <th scope="col">{{ __('messages.Dpm') }}</th>
                                                    <th scope="col">{{ __('messages.student') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-start">
                                                @foreach ($courses as $course)
                                                    @php
                                                        $total_student = App\Models\user_has_course::where('course_id', $course->id)->count();
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $course->code }}</td>
                                                        <td class="text-nowrap" data-toggle="tooltip" data-placement="top" title="{{ $course->title }}">{{ $course->title }}</td>
                                                        <td>{{ optional($course->getTeacher)->name }}</td>
                                                        <td>{{ optional($course->getDpm)->name }}</td>
                                                        <td>{{ $total_student }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                {{-- test content --}}
                {{-- <div class="hidden p-4 rounded-lg bg-gray-50" id="test" role="tabpanel" aria-labelledby="test-tab">
                    <div class="py-2">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <div class="sm:rounded-lg p-2">
                                <div class="mb-4">
                                    <div class="flex justify-between mb-3">
                                        <p class="text-2xl font-bold"><i class="bi bi-file-earmark-check"></i>{{ __('messages.Test Summary') }}</p>
                                    </div>
                                    <div  class="flex justify-center items-center flex-wrap">
                                        <div class=" md:h-80 w-1/2">
                                            <canvas id="testChart"></canvas>
                                        </div>
                                        <div>
                                            <p class="text-xl">{{ __('messages.Average') }}</p>
                                            <p class="text-5xl sm:text-7xl md:text-9xl">{{ intval( ($totaltest != 0) ? ($passtest * 100 /  $totaltest) : 0 ) }}%</p>
                                            <p class="text-xl">{{ __('messages.Pass from') }}</p>
                                            <p class="text-xl">{{ __('messages.Total') }} <b>{{$totaltest}}</b> {{ __('messages.times') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-2 rounded shadow-sm min-w-full">
                                    <div class="flex justify-between mb-3">
                                        <p class="text-2xl font-bold"><i class="bi bi-list-check"></i> {{ __('messages.All Test') }}</p>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="table table-hover " id="test-datatable">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col"> {{ __('messages.Quiz') }}</th>
                                                    <th scope="col">{{ __('messages.User') }}</th>
                                                    <th scope="col">{{ __('messages.Score') }}</th>
                                                    <th scope="col">{{ __('messages.Date') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-start">
                                                @foreach ($tests as $index => $test)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td class="text-nowrap" data-toggle="tooltip" data-placement="top" title="{{ optional($test->getQuiz)->title }}">{{ optional($test->getQuiz)->title }}</td>
                                                        <td>{{ optional($test->getTester)->name }}</td>
                                                        <td>{{ $test->score }} / {{ $test->totalScore }}</td>
                                                        <td>{{ Carbon\Carbon::parse($test->start)->format('d-m-Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                {{-- log content --}}
                <div class="hidden p-4 rounded-lg bg-gray-50 " id="log" role="tabpanel" aria-labelledby="log-tab">
                    <div class="relative overflow-x-auto sm:rounded-lg">
                        <table class="w-full table-hover text-sm text-left rtl:text-right text-gray-500" id="log-table">
                            <thead class="text-xs text-white uppercase bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.User') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Module') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Action') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Note') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Date') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activitys as $index => $log)
                                    <tr class="bg-white">
                                        <th scope="row" class="px-6 py-2 font-medium text-gray-900 whitespace-nowrap">
                                            {{$index + 1}}
                                        </th>
                                        <td class="px-6 py-2">
                                            {{ optional($log->getUser)->name }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ $log->module }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ $log->note }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ $log->content }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ $log->created_at }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- history content --}}
                <div class="hidden p-4 rounded-lg " id="history" role="tabpanel" aria-labelledby="history-tab">
                    {{-- Course Deleted --}}
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-20 bg-gray-50">
                        <p class="font-bold text-2xl my-2">{{ __('messages.Course Deleted') }}</p>
                        <table class="w-full table-hover text-sm text-left rtl:text-right text-gray-500" id="courseDel-table">
                            <thead class="text-xs text-white uppercase bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Title') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Teacher') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Dpm') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Delete_at') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courseDel as $index => $course)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <th scope="row" class="px-6 py-2 font-medium text-gray-900 whitespace-nowrap">
                                            {{$index + 1}}
                                        </th>
                                        <td class="px-6 py-2 text-nowrap" data-toggle="tooltip" data-placement="top" title="{{ $course->title }}">
                                            {{ $course->code }} : {{ $course->title }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ optional($course->getTeacher)->name }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ optional($course->getDpm)->name }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ Carbon\Carbon::parse($course->deleted_at)->format('d-m-Y') }}
                                        </td>
                                        <td class="">
                                            <button type="button" data-res-id="{{$course->id}}" data-res-type="course" class="restoreBtn text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-2 py-2 me-2 my-2 focus:outline-none">
                                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v5h-5M2 19v-5h5m10-4a8 8 0 0 1-14.947 3.97M1 10a8 8 0 0 1 14.947-3.97"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Quiz Deleted --}}
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg bg-gray-50">
                        <p class="font-bold text-2xl my-2">{{ __('messages.Quiz Deleted') }}</p>
                        <table class="w-full table-hover text-sm text-left rtl:text-right text-gray-500" id="quizDel-table">
                            <thead class="text-xs text-white uppercase bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        {{ __('messages.Title') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Create_By') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Pass Score') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Delete_at') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        {{ __('messages.Action') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quizDel as $index => $quiz)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <th scope="row" class="px-6 py-2 font-medium text-gray-900 whitespace-nowrap">
                                            {{$index + 1}}
                                        </th>
                                        <td class="px-6 py-2 text-nowrap" data-toggle="tooltip" data-placement="top" title="{{ $quiz->title }}">
                                            {{ $quiz->title }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ optional($quiz->getCreated)->name }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ $quiz->pass_score }}
                                        </td>
                                        <td class="px-6 py-2">
                                            {{ Carbon\Carbon::parse($quiz->deleted_at)->format('d-m-Y') }}
                                        </td>
                                        <td class="">
                                            <button type="button" data-res-id="{{$quiz->id}}" data-res-type="quiz" class="restoreBtn text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-2 py-2 me-2 my-2 focus:outline-none">
                                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 1v5h-5M2 19v-5h5m10-4a8 8 0 0 1-14.947 3.97M1 10a8 8 0 0 1 14.947-3.97"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Manage content --}}
                <div class="hidden p-4 rounded-lg " id="manage" role="tabpanel" aria-labelledby="manage-tab">
                    <div class="text-center mt-2">
                        <p class="fs-1 fw-bold">{{ __('messages.Agency Data') }}</p>
                    </div>
                    <div class="py-10">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <div class="sm:rounded-lg p-4 row justify-center">
                                <div class="p-4 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div class="">
                                        <div class="bg-white p-4 shadow rounded-lg">
                                            <div class="flex justify-between mb-3">
                                                <p class="text-2xl font-bold"><i class="bi bi-buildings"></i> {{ __('messages.Agency') }}</p>
                                                @hasrole('superAdmin')
                                                    <button class="btn btn-success" onclick="addAgn()" ><i class="bi bi-plus-lg"></i></button>
                                                @endhasrole
                                            </div>
                                            <div class="overflow-auto">
                                                <table class="table table-hover">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th scope="col">{{ __('messages.Action') }}</th>
                                                            <th scope="col">Logo</th>
                                                            <th scope="col">{{ __('messages.Name') }}</th>
                                                            <th scope="col">{{ __('messages.Address') }}</th>
                                                            <th scope="col">{{ __('messages.Contact') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody >
                                                        @foreach ($agns as $agn)
                                                            <tr>
                                                                <td>
                                                                    <div class="flex gap-1">
                                                                        @hasrole('superAdmin')
                                                                            <button class="btn btn-sm btn-danger" id="delBtn" deltype="agn" value="{{ $agn->id }}"><i class="bi bi-trash"></i></button>
                                                                            <button
                                                                                class="btn btn-sm btn-info text-white"
                                                                                id="editBtn"
                                                                                edittype="agn"
                                                                                value="{{ $agn->id }}"
                                                                                agnName="{{$agn->name}}"
                                                                                agnAddr="{{$agn->address ?? ''}}"
                                                                                agnCont="{{$agn->contact ?? ''}}">
                                                                                    <i class="bi bi-gear"></i>
                                                                            </button>
                                                                        @endhasrole
                                                                    </div>
                                                                </td>
                                                                <td class="text-nowrap">
                                                                    <img src="/uploads/logo/{{ $agn->logo ?? '' }}" alt="" width="25">
                                                                </td>
                                                                <td class="text-nowrap">{{$agn->name}}</td>
                                                                <td class="text-nowrap">{{$agn->address?? ""}}</td>
                                                                <td class="text-nowrap">{{$agn->contact?? ""}}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="bg-white p-4 shadow rounded-lg">
                                            <div class="flex justify-between mb-3">
                                                <p class="text-2xl font-bold"><i class="bi bi-building"></i> {{ __('messages.Branch') }}</p>
                                                <button class="btn btn-success" onclick="addBrn()"><i class="bi bi-plus-lg"></i></button>
                                            </div>
                                            <div class="overflow-auto">
                                                <table class="table table-hover">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th scope="col">{{ __('messages.Action') }}</th>
                                                            <th scope="col">{{ __('messages.Name') }}</th>
                                                            <th scope="col">{{ __('messages.Agency') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody >
                                                        @foreach ($brns as $brn)
                                                            <tr>
                                                                <td>
                                                                    <div class="flex gap-1">
                                                                        <button class="btn btn-sm btn-danger" id="delBtn" deltype="brn" value="{{ $brn->id }}"><i class="bi bi-trash"></i></button>
                                                                        <button
                                                                            class="btn btn-sm btn-info text-white"
                                                                            id="editBtn"
                                                                            edittype="brn"
                                                                            brnName="{{$brn->name}}"
                                                                            brnAgn="{{$brn->agency}}"
                                                                            value="{{ $brn->id }}">
                                                                                <i class="bi bi-gear"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                                <td class="text-nowrap">{{$brn->name}}</td>
                                                                <td class="text-nowrap">{{ optional($brn->agencyName)->name }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-4 rounded-lg mb-5 shadow">
                                    <div class="flex justify-between mb-3">
                                        <p class="text-2xl font-bold"><i class="bi bi-house-door"></i> {{ __('messages.Department') }}</p>
                                        <button class="btn btn-success" onclick="addDpm()"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <div class="overflow-auto">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">{{ __('messages.Action') }}</th>
                                                    <th scope="col">{{ __('messages.Name') }}</th>
                                                    <th scope="col">{{ __('messages.Prefix') }}</th>
                                                    <th scope="col">{{ __('messages.Personnel') }}</th>
                                                    <th scope="col">{{ __('messages.Agency') }}</th>
                                                    <th scope="col">{{ __('messages.Branch') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody >
                                                @foreach ($dpms as $dpm)
                                                    <tr>
                                                        <td>
                                                            <div class="flex gap-1">
                                                                <button class="btn btn-sm btn-danger" id="delBtn" deltype="dpm" value="{{ $dpm->id }}"><i class="bi bi-trash"></i></button>
                                                                <button class="btn btn-sm btn-info text-white"
                                                                    id="editBtn"
                                                                    edittype="dpm"
                                                                    dpmName="{{$dpm->name}}"
                                                                    dpmPrefix="{{$dpm->prefix}}"
                                                                    dpmBrn="{{$dpm->branch}}"
                                                                    value="{{ $dpm->id }}">
                                                                        <i class="bi bi-gear"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td>{{$dpm->name}}</td>
                                                        <td>{{$dpm->prefix}}</td>
                                                            @php
                                                                $numPers = App\Models\User::where('dpm', $dpm->id)->count();
                                                            @endphp
                                                        <td>{{$numPers}}</td>
                                                        <td>{{ optional(optional($dpm->brnName)->agencyName)->name }}</td>
                                                        <td>{{optional($dpm->brnName)->name}}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="w-4/5 h-0.5 mx-auto border-0 rounded bg-gray-700">

                    <div class="text-center mt-5">
                        <p class="fs-1 fw-bold">{{ __('messages.Roles') }}</p>
                    </div>
                    <div class="py-10">
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                            <div class="sm:rounded-lg p-4 row justify-center">
                                <div class="p-4 rounded-lg row">
                                    <div class="bg-white p-4 shadow rounded-lg">
                                        <div class="flex justify-between mb-3">
                                            <p class="text-2xl font-bold"><i class="bi bi-person-square"></i> {{ __('messages.Roles') }}</p>
                                            <button class="btn btn-success" onclick="addRole()" ><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                        <div class="overflow-auto">
                                            <table class="table table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">{{ __('messages.Name') }}</th>
                                                        <th scope="col">{{ __('messages.Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody >
                                                    @foreach ($roles as $index => $role)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td class="text-nowrap">{{$role->name}}</td>
                                                            <td>
                                                                @role('superAdmin')
                                                                    <button class="btn btn-sm btn-primary" id="editBtn" edittype="role" value="{{ $role->name }}"><i class="bi bi-gear"></i></button>
                                                                    <button class="btn btn-sm btn-danger" id="delBtn" deltype="role" value="{{ $role->name }}"><i class="bi bi-trash"></i></button>
                                                                @endrole
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @role('superAdmin')
                        <hr class="w-4/5 h-0.5 mx-auto border-0 rounded bg-gray-700">

                        <div class="text-center mt-5">
                            <p class="fs-1 fw-bold">{{ __('messages.Permissions') }}</p>
                        </div>
                        <div class="py-10">
                            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                                <div class="sm:rounded-lg p-4 row justify-center">
                                    <div class="rounded-lg row gap-2">
                                        @foreach ($roles as $index => $role)
                                            <div class="p-4 bg-white shadow rounded-lg">
                                                <h3 class="mb-4 text-start font-semibold text-xl text-gray-900">{{ $role->name }}</h3>
                                                <ul class="items-center w-full text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                                    @foreach ($permissions as $perm)
                                                        <li class="px-3 border border-gray-200 sm:border-b-0 sm:border-r">
                                                            <div class="flex items-center">
                                                                <input id="{{ $role->name }}-check-{{ $perm->name }}" type="checkbox" name="{{ $perm->name }}" value="{{ $role->name }}" class="permission-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" {{ $role->hasPermissionTo($perm->name) ? 'checked' : '' }}>
                                                                <label for="{{ $role->name }}-check-{{ $perm->name }}" class="w-full py-3 ms-2 text-sm font-medium text-gray-900 ">{{ __('messages.perm-' . $perm->name) }}</label>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endrole
                </div>
            </div>
            {{-- End Tabs Contents --}}
        </div>
    </div>
</x-app-layout>
<script src="https://fastly.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('myChart');
    const testch = document.getElementById('testChart');
    const seriesList = @json($seriesList); // Converts the PHP array to a JSON string that JavaScript can read
    const dpmList = @json($dpmList);

    const testData = @json($testData);

    // new Chart(testch, {
    //     type: 'doughnut',
    //     data: {
    //         labels: [
    //             'PASS',
    //             'FAIL'
    //         ],
    //         datasets: [{
    //             label: 'Amount',
    //             data: testData,
    //             backgroundColor: [
    //                 '#4ade80',
    //                 'rgb(255, 99, 132)'
    //             ],
    //             hoverOffset: 4
    //         }]
    //     },
    //     options: {
    //         responsive: true,
    //         plugins: {
    //             legend: {
    //                 position: 'bottom',
    //             },
    //         }
    //     },
    // });

    // new Chart(ctx, {
    //     type: 'bar',
    //     data: {
    //         labels: dpmList,
    //         datasets: [{
    //             label: 'course amount',
    //             data: seriesList,
    //             borderWidth: 1,
    //             backgroundColor: [
    //                 'rgba(255, 99, 132, 0.2)',
    //                 'rgba(255, 159, 64, 0.2)',
    //                 'rgba(255, 205, 86, 0.2)',
    //                 'rgba(75, 192, 192, 0.2)',
    //                 'rgba(54, 162, 235, 0.2)',
    //                 'rgba(153, 102, 255, 0.2)',
    //                 'rgba(201, 203, 207, 0.2)'
    //             ],
    //             borderColor: [
    //                 'rgb(255, 99, 132)',
    //                 'rgb(255, 159, 64)',
    //                 'rgb(255, 205, 86)',
    //                 'rgb(75, 192, 192)',
    //                 'rgb(54, 162, 235)',
    //                 'rgb(153, 102, 255)',
    //                 'rgb(201, 203, 207)'
    //             ],
    //         }]
    //     },
    //     options: {
    //         scales: {
    //             y: {
    //                 beginAtZero: true
    //             }
    //         },
    //         responsive: true,
    //         plugins: {
    //             legend: {
    //                 position: 'bottom',
    //             },
    //         }
    //     }
    // });

    $(document).ready(function() {
        var currentDate = new Date();
        var dateString = currentDate.getDate() + "/"
                        + (currentDate.getMonth()+1)  + "/"
                        + currentDate.getFullYear() + " "
                        + currentDate.getHours() + ":"
                        + currentDate.getMinutes() + ":"
                        + currentDate.getSeconds();
        var url = new URL(window.location.href);

        $('#allcourse-datatable').DataTable({
            paging: true,       // Enables pagination
            searching: true,    // Enables the search box
            ordering: true,     // Enables column ordering
            info: true,         // 'Showing x to y of z entries' string
            lengthChange: true, // Allows the user to change number of rows shown
            pageLength: 10,      // Set number of rows per page
            dom: 'Blfrtip',
            buttons: [
                'excel',
                {
                    extend: 'print',
                    autoPrint: true,
                    title: 'ศูนย์ฝึกอบรมเทรนนิ่งเซ็นเตอร์',
                    messageTop: 'รายงานหลักสูตร',
                    messageBottom: 'Printed on ' + url.origin + ' by {{auth()->user()->name}} at ' + dateString,
                    customize: function (doc) {
                        // Prepend an image to the title (doc.title is empty so we prepend to doc.content[1].table)
                        var imgContainer = $('<div/>').css({
                            'text-align': 'center',
                            'margin-bottom': '10px' // Space below the image
                        });
                        var img = new Image();
                    img.src = 'data:image/png;base64,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';
                        $(img).css({
                            'margin': '0 auto', // Center the image within the div
                            'display': 'block', // Ensure the image is a block-level element
                            'width': '50px',    // Set your desired width
                            'height': 'auto'    // Maintain aspect ratio
                        });

                        imgContainer.append(img);

                        $(doc.document.body)
                            .prepend(imgContainer);  // Adjust this if the image needs to go somewhere specific

                        // Apply your styles to the title and message
                        $(doc.document.body).find('h1').first().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'font-size': '20px',
                            'margin-bottom': '5px'
                        });
                        $(doc.document.body).find('h1').next().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'margin-bottom': '10px'
                        });

                        // Apply styles to the footer
                        $(doc.document.body).find('div').last().css({
                            'text-align': 'end',
                            'font-size': '10px'
                        });
                    }
                },
            ]
        });
    });

    $(document).ready(function() {
        var currentDate = new Date();
        var dateString = currentDate.getDate() + "/"
                        + (currentDate.getMonth()+1)  + "/"
                        + currentDate.getFullYear() + " "
                        + currentDate.getHours() + ":"
                        + currentDate.getMinutes() + ":"
                        + currentDate.getSeconds();
        var url = new URL(window.location.href);

        $('#test-datatable').DataTable({
            paging: true,       // Enables pagination
            searching: true,    // Enables the search box
            ordering: true,     // Enables column ordering
            info: true,         // 'Showing x to y of z entries' string
            lengthChange: true, // Allows the user to change number of rows shown
            pageLength: 5,      // Set number of rows per page
            dom: 'Blfrtip',
            buttons: [
                'excel',
                {
                    extend: 'print',
                    autoPrint: true,
                    title: 'ศูนย์ฝึกอบรมเทรนนิ่งเซ็นเตอร์',
                    messageTop: 'รายงานการทำแบบทดสอบ',
                    messageBottom: 'Printed on ' + url.origin + ' by {{auth()->user()->name}} at ' + dateString,
                    customize: function (doc) {
                        // Prepend an image to the title (doc.title is empty so we prepend to doc.content[1].table)
                        var imgContainer = $('<div/>').css({
                            'text-align': 'center',
                            'margin-bottom': '10px' // Space below the image
                        });
                        var img = new Image();
                        img.src = 'data:image/png;base64,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';
                        $(img).css({
                            'margin': '0 auto', // Center the image within the div
                            'display': 'block', // Ensure the image is a block-level element
                            'width': '50px',    // Set your desired width
                            'height': 'auto'    // Maintain aspect ratio
                        });

                        imgContainer.append(img);

                        $(doc.document.body)
                            .prepend(imgContainer);  // Adjust this if the image needs to go somewhere specific

                        // Apply your styles to the title and message
                        $(doc.document.body).find('h1').first().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'font-size': '20px',
                            'margin-bottom': '5px'
                        });
                        $(doc.document.body).find('h1').next().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'margin-bottom': '10px'
                        });

                        // Apply styles to the footer
                        $(doc.document.body).find('div').last().css({
                            'text-align': 'end',
                            'font-size': '10px'
                        });
                    }
                },
            ]
        });
    });

    $(document).ready(function() {
        var currentDate = new Date();
        var dateString = currentDate.getDate() + "/"
                        + (currentDate.getMonth()+1)  + "/"
                        + currentDate.getFullYear() + " "
                        + currentDate.getHours() + ":"
                        + currentDate.getMinutes() + ":"
                        + currentDate.getSeconds();
        var url = new URL(window.location.href);

        $('#log-table').DataTable({
            paging: true,       // Enables pagination
            searching: true,    // Enables the search box
            ordering: true,     // Enables column ordering
            info: true,         // 'Showing x to y of z entries' string
            lengthChange: true, // Allows the user to change number of rows shown
            pageLength: 10,      // Set number of rows per page
            dom: 'Blfrtip',
            buttons: [
                'excel',
                {
                    extend: 'print',
                    autoPrint: true,
                    title: 'ศูนย์ฝึกอบรมเทรนนิ่งเซ็นเตอร์',
                    messageTop: 'รายงานประวัติการเข้าใช้ระบบ',
                    messageBottom: 'Printed on ' + url.origin + ' by {{auth()->user()->name}} at ' + dateString,
                    customize: function (doc) {
                        // Prepend an image to the title (doc.title is empty so we prepend to doc.content[1].table)
                        var imgContainer = $('<div/>').css({
                            'text-align': 'center',
                            'margin-bottom': '10px' // Space below the image
                        });
                        var img = new Image();
                        img.src = 'data:image/png;base64,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';
                        $(img).css({
                            'margin': '0 auto', // Center the image within the div
                            'display': 'block', // Ensure the image is a block-level element
                            'width': '50px',    // Set your desired width
                            'height': 'auto'    // Maintain aspect ratio
                        });

                        imgContainer.append(img);

                        $(doc.document.body)
                            .prepend(imgContainer);  // Adjust this if the image needs to go somewhere specific

                        // Apply your styles to the title and message
                        $(doc.document.body).find('h1').first().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'font-size': '20px',
                            'margin-bottom': '5px'
                        });
                        $(doc.document.body).find('h1').next().css({
                            'text-align': 'center',
                            'font-weight': 'bold',
                            'margin-bottom': '10px'
                        });

                        // Apply styles to the footer
                        $(doc.document.body).find('div').last().css({
                            'text-align': 'end',
                            'font-size': '10px'
                        });
                    }
                },
            ]
        });
    });

    $(document).ready(function() {
        $('#courseDel-table').DataTable({
            paging: true,       // Enables pagination
            searching: true,    // Enables the search box
            ordering: true,     // Enables column ordering
            info: true,         // 'Showing x to y of z entries' string
            lengthChange: true, // Allows the user to change number of rows shown
            pageLength: 10,      // Set number of rows per page
        });
    });

    $(document).ready(function() {
        $('#quizDel-table').DataTable({
            paging: true,       // Enables pagination
            searching: true,    // Enables the search box
            ordering: true,     // Enables column ordering
            info: true,         // 'Showing x to y of z entries' string
            lengthChange: true, // Allows the user to change number of rows shown
            pageLength: 10,      // Set number of rows per page
        });
    });


    document.addEventListener('DOMContentLoaded', function () {
        $(document).ready(function() {
            $('.restoreBtn').click(function() {
                // Get the notification ID from the data attribute
                const resId = $(this).data('res-id');
                const resType = $(this).data('res-type');
                Swal.fire({
                    title: `Are you sure?`,
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!',
                    showLoaderOnConfirm: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/restore/${resType}/${resId}`, // You need to define this route in your web.php
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                // You can add some code here to handle a successful response
                                if (response.success) {
                                    Swal.fire(
                                        'Success!',
                                        `${response.success} has been restore.`,
                                        'success'
                                    ).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.reload()
                                        }
                                    });
                                    console.log("success: ",response.success);
                                } else {
                                    Swal.fire(
                                        'Sorry!',
                                        `${response.success} has not restore.`,
                                        'error'
                                    );
                                    console.log("error: ",response.error);
                                };
                                // window.location.reload()
                            },
                            error: function(error) {
                                // You can add some error handling here
                                Swal.fire(
                                    'Sorry!',
                                    'Data has not restore.',
                                    'error'
                                )
                                console.log("error: ",error);
                            }
                        });
                    }
                })

            });
        });
    });

    addAgn = () => {
        Swal.fire({
            title: '{{ __('messages.Add Agency') }}',
            html: `
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('messages.Name') }}</label>
                    <input type="text" class="form-control" id="name">
                </div>
                <div class="mb-3">
                    <label for="addr" class="form-label">{{ __('messages.Address') }}</label>
                    <input type="text" class="form-control" id="addr">
                </div>
                <div class="mb-3">
                    <label for="cont" class="form-label">{{ __('messages.Contact') }}</label>
                    <input type="text" class="form-control" id="cont">
                </div>
                <div class="flex items-center justify-center w-full mt-2">
                    <label for="agn-file" class="flex flex-col items-center justify-center w-full h-30 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50   hover:bg-gray-100   ">
                        <div class="flex flex-col items-center justify-center pt-2 pb-2">
                            <p class="mb-2 font-bold">รูปภาพlogo</p>
                            <p class="text-sm text-gray-500 "><span class="font-semibold">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500 ">jpeg,png  (MAX 5Mb size)</p>
                        </div>
                        <input id="agn-file" type="file" class="hidden" />
                    </label>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "{{ __('messages.Save') }}",
            preConfirm: () => {
                const agnname = document.getElementById('name').value;
                const addr = document.getElementById('addr').value;
                const cont = document.getElementById('cont').value;
                const agnfile = document.getElementById('agn-file').files[0];

                if (!agnname) {
                    Swal.showValidationMessage("Name is required");
                    return;
                }

                const formData = new FormData();
                formData.append('name', agnname);
                formData.append('address', addr);
                formData.append('contact', cont);
                formData.append('logo', agnfile);
                formData.append('_token', '{{ csrf_token() }}');

                return fetch('/manage/addAgency', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed`
                    )
                })
            }
        }).then((result) => {
            if (result.isConfirmed) {
                console.log(result.value);
                Swal.fire(
                    'Saved!',
                    'Your data was saved successfully.',
                    'success'
                ).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload()
                    }
                });
            }
        });
    }

    addBrn = () => {
        Swal.fire({
            title: '{{ __('messages.Add Branch') }}',
            html: `
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('messages.Name') }}</label>
                    <input type="text" class="form-control" id="name">
                </div>

                <label for="agn" class="form-label">{{ __('messages.Agency') }}</label>
                <select class="form-select" aria-label="Default select example" id="agn">
                    @foreach ($agns as $agn)
                        <option value="{{$agn->id}}">{{$agn->name}}</option>
                    @endforeach
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: "{{ __('messages.Save') }}",
            preConfirm: () => {
                const brnname = document.getElementById('name').value;
                const agn = document.getElementById('agn').value;

                if (!brnname) {
                    Swal.showValidationMessage("Name is required");
                    return;
                }

                return fetch('/manage/addBranch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: brnname, agency:agn })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed`
                    )
                })
            }
        }).then((result) => {
            if (result.isConfirmed) {
                console.log(result.value);
                Swal.fire(
                    'Saved!',
                    'Your data was saved successfully.',
                    'success'
                ).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload()
                    }
                });
            }
        });
    }

    addRole = () => {
        Swal.fire({
            title: '{{ __('messages.Add Role') }}',
            html: `
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('messages.Name') }}</label>
                    <input type="text" class="form-control" id="name">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "{{ __('messages.Save') }}",
            preConfirm: () => {
                const rname = document.getElementById('name').value;

                if (!rname) {
                    Swal.showValidationMessage("Name is required");
                    return;
                }

                return fetch('/manage/addRole', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: rname })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed`
                    )
                })
            }
        }).then((result) => {
            if (result.isConfirmed) {
                console.log(result.value);
                Swal.fire(
                    'Saved!',
                    'Your data was saved successfully.',
                    'success'
                ).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload()
                    }
                });
            }
        });
    }

    addDpm = () => {
        Swal.fire({
            title: '{{ __('messages.Add Department') }}',
            html: `
                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('messages.Name') }}</label>
                    <input type="text" class="form-control" id="name">
                </div>

                <div class="mb-3">
                    <label for="prefix" class="form-label">{{ __('messages.Prefix') }}</label>
                    <input type="text" class="form-control" id="prefix">
                </div>

                <label for="brn" class="form-label">{{ __('messages.Branch') }}</label>
                <select class="form-select" aria-label="Default select example" id="brn">
                    @foreach ($brns as $brn)
                        <option value="{{$brn->id}}">{{$brn->name}}</option>
                    @endforeach
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __('messages.Save') }}',
            showLoaderOnConfirm: true,
            preConfirm: (name) => {
                const dpmname = document.getElementById('name').value;
                const prefix = document.getElementById('prefix').value;
                const brn = document.getElementById('brn').value;

                if (!dpmname || !prefix ) {
                    Swal.showValidationMessage("Name or prefix is required");
                    return;
                }

                return fetch('/manage/addDpm', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: dpmname, branch:brn, prefix:prefix })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed`
                    )
                })
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                console.log(result.value);
                Swal.fire(
                    'Saved!',
                    'Your department was added successfully.',
                    'success'
                ).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload()
                    }
                });
            }
        })

    }


    const delBtns = document.querySelectorAll('#delBtn');
    delBtns.forEach((delbtn) => {
        delbtn.addEventListener('click', () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const delId = delbtn.value;
                    const delType = delbtn.getAttribute('deltype');

                    return fetch('/manage/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ delid: delId, type:delType })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText)
                        }
                        return response.json()
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed`
                        )
                    })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log(result.value);
                    Swal.fire(
                    'Deleted!',
                    'Your data has been deleted.',
                    'success'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload()
                        }
                    });
                }
            })
        })
    })

    const editBtns = document.querySelectorAll('#editBtn');
    editBtns.forEach((editbtn) => {
        const eid = editbtn.value;
        const etype = editbtn.getAttribute('edittype');

        if (etype == 'agn') {
            const ename = editbtn.getAttribute('agnName');
            const eaddr = editbtn.getAttribute('agnAddr');
            const econt = editbtn.getAttribute('agnCont');
            editbtn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Edit Agency',
                    html: `
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" value="${ename}">
                        </div>
                        <div class="mb-3">
                            <label for="addr" class="form-label">Address</label>
                            <input type="text" class="form-control" id="addr" value="${eaddr}">
                        </div>
                        <div class="mb-3">
                            <label for="cont" class="form-label">Contact</label>
                            <input type="text" class="form-control" id="cont" value="${econt}">
                        </div>
                        <div class="flex items-center justify-center w-full mt-2">
                            <label for="agn-file" class="flex flex-col items-center justify-center w-full h-30 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50   hover:bg-gray-100   ">
                                <div class="flex flex-col items-center justify-center pt-2 pb-2">
                                    <p class="mb-2 font-bold">รูปภาพlogo</p>
                                    <p class="text-sm text-gray-500 "><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-500 ">jpeg,png  (MAX 5Mb size)</p>
                                </div>
                                <input id="agn-file" type="file" class="hidden" />
                            </label>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "Save",
                    preConfirm: () => {
                        const agnname = document.getElementById('name').value;
                        const addr = document.getElementById('addr').value;
                        const cont = document.getElementById('cont').value;
                        const agnfile = document.getElementById('agn-file').files[0];

                        if (!agnname) {
                            Swal.showValidationMessage("Name is required");
                            return;
                        }

                        const formData = new FormData();
                        formData.append('name', agnname);
                        formData.append('address', addr);
                        formData.append('contact', cont);
                        formData.append('logo', agnfile);
                        formData.append('agnid', eid);
                        formData.append('editType', etype);
                        formData.append('_token', '{{ csrf_token() }}');

                        return fetch('/manage/update', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText)
                            }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed`
                            )
                        })
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log(result.value);
                        Swal.fire(
                            'Saved!',
                            'Your changed was saved successfully.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload()
                            }
                        });
                    }
                });
            })
        } else if (etype == 'brn') {
            const brnName = editbtn.getAttribute('brnName');
            const brnAgn = editbtn.getAttribute('brnAgn');
            editbtn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Add Branch',
                    html: `
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" value="${brnName}">
                        </div>

                        <label for="agn" class="form-label">Agency</label>
                        <select class="form-select" aria-label="Default select example" id="agn" value="${brnAgn}">
                            @foreach ($agns as $agn)
                                <option value="{{$agn->id}}">{{$agn->name}}</option>
                            @endforeach
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "Save",
                    preConfirm: () => {
                        const brnname = document.getElementById('name').value;
                        const agn = document.getElementById('agn').value;

                        if (!brnname) {
                            Swal.showValidationMessage("Name is required");
                            return;
                        }

                        return fetch('/manage/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ name: brnname, agency:agn,  brnid: eid, editType: etype})
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText)
                            }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed`
                            )
                        })
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log(result.value);
                        Swal.fire(
                            'Saved!',
                            'Your changed was saved successfully.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload()
                            }
                        });
                    }
                });
            })
        } else if (etype == 'dpm') {
            const dpmName = editbtn.getAttribute('dpmName');
            const dpmPrefix = editbtn.getAttribute('dpmPrefix');
            const dpmBrn = editbtn.getAttribute('dpmBrn');
            editbtn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Add Department',
                    html: `
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" value="${dpmName}">
                        </div>

                        <div class="mb-3">
                            <label for="prefix" class="form-label">Prefix</label>
                            <input type="text" class="form-control" id="prefix" value="${dpmPrefix}">
                        </div>

                        <label for="brn" class="form-label">Branch</label>
                        <select class="form-select" aria-label="Default select example" id="brn" value="${dpmBrn}">
                            @foreach ($brns as $brn)
                                <option value="{{$brn->id}}">{{$brn->name}}</option>
                            @endforeach
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    showLoaderOnConfirm: true,
                    preConfirm: (name) => {
                        const dpmname = document.getElementById('name').value;
                        const prefix = document.getElementById('prefix').value;
                        const brn = document.getElementById('brn').value;

                        if (!dpmname || !prefix ) {
                            Swal.showValidationMessage("Name or prefix is required");
                            return;
                        }

                        return fetch('/manage/update', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ name: dpmname, branch:brn, prefix:prefix, dpmid: eid, editType: etype })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText)
                            }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed`
                            )
                        })
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log(result.value);
                        Swal.fire(
                            'Saved!',
                            'Your changed was saved successfully.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload()
                            }
                        });
                    }
                });
            })
        } else if (etype == 'role') {
            editbtn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Edit Role',
                    html: `
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('messages.Name') }}</label>
                            <input type="text" value="${eid}" class="form-control" id="name">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('messages.Save') }}",
                    preConfirm: () => {
                        const rname = document.getElementById('name').value;

                        if (!rname) {
                            Swal.showValidationMessage("Name is required");
                            return;
                        }

                        return fetch('/manage/editRole', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ newName: rname , oldName: eid})
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText)
                            }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed`
                            )
                        })
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log(result.value);
                        Swal.fire(
                            'Saved!',
                            'Role was saved successfully.',
                            'success'
                        ).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload()
                            }
                        });
                    }
                });
            });
        }

    })

    $(document).ready(function () {
        $('.permission-checkbox').change(function () {
            // Get checkbox details
            var checkbox = $(this);
            var rname = checkbox.val();
            var pname = checkbox.attr('name');
            var checked = checkbox.prop('checked');

            // Prepare data for AJAX request
            var data = {
                role_name: rname,
                perm_name: pname,
                check: checked
            };

            // Send data to the controller using AJAX
            $.ajax({
                url: '/add-permission-to-role',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    // Handle success response if needed
                    console.log(response);
                },
                error: function (error) {
                    // Handle error if needed
                    console.error(error);
                }
            });
        });
    });
</script>

<style>

</style>
