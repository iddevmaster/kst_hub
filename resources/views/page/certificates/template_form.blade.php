<x-app-layout>
    @php
        $labels = [
            'name' => 'ชื่อผู้รับ',
            'course' => 'ชื่อหลักสูตร',
            'date' => 'วันที่ออกใบ',
            'cert_no' => 'เลขที่ใบรับรอง',
        ];
        $action = $template
            ? route('certificates.templates.update', $template->id)
            : route('certificates.templates.store');
        $inputCls = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2';
    @endphp
    <div class="py-10">
        <div class="px-4 max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center">
                <p class="fs-2 fw-bold m-0">{{ $template ? 'แก้ไข template' : 'เพิ่ม template' }}</p>
                <a href="{{ route('certificates.templates') }}" class="text-gray-600 hover:underline"><i class="bi bi-arrow-left"></i> กลับ</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ $action }}" method="post" enctype="multipart/form-data" class="card p-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-1 text-sm font-medium">ชื่อ template</label>
                        <input type="text" name="name" class="{{ $inputCls }}" value="{{ old('name', optional($template)->name) }}" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium">ใช้กับหลักสูตร</label>
                        <select name="course_id" class="{{ $inputCls }}">
                            <option value="">— ค่าเริ่มต้น (ทุกหลักสูตรที่ไม่มี template เฉพาะ) —</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" @selected(old('course_id', optional($template)->course_id) == $c->id)>
                                    {{ $c->code ? $c->code . ' - ' : '' }}{{ $c->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">ผู้เรียนที่ผ่านแบบทดสอบใดก็ได้ของหลักสูตรนี้จะได้ใบรับรอง</p>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium">
                            รูปพื้นหลังใบรับรอง (JPG/PNG, แนวนอน A4 สัดส่วน ~1.414:1) {{ $template ? '— เลือกเมื่อต้องการเปลี่ยน' : '' }}
                        </label>
                        <input type="file" name="bg_image" accept="image/png,image/jpeg" class="{{ $inputCls }}" {{ $template ? '' : 'required' }}>
                        @if ($template)
                            <img src="{{ $template->bgUrl() }}" alt="" class="mt-2 rounded border" style="max-height: 120px;">
                        @endif
                    </div>
                    <div class="flex items-center">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="active" value="1" @checked(old('active', $template ? $template->active : true))>
                            <span class="text-sm font-medium">เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>

                <p class="font-bold mb-1">ตำแหน่งข้อความบนใบรับรอง</p>
                <p class="text-xs text-gray-500 mb-2">
                    X / Y เป็น % ของหน้ากระดาษ (0,0 = มุมซ้ายบน). Align คือจุด X เป็นกึ่งกลาง/ชิดซ้าย/ชิดขวาของข้อความ.
                    บันทึกแล้วกด "ดูตัวอย่าง PDF" เพื่อตรวจตำแหน่ง ปรับจนพอดีกับช่องว่างในรูปพื้นหลัง
                </p>
                <div class="relative overflow-x-auto mb-4">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-2 py-2">ข้อมูล</th>
                                <th class="px-2 py-2">แสดง</th>
                                <th class="px-2 py-2">X %</th>
                                <th class="px-2 py-2">Y %</th>
                                <th class="px-2 py-2">ขนาด (pt)</th>
                                <th class="px-2 py-2">Align</th>
                                <th class="px-2 py-2">ตัวหนา</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labels as $key => $label)
                                @php $f = old("fields.$key", $fields[$key]); @endphp
                                <tr class="border-b">
                                    <td class="px-2 py-2 font-medium">{{ $label }}</td>
                                    <td class="px-2 py-2"><input type="checkbox" name="fields[{{ $key }}][show]" value="1" @checked(!empty($f['show']))></td>
                                    <td class="px-2 py-2" style="width: 100px;"><input type="number" step="0.1" min="0" max="100" name="fields[{{ $key }}][x]" value="{{ $f['x'] }}" class="{{ $inputCls }}" required></td>
                                    <td class="px-2 py-2" style="width: 100px;"><input type="number" step="0.1" min="0" max="100" name="fields[{{ $key }}][y]" value="{{ $f['y'] }}" class="{{ $inputCls }}" required></td>
                                    <td class="px-2 py-2" style="width: 100px;"><input type="number" step="1" min="6" max="120" name="fields[{{ $key }}][size]" value="{{ $f['size'] }}" class="{{ $inputCls }}" required></td>
                                    <td class="px-2 py-2" style="width: 130px;">
                                        <select name="fields[{{ $key }}][align]" class="{{ $inputCls }}">
                                            @foreach (['center' => 'กึ่งกลาง', 'left' => 'ชิดซ้าย', 'right' => 'ชิดขวา'] as $v => $t)
                                                <option value="{{ $v }}" @selected($f['align'] === $v)>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-2"><input type="checkbox" name="fields[{{ $key }}][bold]" value="1" @checked(!empty($f['bold']))></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="text-white bg-green-700 hover:bg-green-800 font-medium rounded-lg text-sm px-5 py-2.5">
                        <i class="bi bi-save"></i> บันทึก
                    </button>
                    @if ($template)
                        <a href="{{ route('certificates.templates.preview', $template->id) }}" target="_blank" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">
                            <i class="bi bi-file-earmark-pdf"></i> ดูตัวอย่าง PDF (ค่าที่บันทึกล่าสุด)
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
