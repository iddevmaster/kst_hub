<x-app-layout>
    <x-slot name="header">
        <div class="font-semibold text-xl text-gray-800 leading-tight d-flex justify-content-between">
            <p>{{$quiz->title}}</p>
        </div>
    </x-slot>
    <div class="container pt-5">
        <div class="max-w-7xl mx-auto">
            <form action="/quiz/{{ $id }}/file/import" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="card px-4 py-4">
                    <p class="fw-bold text-2xl mb-4">นำเข้าคำถาม</p>
                    <div class="mb-4 border-b border-gray-200 ">
                        <input type="file" name="questionsFile" id="questionsFile" accept=".xlsx">
                        <p class="text-sm text-gray-500">ไฟล์ต้องเป็นไฟล์ .xlsx เท่านั้น</p>
                    </div>
                    <div class="mb-4">
                        <p>จำนวนข้อที่พบทั้งหมด <span id="dataCount" >0</span> ข้อ</p>
                        <p>นำเข้าสำเร็จ <span id="successCount" >0</span> ข้อ</p>
                        <p>นำเข้าไม่สำเร็จ <span id="failCount" >0</span> ข้อ</p>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="transition ease-in-out hover:-translate-y-1 hover:scale-110 duration-300 text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2 ">
                            ดำเนินการนำเข้า
                        </button>
                        <a href="{{route('quiz.detail', ['id' => $id])}}">
                            <button type="button" class="transition ease-in-out hover:-translate-y-1 hover:scale-110 duration-300 focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 ">
                                ย้อนกลับ
                            </button>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
    let questDatas = [];
    document.getElementById('questionsFile').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function(e) {
            var data = new Uint8Array(e.target.result);
            var workbook = XLSX.read(data, { type: 'array' });

            var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            var jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

            jsonData.shift(); // Remove the first line (header)
            questDatas = jsonData;

            // Update the span with id dataCount
            document.getElementById('dataCount').innerText = jsonData.length;
        };
        reader.readAsArrayBuffer(file);

    });

    function chunkArray(array, chunkSize) {
        const chunks = [];
        for (let i = 0; i < array.length; i += chunkSize) {
            chunks.push(array.slice(i, i + chunkSize));
        }
        return chunks;
    }

    function updateImportStatus(successCount, failCount) {
        document.getElementById('successCount').innerText = successCount;
        document.getElementById('failCount').innerText = failCount;
    }

    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let successCount = 0;
        let failCount = 0;

        const chunks = chunkArray(questDatas, 100);
        chunks.forEach((chunkSet, index) => {
            const eachChunkSize = chunkSet.length;
            var formData = new FormData();
            formData.append('questions', JSON.stringify(chunkSet));
            console.log("chunkSet: ",chunkSet);
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successCount += data.success;
                        failCount += (eachChunkSize - data.success);
                        updateImportStatus(successCount, failCount);
                        console.log(`Imported chunk ${index} successfully count : ${successCount}`);
                    }
                }).catch(error => {
                    console.log('Error:', error);
                    failCount += eachChunkSize;
                    updateImportStatus(successCount, failCount);
                    console.log(`Failed to import chunk ${index} count : ${failCount}`);
                });
        });
    });

</script>
