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

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        window.addEventListener('show-toast-message', e => {
            const {status, message} = e.detail;
            const safeMessage = escapeHtml(message);
            const id = `toast-${status}-${Math.random().toString(36).substr(2, 9)}` ;
            const color = status === 'success' ? 'green' : 'red';
            const iconPath = status === 'success' 
                ? `<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>`
                : `<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.536 13.536a1 1 0 0 1-1.414 0L10 11.914l-2.122 2.122a1 1 0 0 1-1.414-1.414L8.586 10 6.464 7.879a1 1 0 0 1 1.414-1.414L10 8.586l2.122-2.122a1 1 0 1 1 1.414 1.414L11.414 10l2.122 2.122a1 1 0 0 1 0 1.414Z"/>`;
            const wrapper = document.getElementById(`toast-wrapper`);
            if (!wrapper) return;
            const html = 
                `<div id="${id}" class="z-[100] flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 fixed top-5 right-5" role="alert">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-${color}-500 bg-${color}-100 rounded-lg dark:bg-${color}-800 dark:text-${color}-200">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            ${iconPath}
                        </svg>
                        <span class="sr-only">${status === 'success' ? 'Check' : 'Error'} icon</span>
                    </div>
                    <div class="ms-3 text-sm font-normal">${safeMessage}</div>
                </div>`;
            wrapper.insertAdjacentHTML('beforeend', html);
            const toastEl = document.getElementById(id);
            if (toastEl) {
                $(toastEl).hide().fadeIn(150).delay(4000).fadeOut(300, function () {
                    this.remove();
                });
            }
        });
    </script>
</head>

<body class="font-sans antialiased">
    <div id="toast-wrapper"></div>
    @if(session('success'))
        <x-toast-success message="{{  session('success') }}" />
    @elseif(session('error'))
        <x-toast-error message="{{  session('error') }}" />
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