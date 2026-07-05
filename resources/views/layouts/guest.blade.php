<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MSP') }}</title>
    <link rel="shortcut icon" href="{{ URL::asset('proofing-assets/img/msp_logo.svg') }}">
    <!-- Fonts -->
    <!-- <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap"> -->
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <!-- Scripts -->
    @vite(['resources/js/app.ts', 'resources/css/app.scss'])
    @livewireStyles
    <script>
        // Disable right click context menu immediately
        // document.addEventListener('contextmenu', event => event.preventDefault());

        // Block keyboard shortcuts for Save (Ctrl+S), View Source (Ctrl+U), and Inspect (F12/Ctrl+Shift+I)
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S (Save), U (View Source), P (Print)
            if ((e.ctrlKey || e.metaKey) && (e.keyCode === 83 || e.keyCode === 85 || e.keyCode === 80)) {
                e.preventDefault();
                return false;
            }
            // F12 or Ctrl+Shift+I (Inspect)
            if (e.keyCode === 123 || ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 73)) {
                e.preventDefault();
                return false;
            }
        });

        // Extra layer for right-click mouse buttons
        document.addEventListener('mousedown', function(e) {
            if (e.button === 2) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</head>
<body class="font-sans antialiased">
    <div class="flex items-center justify-center p-6 min-h-screen">
        <div class="w-full max-w-md">
            <div class="flex align-middle justify-center">
                <img src="{{ Vite::asset('resources/assets/images/MSP-Logo.svg') }}" width="150" alt="">
            </div>
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>


