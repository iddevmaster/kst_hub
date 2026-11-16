<x-app-layout>
    <div class="py-10">
        <div class="px-4 max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center flex-wrap gap-2">
                <p class="fs-2 fw-bold m-0">{{ __('messages.cert_issued') }}</p>
                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('certificates.templates') }}" class="text-gray-800 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-4 py-2.5">
                        <i class="bi bi-card-image"></i> {{ __('messages.cert_templates') }}
                    </a>
                    <form action="{{ route('certificates.backfill') }}" method="post" onsubmit="return confirm('ออกใบรับรองย้อนหลังให้ผู้ที่เคยสอบผ่านแล้วแต่ยังไม่มีใบ ในหลักสูตรที่มี template?');">
                        @csrf
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2.5">
                            <i class="bi bi-arrow-repeat"></i> ออกใบรับรองย้อนหลัง
                        </button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="get" class="mb-3 flex gap-2" style="max-width: 420px;">
                <input type="search" name="q" value="{{ request('q') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" placeholder="ค้นหาเลขที่ / ชื่อ / หลักสูตร">
                <button class="text-white bg-blue-700 hover:bg-blue-800 rounded-lg text-sm px-4">{{ __('messages.search') }}</button>
            </form>

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">เลขที่</th>
                            <th class="px-4 py-3">ผู้รับ</th>
                            <th class="px-4 py-3">หลักสูตร</th>
                            <th class="px-4 py-3">คะแนน</th>
                            <th class="px-4 py-3">วันที่ออก</th>
                            <th class="px-4 py-3">สถานะ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($certs as $c)
                            @php $snap = $c->snapshot ?? []; @endphp
                            <tr class="bg-white border-b">
                                <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ $c->cert_no }}</td>
                                <td class="px-4 py-2">{{ $snap['name'] ?? optional($c->user)->name }}</td>
                                <td class="px-4 py-2">{{ $snap['course'] ?? optional($c->course)->title }}</td>
                                <td class="px-4 py-2">{{ $c->score }}/{{ $c->total_score }}</td>
                                <td class="px-4 py-2">{{ optional($c->issued_at)->format('d-m-Y') }}</td>
                                <td class="px-4 py-2">
                                    @if ($c->isRevoked())
                                        <span class="text-xs bg-red-100 text-red-800 rounded px-2 py-1">ถูกเพิกถอน</span>
                                    @else
                                        <span class="text-xs bg-green-100 text-green-800 rounded px-2 py-1">ใช้งานได้</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-end whitespace-nowrap">
                                    <a href="{{ route('certificate.download', $c->id) }}" target="_blank" class="text-blue-600 hover:underline me-3">PDF</a>
                                    <form action="{{ route('certificates.revoke', $c->id) }}" method="post" class="inline" onsubmit="return confirm('{{ $c->isRevoked() ? 'คืนสถานะใบรับรองนี้?' : 'เพิกถอนใบรับรองนี้? ผู้เรียนจะดาวน์โหลดไม่ได้' }}');">
                                        @csrf
                                        <button type="submit" class="{{ $c->isRevoked() ? 'text-green-600' : 'text-red-600' }} hover:underline">
                                            {{ $c->isRevoked() ? 'คืนสถานะ' : 'เพิกถอน' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="bg-white"><td colspan="7" class="px-4 py-6 text-center text-gray-400">ยังไม่มีใบรับรองที่ออก</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $certs->links() }}</div>
        </div>
    </div>
</x-app-layout>
