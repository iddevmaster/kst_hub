<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: normal;
            src: url("{{ public_path('fonts/THSarabunNew.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: bold;
            src: url("{{ public_path('fonts/THSarabunNew Bold.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: italic;
            font-weight: normal;
            src: url("{{ public_path('fonts/THSarabunNew Italic.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: italic;
            font-weight: bold;
            src: url("{{ public_path('fonts/THSarabunNew BoldItalic.ttf') }}") format('truetype');
        }
        :root {
            font-size: 16px;
        }
        body {
            font-family: 'THSarabunNew';
            width: 794px;
            height: 1123px;
        }
        header {
            text-align: center;
        }
        header > h3 {
            margin-top: 5px;
            margin-bottom: 0px;
        }
        header > p {
            margin-top: 5px;
        }
        section {
            display: flex;
            justify-content: center;
        }
        section > table {
            width: 90%;
            border-collapse: collapse;
            text-align: start;
        }
        section > table > thead > tr > th {
            border: 1px solid black;
            padding: 5px;
            background-color: #f1f1f1;
        }
        section > table > tbody > tr > td {
            border: 1px solid black;
            padding: 5px;
        }
        footer {
            margin-top: 0px;
            text-align: center;
            font-size: 10px;
        }
        #filtered {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            margin-top: 0px;
            margin-bottom: 10px;
        }
        #filtered > p {
            margin: 0px;
        }
    </style>
</head>
<body>
    <header>
        <img src="/img/logo.png" width="60" alt="">
        <h3>ศูนย์ฝึกอบรมเทรนนิ่งเซนเตอร์</h3>
        <p>ลำดับผลการทดสอบ</p>
        <div id="filtered">
            @if ($fbrn)
                <p>สาขา: <u>{{ $fbrn->name }}</u></p>
            @endif
            @if ($fuser)
                <p>ผู้ทดสอบ: <u>{{ $fuser->name }}</u></p>
            @endif
            @if ($fquiz)
                <p>ชุดข้อสอบ: <u>{{ $fquiz->title }}</u></p>
            @endif
            @if ($fsdate)
                <p>ประจำวันที่: <u>{{ $fsdate }}</u></p>
            @endif
        </div>
    </header>
    <section>
        <table>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"> {{ __('messages.Quiz') }}</th>
                    <th scope="col">{{ __('messages.User') }}</th>
                    <th scope="col">คะแนนที่ดีที่สุด</th>
                    <th scope="col">จำนวนครั้งที่ทำ</th>
                    <th>ค่าเฉลี่ย</th>
                    {{-- <th>วันที่สอบล่าสุด</th> --}}
                </tr>
            </thead>
            <tbody class="text-start">
                @if (count($tests ?? []) > 0)
                    @foreach ($tests as $index => $test)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ optional($test->getQuiz)->title }}</td>
                            <td>{{ optional($test->getTester)->name }}</td>
                            <td>{{ $test->best_score }}</td>
                            <td>{{ $test->times_tested }}</td>
                            <td>{{ $test->average_score }}</td>
                            {{-- <td>{{ Carbon\Carbon::parse($test->latest_at)->thaidate('j M Y - H:i') }}</td> --}}
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center"><span
                                class="bg-pink-100 text-pink-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-pink-400">ไม่พบข้อมูล</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </section>
    <footer>
        <p>Printed from <u>https://smarthub.trainingzenter.com</u> at {{ now() }}</p>
    </footer>
    <script>
        window.onload = function () {
            window.print();
        }
    </script>
</body>
</html>
