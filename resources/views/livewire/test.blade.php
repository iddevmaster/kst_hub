<div class="card p-4">
    <div class="bg-gray-200 px-4 py-3 mb-4 rounded d-flex justify-between">
        <div class="d-flex">
            <p>{{ $questNum }}. {!! $question->title !!}</p>
        </div>
        <div class="fs-1 text-red-600" id="playbtn" style="cursor: pointer">
            <div id="playIcon"><i class="bi bi-play-fill"></i></div>
            <audio id="playaudio" preload sourcelist="{{ $question->audio ?? '[]' }}"></audio>
        </div>
    </div>

    @if ($question->type)
        <div class="grid sm:grid-cols-2 grid-cols-1 gap-2 px-2 mb-4">
            @php
                $choices = $question->answer;
                if ($question->shuffle_ch) {
                    $finChoices = collect($choices)->shuffle();
                } else {
                    $finChoices = $choices;
                }
            @endphp
            @foreach ($finChoices as $index => $choice)
                <div class="flex items-center ps-4 border border-gray-200 rounded">
                    <input id="bordered-radio-{{ $question->id . $choice['id'] }}" type="radio"
                        wire:model="answers.{{ $question->id }}" value="{{ $choice['id'] . $choice['answer'] }}"
                        name="bordered-radio"
                        class="cursor-pointer w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500  focus:ring-2 ">
                    <label for="bordered-radio-{{ $question->id . $choice['id'] }}"
                        class="cursor-pointer py-3 ms-2 text-sm font-medium text-gray-900 ">{{ $index + 1 }}. <span
                            class="speaktext">{{ $choice['text'] }}</span></label>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-wrap gap-2 px-2 mb-4">
            <p><b>Answer:</b></p>
            <input type="text" id="choice1" wire:model="answers.{{ $question->id }}" maxlength="1000"
                name="choice1"
                class="block w-100 text-sm text-gray-900 bg-transparent border-t-0 border-s-0 border-e-0 border-b-2 border-gray-400 appearance-none  focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                placeholder="Input text" />
        </div>
    @endif

    <div class="flex justify-between">
        <button wire:click="gotoPreviousQuestion" {{ $questNum <= 1 ? 'disabled' : '' }} type="button"
            class="pauseAudio flex text-white bg-gray-500 hover:bg-gray-600 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2.5 me-2 mb-2   focus:outline-none ">
            <svg class="w-4 h-4 me-1 text-white " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 12 10">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 1 1 5l4 4m6-8L7 5l4 4" />
            </svg>
            Previous
        </button>
        <p class="hidden self-center sm:block md:block lg:block">Question: <b>{{ $questNum }}</b> /
            {{ $total }}</p>
        @if ($questNum >= $total)
            <button wire:click="gotoNextQuestion" type="button"
                class="pauseAudio flex text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800">
                Submit
            </button>
        @else
            <button wire:click="gotoNextQuestion" type="button"
                class="pauseAudio flex text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 me-2 mb-2   focus:outline-none ">
                Next
                <svg class="w-4 h-4 ms-1 text-white " aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 12 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m7 9 4-4-4-4M1 9l4-4-4-4" />
                </svg>
            </button>
        @endif
    </div>
    <script>
        // Reset any existing event handlers
        $('#playIcon').off('click');
        $('#playaudio').off('pause play ended');
        $('.pauseAudio').off('click');

        let isPlaying = false;
        let current = 0;

        // Main play button click handler
        $('#playIcon').on('click', function() {
            const sourcelist = $('#playaudio').attr('sourcelist');

            // ตรวจสอบว่ามีเสียงหรือไม่
            if (!sourcelist || sourcelist === '[]') {
                console.log('No audio available');
                return;
            }

            let source = JSON.parse(sourcelist);
            if (!source || source.length === 0) {
                console.log('No audio available');
                return;
            }

            if (!isPlaying) {
                console.log('Audio sources:', source);
                let current = 0;
                $('#playaudio').attr('src', source[current]);
                $('#playaudio').trigger('play');
                isPlaying = true;
            } else {
                $('#playaudio').trigger('pause');
                isPlaying = false;
            }
        });

        // Handle track ending
        $('#playaudio').on('ended', function() {
            const sourcelist = $('#playaudio').attr('sourcelist');
            if (!sourcelist || sourcelist === '[]') {
                return;
            }

            let source = JSON.parse(sourcelist);
            if (!source || source.length === 0) {
                return;
            }

            current++;
            if (current < source.length) {
                $(this).attr('src', source[current]);
                this.play();
            } else {
                current = 0;
                $(this).attr('src', source[current]);
                isPlaying = false;
            }
        });

        // Handle pause event
        $('#playaudio').on('pause', function() {
            $('#playIcon').html('<i class="bi bi-play-fill"></i>');
            isPlaying = false;
            console.log('Audio has been paused');
        });

        // Handle play event
        $('#playaudio').on('play', function() {
            $('#playIcon').html('<i class="bi bi-pause-fill"></i>');
            isPlaying = true;
            console.log('Audio has playing...');
        });

        // Handle pause button click
        $('.pauseAudio').on('click', function() {
            $('#playaudio').trigger('pause');
            $('#playIcon').html('<i class="bi bi-play-fill"></i>');
            isPlaying = false;
            console.log('Audio has been paused');
        });
    </script>
</div>
