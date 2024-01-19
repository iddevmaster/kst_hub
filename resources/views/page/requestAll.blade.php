<x-app-layout>
    <div class="text-center mt-5">
        <p class="fs-1 fw-bold">{{ __('messages.Request All') }}</p>
    </div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-end"><button class="btn btn-success" id="addBtn"><i class="bi bi-plus-lg"></i>{{ __('messages.Add') }}</button></div>

            <div class="sm:rounded-lg p-4 row">
                @hasanyrole('admin|staff')
                    @foreach (auth()->user()->notifications as $notify)
                        @php
                            $user = App\Models\User::find($notify->data['user']);
                        @endphp
                        <div x-data="{ open: false }" class='mb-3 hover:-translate-y-1 duration-300'>
                            <button @click="open = !open" class = 'shadow-sm rounded-2xl bg-white w-full p-2 text-left text-gray-700'>
                                <div class="flex justify-around">
                                    <p>Request from: {{ $user->name }}</p>
                                    <p>Department: {{ $user->dpmName->name }}</p>
                                    <p><i class="bi bi-clock"></i> {{ $notify->data['date'] }}
                                        @if ($notify->data['status'] == 'wait')
                                            <span class="ms-1 inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ $notify->data['status'] }}</span>
                                        @elseif ($notify->data['status'] == 'success')
                                            <span class="inline-flex items-center rounded-md bg-green-500 px-2 py-1 text-xs font-medium text-white ring-1 ring-inset ring-gray-500/10">{{ $notify->data['status'] }}</span>
                                        @endif
                                    </p>
                                </div>
                            </button>
                            <div x-show="open" class="accordion-content" class='w-full px-4 py-2 text-left text-gray-700'>
                                <div class="flex justify-center w-full">
                                    <div class="bg-white w-4/5 p-2">
                                        <div class="grid grid-cols-3 text-sm mb-2 bg-gray-100 rounded-lg p-2">
                                            <p>{{ $notify->data['content'] }}</p>
                                        </div>
                                        @if ($notify->data['status'] != 'success')
                                            <div class="flex justify-center">
                                                <button class="successBtn btn btn-sm btn-success" data-notification-id="{{ $notify->id }}">Success</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endhasanyrole
                @hasrole('teacher')
                    @foreach (DB::table('notifications')->get() as $notify)
                        @php
                            // Decode the JSON data into an array
                            $notificationData = json_decode($notify->data, true);
                        @endphp
                        @if ($notificationData['user'] == auth()->user()->id)
                            @php
                                $user = auth()->user();
                            @endphp
                            <div x-data="{ open: false }" class='mb-3 hover:-translate-y-1 duration-300'>
                                <button @click="open = !open" class = 'shadow-sm rounded-2xl bg-white w-full p-2 text-left text-gray-700'>
                                    <div class="flex justify-around">
                                        <p>Request from: {{ $user->name }}</p>
                                        <p>Department: {{ $user->dpmName->name }}</p>
                                        <p><i class="bi bi-clock"></i> {{ $notificationData['date'] }}
                                            @if ($notificationData['status'] == 'wait')
                                                <span class="ms-1 inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">{{ $notificationData['status'] }}</span>
                                            @elseif ($notificationData['status'] == 'success')
                                                <span class="inline-flex items-center rounded-md bg-green-500 px-2 py-1 text-xs font-medium text-white ring-1 ring-inset ring-gray-500/10">{{ $notificationData['status'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </button>
                                <div x-show="open" class="accordion-content" class='w-full px-4 py-2 text-left text-gray-700'>
                                    <div class="flex justify-center w-full">
                                        <div class="bg-white w-4/5 p-2">
                                            <div class="grid grid-cols-3 text-sm mb-2 bg-gray-100 rounded-lg p-2">
                                                <p>{{ $notificationData['content'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endhasrole
            </div>
        </div>
    </div>
</x-app-layout>
<script type="text/javascript">

    $(document).ready(function() {
        $('.successBtn').click(function() {
            // Get the notification ID from the data attribute
            var notificationId = $(this).data('notification-id');

            // Send an AJAX request to mark the notification as read
            $.ajax({
                url: '/notifications/success/' + notificationId, // You need to define this route in your web.php
                type: 'GET',
                success: function(response) {
                    // You can add some code here to handle a successful response
                    console.log('Notification marked as success');
                    window.location.reload()
                },
                error: function(error) {
                    // You can add some error handling here
                    console.log('Error marking notification as success');
                }
            });
        });
    });

    const addBtn = document.getElementById('addBtn');
    addBtn.addEventListener('click', () => {
        Swal.fire({
            input: 'textarea',
            inputLabel: 'Send Message to Staff',
            inputPlaceholder: 'Type your message here...',
            inputAttributes: {
                'aria-label': 'Type your message here'
            },
            showCancelButton: true,
            confirmButtonText: 'Send',
            showLoaderOnConfirm: true,
            preConfirm: (message) => {
                // You could add code here to handle the message, like sending it to a server
                // For example, using fetch to send the message to a server endpoint:
                if ( !message ) {
                    Swal.showValidationMessage("Please enter message!");
                    return;
                }

                return fetch('/notic/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ text: message })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json();
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error}`
                    )
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                // If the promise resolves, it means the message was sent successfully
                // You can handle the success here
                console.log('Message was sent', result.value);
                Swal.fire(
                    'Success!',
                    'Your message was sent.',
                    'success'
                )
            }
        });
    })
</script>

<style>

</style>
