<x-app-layout>
    @php
        $dateString = Auth::user()->startlt;

        // Create a DateTime object from your date string
        $yourDate = DateTime::createFromFormat('Y-m-d', $dateString);

        // Get the current DateTime
        $now = new DateTime();

        // Calculate the difference between the dates
        $difference = $yourDate->diff($now);
    @endphp

    @if ($difference->invert == 1)
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Course') }}
            </h2>
        </x-slot>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 d-flex flex-wrap gap-4 justify-content-center">
                    @foreach ($courses as $course)
                        <div class="card" style="width: 18rem;">
                            <div class="card-header" >
                                <img class="bg-light rounded" src="/img/logo.png" alt="" width="80">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold">{{ $course->title }}</h5>
                                <p class="card-text fs-6 mb-2">{{ Str::limit($course->description, 60) }}</p>
                                <a href="{{ route('course.detail', ['id' => $course->id]) }}" class="btn btn-primary">Detail <i class="bi bi-chevron-double-right"></i></a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 text-center">
                    <i class="bi bi-clock-history text-8xl text-red-500"></i>
                    <p class="text-4xl mt-2 font-bold">Your account has <span class="text-red-500">expired</span></p>
                    <p>Please contact staff to renew it.</p>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
