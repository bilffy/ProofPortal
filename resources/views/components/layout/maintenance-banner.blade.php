@php
    $maintenance = config('app.maintenance_banner');
    $from = trim((string) ($maintenance['from'] ?? ''));
    $to = trim((string) ($maintenance['to'] ?? ''));
    $show = ($maintenance['enabled'] ?? false) && $from !== '' && $to !== '';
@endphp

@if ($show)
    <div
        class="maintenance-banner w-full border-b px-4 py-2.5 text-center text-sm font-semibold shrink-0"
        style="background-color: #fee2e2; color: #FF0000; border-color: #fecaca;"
        role="status"
    >
        Please be advised that the MSP portal will be undergoing scheduled maintenance from {{ $from }} to {{ $to }}
    </div>
@endif
