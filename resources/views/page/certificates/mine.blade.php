<x-app-layout>
    <div class="py-10">
        <div class="px-4 max-w-8xl mx-auto sm:px-6 lg:px-8">
            <p class="fs-2 fw-bold mb-4">{{ __('messages.my_certificates') }}</p>

            @if ($certs->isEmpty())
                <div class="card p-4 text-gray-500 text-center">
                    <i class="bi bi-award fs-1"></i>
                    <p class="m-0">{{ __('messages.cert_none') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($certs as $cert)
                        @php $snap = $cert->snapshot ?? []; @endphp
                        <div class="card p-3 shadow-sm {{ $cert->isRevoked() ? 'opacity-60' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="text-amber-500 text-4xl"><i class="bi bi-award-fill"></i></div>
                                <div class="grow">
                                    <p class="font-bold m-0">{{ $snap['course'] ?? optional($cert->course)->title }}</p>
                                    @if (!empty($snap['school']))
                                        <p class="text-sm text-gray-500 m-0">{{ __('messages.cert_school') }}: {{ $snap['school'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500 m-0">{{ $cert->cert_no }}</p>
                                    <p class="text-sm m-0 mt-1">
                                        {{ __('messages.score') }}: {{ $cert->score }}/{{ $cert->total_score }} ({{ rtrim(rtrim(number_format($cert->percent, 2), '0'), '.') }}%)
                                    </p>
                                    <p class="text-xs text-gray-500 m-0">{{ optional($cert->issued_at)->format('d-m-Y') }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                @if ($cert->isRevoked())
                                    <span class="inline-block text-xs text-white bg-red-500 rounded px-2 py-1">ถูกเพิกถอน</span>
                                @else
                                    <a href="{{ route('certificate.download', $cert->id) }}" target="_blank" class="inline-block text-white bg-green-700 hover:bg-green-800 font-medium rounded-lg text-sm px-4 py-2">
                                        <i class="bi bi-download"></i> {{ __('messages.cert_download') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
