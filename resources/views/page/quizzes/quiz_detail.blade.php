<x-app-layout>
    <x-slot name="header">
        <div class="font-semibold text-xl text-gray-800 leading-tight d-flex justify-content-between">
            <p><b>{{ __('messages.quiz') }}:: </b>{{ $quiz->title }}</p>
        </div>
    </x-slot>
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-lg-2 col-md-4 col-sm-12">
                <div class="card p-4 mb-4">
                    <p class="text-center fw-bold fs-5 mb-4">{{ __('messages.quiz_fea') }}</p>
                    <p><b>{{ __('messages.time_lim') }}: </b>&nbsp; {{ $quiz->time_limit ? $quiz->time_limit : "None" }}</p>
                    <p><b>{{ __('messages.pass_sc') }}: </b>&nbsp; {{ $quiz->pass_score }}%</p>
                    <p><b>{{ __('messages.shuff_ques') }}: </b>&nbsp; {{ $quiz->shuffle_quest ? "True" : "False" }}</p>
                    <p><b>{{ __('messages.by') }}: </b>&nbsp; {{ optional($quiz->getCreated)->name }}</p>
                    <p><b>{{ __('messages.question') }}: </b>&nbsp; {{ count( $questions ?? [] ) }}</p>
                    <p><b>{{ __('messages.update') }}: </b> &nbsp;
                        @php
                            $date = new DateTime($quiz->updated_at);
                            $formattedDate = $date->format('d-m-Y');
                            echo $formattedDate;
                        @endphp
                    </p>
                    <p><b>{{ __('messages.create') }}: </b> &nbsp;
                        @php
                            $date = new DateTime($quiz->created_at);
                            $formattedDate = $date->format('d-m-Y');
                            echo $formattedDate;
                        @endphp
                    </p>
                </div>
                <div class="flex justify-center mt-4 ">
                    <a href="{{ route('quiz.quest.add', ['id' => $id]) }}">
                        <button type="button" class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-2 py-2 me-2 mb-2 ">
                            <i class="bi bi-plus-lg"></i> {{ __('messages.add_ques') }}
                        </button>
                    </a>
                </div>
                <div class="card p-4 mb-4">
                    <div class="mb-3 text-center">
                        <p>{{ __('messages.question') }}</p>
                    </div>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($questions as $index => $quest)
                            <button type="button" class="text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-500 focus:ring-2 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-xs p-1.5 text-center">
                                {{$index+1}}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-10 col-sm-12 col-md-8 mb-4" id="content">
                @foreach ($questions as $index => $quest)
                    <div id="accordion-open" data-accordion="open" class="mb-2">
                        <h2 id="accordion-open-heading{{$index}}">
                            <button type="button" class="bg-white rounded-xl flex items-center justify-between w-full px-4 py-3 font-medium rtl:text-right text-gray-500 border border-gray-200 focus:text-black focus:ring-4 focus:ring-gray-200 "
                                data-accordion-target="#accordion-open-body{{$index}}" aria-expanded="false" aria-controls="accordion-open-body{{$index}}">
                                <span class="flex items-center">{{$index+1}}. {!! $quest->title !!}</span>
                                <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                                </svg>
                            </button>
                        </h2>
                        <div id="accordion-open-body{{$index}}" class="hidden" aria-labelledby="accordion-open-heading{{$index}}">
                            <div class="overflow-x-scroll px-3.5 md:px-8 py-4 border border-t-0 border-gray-200 rounded-xl bg-gray-50">
                                <div class="flex gap-4 mb-2 flex-wrap">
                                    <p ><b>{{ __('messages.type') }}:</b> &nbsp;{{$quest->type ? 'Multiple choice' : 'Short answer'}}</p>
                                    <p ><b>{{ __('messages.score') }}:</b> &nbsp;{{$quest->score}}</p>
                                    <p ><b>{{ __('messages.shuff_ques') }}:</b> &nbsp;{{$quest->shuffle_ch ? "Yes" : "No"}}</p>
                                </div>
                                <div>
                                    <div class="d-flex">
                                        <p class="align-self-center me-2"><b>เสียงบรรยาย:</b> </p>
                                        @if ($quest->audio)
                                            <audio preload controls sourcelist="{{ $quest->audio }}"></audio>
                                        @else
                                            <p class="text-danger">-ไม่พบเสียงบรรยาย-</p>
                                        @endif
                                    </div>
                                </div>
                                @if ($quest->type)
                                    <p class="mb-2"><b>{{ __('messages.choices') }}:</b></p>
                                    <div class="flex lg:gap-x-20 gap-3.5 bg-gray-200 p-2 flex-wrap rounded">
                                        @foreach ($quest->answer as $index => $choice)
                                            <p class="{{ $choice['answer'] ? 'text-green-500' : '' }}">{{$index+1}}. {{$choice['text']}}</p>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mb-2"><b>{{ __('messages.answer') }}:</b> &nbsp;<span class="bg-gray-200 px-2 py-1 rounded">{{$quest->answer[0]['answer']}}</span></p>
                                @endif

                                <div class="mt-4 flex gap-2">
                                    <a href="{{route('quiz.quest.edit', ['qid' => $id, 'id' => $quest->id])}}">
                                        <button type="button" class="flex px-2 py-2 text-xs font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                                            <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                                                <path d="M12.687 14.408a3.01 3.01 0 0 1-1.533.821l-3.566.713a3 3 0 0 1-3.53-3.53l.713-3.566a3.01 3.01 0 0 1 .821-1.533L10.905 2H2.167A2.169 2.169 0 0 0 0 4.167v11.666A2.169 2.169 0 0 0 2.167 18h11.666A2.169 2.169 0 0 0 16 15.833V11.1l-3.313 3.308Zm5.53-9.065.546-.546a2.518 2.518 0 0 0 0-3.56 2.576 2.576 0 0 0-3.559 0l-.547.547 3.56 3.56Z"/>
                                                <path d="M13.243 3.2 7.359 9.081a.5.5 0 0 0-.136.256L6.51 12.9a.5.5 0 0 0 .59.59l3.566-.713a.5.5 0 0 0 .255-.136L16.8 6.757 13.243 3.2Z"/>
                                            </svg>
                                            &nbsp; {{ __('messages.edit') }}
                                        </button>
                                    </a>

                                    <button type="button" data-ques-id="{{$quest->id}}" value="{{$quest->id}}" class="delQuesBtn flex px-2 py-2 text-xs font-medium text-center text-white bg-red-500 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-blue-300 ">
                                        <svg class="w-4 h-4 text-white " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h16M7 8v8m4-8v8M7 1h4a1 1 0 0 1 1 1v3H6V2a1 1 0 0 1 1-1ZM3 5h12v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5Z"/>
                                        </svg>
                                        &nbsp; {{ __('messages.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
            Swal.fire({
                icon: "success",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000,
                title: "{{ session('success') }}"
            });
        @elseif (session('error'))
            Swal.fire({
                icon: "error",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000,
                title: "Question has not been saved."
            });
            console.log("Error: {{ session('error') }}");
        @endif
    });

    $(document).ready(function() {
        $('.delQuesBtn').click(function() {
            // Get the notification ID from the data attribute
            const delQuesId = $(this).data('ques-id');
            Swal.fire({
                title: `Are you sure?`,
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/quiz/question/delete/' + delQuesId, // You need to define this route in your web.php
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            // You can add some code here to handle a successful response
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Question has been deleted.',
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
                                    'Question has not deleted.',
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
                                'Question has not deleted.',
                                'error'
                            )
                            console.log("error: ",error);
                        }
                    });
                }
            })

        });

        $('audio').each(function(){
            const sourcelist = $(this).attr('sourcelist');
            let source = JSON.parse(sourcelist);
            console.log("source: ", source);
            let current = 0;

            $(this).attr('src', source[current]);

            $(this).on('ended', function(){
                current++;
                if(current < source.length){
                    $(this).attr('src', source[current]);
                    this.play();
                } else {
                    current = 0;
                    $(this).attr('src', source[current]);
                }
            });
        });
    });


</script>
<style>
    .course-menu {
        position: absolute;
        top: 0;
        right: 0;
        width: fit-content;
        /* display: none; */
        transition: 1s;
    }
    .addtopic-btn > p {
        display: none;
    }
    .addtopic-btn:hover > p {
        display: unset;
    }
</style>
