<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MSP') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Scripts -->
    @vite(['resources/js/app.ts', 'resources/css/app.scss'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="flex items-center justify-center p-6 min-h-screen">
        <div class="w-full max-w-md">
            <div class="flex align-middle justify-center">
                <img src="{{ Vite::asset('resources/assets/images/MSP-Logo_400x400.png') }}" alt="">
            </div>
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>
</html>


