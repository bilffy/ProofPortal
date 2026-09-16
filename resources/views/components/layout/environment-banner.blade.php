@php
    $platform = strtolower((string) (config('app.platform') ?: 'production'));

    $banner = match ($platform) {
        'local' => [
            'text'  => 'LOCAL SYSTEM ENVIRONMENT',
            'style' => 'background-color: #d1fae5; color: #065f46; border-color: #a7f3d0;',
        ],
        'uat' => [
            'text' => 'UAT SYSTEM ENVIRONMENT',
            'style' => 'background-color: #fee2e2; color: #991b1b; border-color: #fecaca;',
        ],
        'staging' => [
            'text' => 'STAGING SYSTEM ENVIRONMENT',
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
