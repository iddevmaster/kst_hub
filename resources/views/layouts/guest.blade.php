<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="/img/logo.png" type="image/icon type">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="font-sans text-gray-900 antialiased" >
        <div class="min-h-screen flex flex-col sm:justify-center items-center bg-gray-100" style="background-image: url('{{ asset('img/bg2.jpg') }}'); background-size: cover; background-position: center">
            <div class="py-4">
                <img src="/img/logo.png" style="max-height: 150px" alt="" >
            </div>

            <div class="w-full sm:max-w-md my-6 px-6 py-4  overflow-hidden rounded-xl" style="backdrop-filter: blur(4px); box-shadow: 0px 0px 10px 0px white; border-radius: 20px; background-color:rgba(255, 255, 255, .2);">
                {{ $slot }}
            </div>
            <footer class="flex mt-5 w-100 justify-around w-full absolute bottom-5" >
                <div class="flex gap-2 align-items-center">
                    <div><img src="/img/logoiddrives.png" alt="" width="50"></div>
                    <div class="bg-white rounded p-1"><img src="/img/logoid_new.png" alt="" width="50"></div>
                    <div class="flex items-center text-white text-sm"><p style="height: fit-content">บริษัท ไอดีไดรฟ์ จำกัด 200/222 หมู่2 ถนนชัยพฤกษ์ ตำบลในเมือง อำเภอเมืองขอนแก่น จังหวัดขอนแก่น 40000 <br> Tel : 043-228 899 www.iddrives.co.th Email : idofficer@iddrives.co.th</p></div>
                </div>
                <div class="text-center text-white">
                    @php
                        $nowdate = Carbon\Carbon::now();
                    @endphp
                    <p>© {{ $nowdate->format('Y') }} Hub Training</p>
                    <p>Powered By <a class="text-primary" href="https://iddrives.co.th/">ID Drives</a></p>
                </div>
            </footer>
        </div>
    </body>
</html>
