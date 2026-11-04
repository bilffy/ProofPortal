@if ($messages->count())
    <div class="table-responsive">
        <table id="emails-messages-table" class="table">
            @php
                $sortBy = $sortBy ?? 'created_at';
                $sortDir = $sortDir ?? 'descending';
                $sortHead = function (string $key, string $label) use ($sortBy, $sortDir) {
                    $arrow = $sortBy === $key
                        ? '<i class="fa fa-arrow-' . ($sortDir === 'ascending' ? 'up' : 'down') . ' fa-sm"></i> '
                        : '';

                    return '<th scope="col" class="emails-sortable" data-sort="' . $key . '">' . $arrow . e($label) . '</th>';
                };
            @endphp
            <thead>
                <tr>
                    @if ($showOrder ?? false)
                        {!! $sortHead('order_number', __('Order')) !!}
                    @endif
                    {!! $sortHead('created_at', __('Created')) !!}
                    {!! $sortHead('sentdate', __('Sent')) !!}
                    {!! $sortHead('email_to', __('To')) !!}
                    {!! $sortHead('email_from', __('From')) !!}
                    {!! $sortHead('status_name', __('Status')) !!}
                    <th scope="col">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($messages as $message)
                    <tr>
                        @if ($showOrder ?? false)
                            <td>{{ $message->order->order_number ?? '—' }}</td>
                        @endif
                        <td>{{ $message->created_at ? $message->created_at->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ $message->sentdate ? \Carbon\Carbon::parse($message->sentdate)->format('Y-m-d H:i') : '—' }}</td>
                        <td>{{ $message->email_to }}</td>
                        <td>{{ $message->email_from }}</td>
                        <td>{{ $message->status->status_external_name ?? $message->status->status_internal_name ?? '—' }}</td>
                        <td>
                            <a
                                href="#"
                                class="btn btn-link p-0 js-view-email"
                                data-message-id="{{ $message->id }}"
                            >
                                {{ __('View') }}
                            </a>
                            <span class="text-muted mx-1">|</span>
                            <a
                                href="#"
                                class="btn btn-link p-0 js-resend-email"
                                data-message-id="{{ $message->id }}"
                            >
                                {{ __('Resend') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($messages, 'hasPages'))
        @php
            $current = $messages->currentPage();
            $last = $messages->lastPage();
            $pageList = [];
            if ($last <= 7) {
                $pageList = range(1, $last);
            } else {
                $shown = collect([1, 2, $last - 1, $last, $current - 1, $current, $current + 1])
                    ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
                $prevShown = 0;
                foreach ($shown as $p) {
                    if ($prevShown && $p - $prevShown > 1) {
                        $pageList[] = '...';
                    }
                    $pageList[] = $p;
                    $prevShown = $p;
                }
            }
        @endphp
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="emails-pager">
            @if ($messages->onFirstPage())
                <span class="emails-page-btn emails-page-nav is-disabled"><i class="fa fa-angle-left"></i> {{ __('Prev') }}</span>
            @else
                <a href="#" data-page="{{ $current - 1 }}" class="emails-page-btn emails-page-nav"><i class="fa fa-angle-left"></i> {{ __('Prev') }}</a>
            @endif

            @foreach ($pageList as $p)
                @if ($p === '...')
                    <span class="emails-page-gap">...</span>
                @elseif ($p === $current)
                    <span class="emails-page-btn is-current" aria-current="page">{{ $p }}</span>
                @else
                    <a href="#" data-page="{{ $p }}" class="emails-page-btn">{{ $p }}</a>
                @endif
            @endforeach

            @if ($messages->hasMorePages())
                <a href="#" data-page="{{ $current + 1 }}" class="emails-page-btn emails-page-nav">{{ __('Next') }} <i class="fa fa-angle-right"></i></a>
            @else
                <span class="emails-page-btn emails-page-nav is-disabled">{{ __('Next') }} <i class="fa fa-angle-right"></i></span>
            @endif
        </nav>
    @endif
@else
    <div class="text-center py-4">
        <i class="fa fa-envelope-o fa-3x text-muted"></i>
        <p class="mt-3 mb-0">{{ __('No emails found.') }}</p>
    </div>
@endif
