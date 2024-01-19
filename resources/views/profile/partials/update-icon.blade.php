<section>
    <div class="container">
        <div class="row ">
            <div class="col-lg-8 col-md-6 col-sm-12 mb-2">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('messages.Profile Icon') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ __("messages.Update your account's profile icon") }}
                    </p>
                </header>

                <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                    @csrf
                </form>

                <form method="POST" action="{{ route('icon.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf

                    <div class="mb-3">
                        <label for="iconFile" class="form-label">{{ __('messages.Default file input example') }}</label>
                        <input class="form-control border rounded" type="file" id="iconFile" name="icon" accept="image/png, image/jpeg">
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button name='action' :value="1">{{ __('messages.Save') }}</x-primary-button>
                        <x-danger-button name='action' :value="0">{{ __('messages.Delete') }}</x-danger-button>

                        @if (session('status') === 'profile-updated')
                            <p
                                x-data="{ show: true }"
                                x-show="show"
                                x-transition
                                x-init="setTimeout(() => show = false, 2000)"
                                class="text-sm text-gray-600"
                            >{{ __('Saved.') }}</p>
                        @elseif (session('status') === 'profile-updated-error')
                        <p
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 2000)"
                            class="text-sm text-red-600"
                        >{{ __("Don't Saved.") }}</p>
                        @elseif (session('status') === 'icon-deleted')
                        <p
                            x-data="{ show: true }"
                            x-show="show"
                            x-transition
                            x-init="setTimeout(() => show = false, 2000)"
                            class="text-sm text-gray-600"
                        >{{ __("Icon deleted.") }}</p>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12">
                <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                    <div style="background-image: url('/img/icons/{{$user->icon? $user->icon : 'person.jpg'}}'); width: 150px; height: 150px; background-size: cover; background-position: center; border-radius: 100%; border: 1px solid black"></div>
                </div>
            </div>
        </div>
    </div>
</section>
