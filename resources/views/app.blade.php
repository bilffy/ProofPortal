<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <!-- MSP LOGO -->
    <link rel="shortcut icon" href="{{ asset('proofing-assets/img/msp_logo.svg') }}">
    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <!-- Scripts and Styles -->
    @livewireStyles
    @vite(entrypoints: ['resources/css/app.scss', 'resources/js/app.ts'])
    <script type="module" src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    @yield('css')
    <script>
        var base_url = "{{URL::to('/')}}";
    </script>
</head>

<body class="font-sans antialiased">
    @if(session('success'))
        <x-toast-success message="{{  session('success') }}" />
    @endif

    @yield('main')
    @livewireScripts

    <!-- Proofing Assets: Bootstrap and necessary plugins -->
    <script src="{{ asset('proofing-assets/vendors/js/jquery.min.js') }}"></script>
    <script src="{{ asset('proofing-assets/vendors/js/popper2.11.8.min.js') }}"></script>
    <script src="{{ asset('proofing-assets/vendors/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('proofing-assets/vendors/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('proofing-assets/vendors/js/pace.min.js') }}"></script>
    <script src="{{ asset('proofing-assets/js/app.js') }}"></script>
    <!-- END OF Proofing Assets: Bootstrap and necessary plugins -->

    <!-- CoreUI main scripts -->
    @yield('js')
    @stack('scripts')
</body>

</html>