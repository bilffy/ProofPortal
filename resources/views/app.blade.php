<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <!-- MSP LOGO -->
    <link rel="shortcut icon" href="{{ URL::asset('proofing-assets/img/msp_logo.svg') }}">
    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <!-- Scripts and Styles -->
    @vite(entrypoints: ['resources/css/app.scss', 'resources/js/app.ts'])
    @livewireStyles
    <script type="module" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js" integrity="sha512-4MvcHwcbqXKUHB6Lx3Zb5CEAVoE9u84qN+ZSMM6s7z8IeJriExrV3ND5zRze9mxNlABJ6k864P/Vl8m0Sd3DtQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" integrity="sha512-aD9ophpFQ61nFZP6hXYu4Q/b/USW7rpLCQLX6Bi0WJHXNO7Js/fUENpBQf/+P4NtpzNX0jSgR5zVvPOJp+W2Kg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @yield('css')
    <script>
        var base_url = "{{URL::to('/')}}";

        function debounce(func, delay) {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        function isEmptyString(obj) {
            return typeof obj === 'string' && obj.trim() === '';
        }
    </script>
</head>

<body class="font-sans antialiased">
    @if(session('success'))
        <x-toast-success message="{{  session('success') }}" />
    @endif

    @yield('main')
    @livewireScripts

    <!-- Proofing Assets: Bootstrap and necessary plugins -->
    <script src="{{ URL::asset('proofing-assets/vendors/js/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('proofing-assets/js/app.js') }}"></script>
    <!-- END OF Proofing Assets: Bootstrap and necessary plugins -->

    <!-- CoreUI main scripts -->
    @yield('js')
    @stack('scripts')
</body>

</html>