<div class="p-4 rounded-lg" id="addGroup">
    <form wire:submit.prevent="updateGroup">
        @csrf
        <div class="mb-6">
            <label for="gname" class="block mb-2 text-sm font-medium text-gray-900">{{ __('messages.Name') }}</label>
            <input type="gname" wire:model="gName" id="gname" maxlength="100" name="gname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 " placeholder="Enter group name" required>
        </div>
        <div class="my-4">
            {{-- <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search for names.."> --}}
            <div class="my-5 flex justify-between">
                <h3 class="text-lg font-medium text-gray-900">{{ __('messages.choose_course') }}:</h3>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="text" maxlength="50" onkeyup="myFunction()" id="myInput" class="block w-full ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500" placeholder="Search...">
                </div>
            </div>
            <ul class="grid w-full gap-6 md:grid-cols-3" id="myUL">
                @if ($all_courses)
                    @foreach ($all_courses as $index => $course)
                        <li>
                            <input type="checkbox" id="react-option{{ $index }}" value="{{ $course->id }}"  class="hidden peer"
                                {{ in_array($course->id, $courses->pluck('id')->toArray()) ? 'checked' : '' }}  wire:model="gCourses">
                            <label for="react-option{{ $index }}" class="inline-flex items-center justify-between w-full text-gray-500 bg-white border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 hover:text-gray-600 peer-checked:text-gray-600 hover:bg-gray-50">
                                <div class="card w-full" style="height: 200px">
                                    <div class="card-header" style="height: 100px; background-image: url('{{ $course->img ? '/uploads/course_imgs/'.$course->img : '/img/logo.png' }}'); background-size: cover; background-position:center;">
                                    </div>
                                    <div class="card-body gray-800" style="border-radius: 0px 0px 5px 5px">
                                        <h5 class="card-title fw-bold mb-2"><span>{{ $course->code }}</span>:: {{ $course->title }}</h5>
                                    </div>
                                </div>
                            </label>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="modal-footer">
            <button type="button" wire:click="switchToGroupMode" class="btn btn-outline-primary mx-2">{{ __('messages.Cancel') }}</button>
            <button type="submit" class="btn btn-outline-primary mx-2">{{ __('messages.Save') }}</button>
        </div>
    </form>
</div>
