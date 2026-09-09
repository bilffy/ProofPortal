@php
    $platform = strtolower((string) (config('app.platform') ?: 'production'));

    $banner = match ($platform) {
        'uat' => [
            'text' => 'SYSTEM IS IN UAT ENVIRONMENT',
            // Inline styles so colours always render (Tailwind may purge unused utility classes).
            'style' => 'background-color: #fee2e2; color: #991b1b; border-color: #fecaca;',
        ],
        'staging' => [
            'text' => 'SYSTEM IS IN STAGING ENVIRONMENT',
            'style' => 'background-color: #fef3c7; color: #92400e; border-color: #fde68a;',
        ],
        default => null,
    };
@endphp

@if ($banner)
    <div
        class="environment-banner w-full border-b px-4 py-2.5 text-center text-sm font-semibold shrink-0"
        style="{{ $banner['style'] }}"
        role="status"
    >
        {{ $banner['text'] }}
    </div>
@endif
