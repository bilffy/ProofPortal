<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        @inertiaHead
        @vite('resources/js/app.ts')
        @vite('resources/css/app.scss')
        @vite('resources/css/output.scss')
    </head>
    <body>
        @inertia
    </body>
</html>