@if (count($courses) > 0)
    @foreach ($courses as $course)
        <a href="{{ route('course.detail', ['id' => $course->id]) }}">
            <div class="card" style="height: 350px">
                <div class="card-header" style="background-image: url('{{ $course->img ? '/uploads/course_imgs/'.$course->img : '/img/smarthubtz_logo.png' }}')">
                    {{-- course Img --}}
                </div>
                <div class="card-body" style="border-radius: 0px 0px 5px 5px">
                    <div class="p-2 pt-0">
                        <p class="card-title fw-bold mb-0 text-xs">ฝ่าย: {{ optional($course->getDpm)->name }}</p>
                        <h5 class="card-title fw-bold text-xl mb-2">{{ $course->code }} : {{ Str::limit($course->title, 60) }}</h5>
                        <p class="card-text text-gray-600 text-sm">{{ Str::limit($course->description, 100) }}</p>
                    </div>
                </div>
            </div>
        </a>
    @endforeach
@else
    <div class="flex justify-center fw-bold"><span class="bg-yellow-100 text-yellow-800 text-xl font-medium mr-2 px-2.5 py-0.5 rounded ">Course not found</span></div>
@endif
