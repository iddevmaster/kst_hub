<x-app-layout>
    <div class="py-10">
        <div class="px-4 max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center flex-wrap gap-2">
                <p class="fs-2 fw-bold m-0">{{ __('messages.cert_templates') }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('certificates.manage') }}" class="text-gray-800 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-4 py-2.5">
                        <i class="bi bi-list-check"></i> {{ __('messages.cert_issued') }}
                    </a>
                    <a href="{{ route('certificates.templates.create') }}" class="text-white bg-green-700 hover:bg-green-800 font-medium rounded-lg text-sm px-4 py-2.5">
                        <i class="bi bi-plus-lg"></i> เพิ่ม template
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">ตัวอย่าง</th>
                            <th class="px-4 py-3">ชื่อ</th>
                            <th class="px-4 py-3">ใช้กับหลักสูตร</th>
                            <th class="px-4 py-3">สถานะ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $t)
                            <tr class="bg-white border-b">
                                <td class="px-4 py-2"><img src="{{ $t->bgUrl() }}" alt="" style="height: 50px;" class="rounded border"></td>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $t->name }}</td>
                                <td class="px-4 py-2">
                                    @if ($t->course_id)
                                        {{ optional($t->getCourse)->title ?? '(หลักสูตรถูกลบ)' }}
                                    @else
                                        <span class="text-xs bg-blue-100 text-blue-800 rounded px-2 py-1">ค่าเริ่มต้น (ทุกหลักสูตรที่ไม่มี template เฉพาะ)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if ($t->active)
                                        <span class="text-xs bg-green-100 text-green-800 rounded px-2 py-1">เปิดใช้งาน</span>
                                    @else
                                        <span class="text-xs bg-gray-200 text-gray-700 rounded px-2 py-1">ปิด</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-end whitespace-nowrap">
                                    <a href="{{ route('certificates.templates.preview', $t->id) }}" target="_blank" class="text-blue-600 hover:underline me-3">ดูตัวอย่าง</a>
                                    <a href="{{ route('certificates.templates.edit', $t->id) }}" class="text-amber-600 hover:underline me-3">แก้ไข</a>
                                    <form action="{{ route('certificates.templates.delete', $t->id) }}" method="post" class="inline" onsubmit="return confirm('ลบ template นี้? ใบรับรองที่ออกไปแล้วจะยังดาวน์โหลดได้');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline">ลบ</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="bg-white"><td colspan="5" class="px-4 py-6 text-center text-gray-400">ยังไม่มี template</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
