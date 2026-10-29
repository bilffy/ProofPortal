<div>
    <div class="py-2 flex items-center justify-end">
        <a href="{{ route('orders.index') }}" class="text-sm text-primary hover:underline">&larr; Back to Quotes</a>
    </div>

    <div class="od-page">
    {{-- Catchy header banner --}}
    <div class="od-banner">
        <div class="od-banner-top">
            <div class="od-banner-left">
                <p class="od-banner-eyebrow">Print &amp; Artwork Request</p>
                <h2 class="od-banner-title">{{ $order->order_number }}</h2>
                <p class="od-banner-sub">
                    <i class="fas fa-calendar-alt"></i>
                    Submitted {{ $order->created_at?->format('d M Y, g:i a') }}
                </p>
            </div>
            <div class="od-banner-right">
                <!-- @php
                    $statusColour = $order->status->colour_code ?? '#005890';
                @endphp
                <span class="od-status-pill" style="--status-colour: {{ $statusColour }};">
                    <span class="od-status-dot"></span>
                    {{ $order->status->status_external_name ?? 'Unknown' }}
                </span> -->
                @if ($order->product)
                    <span class="od-product-pill">
                        <i class="fas fa-box-open"></i>
                        {{ $order->product->name }}
                    </span>
                @endif

                {{-- Order notification emails: one button per recipient, each
                     showing that email's delivery status. --}}
                <div class="od-email-actions">
                    @foreach ([
                        'print' => ['label' => 'View Print Team Email', 'icon' => 'fa-print'],
                        'requestor' => ['label' => 'View Requestor Email', 'icon' => 'fa-user-check'],
                    ] as $recipient => $meta)
                        @php
                            $orderEmail = $this->orderEmails[$recipient];
                            $emailStatus = $orderEmail?->status;
                            $statusKey = strtoupper((string) ($emailStatus->status_internal_name ?? ''));
                            $emailColour = $emailStatus->colour_code
                                ?? match ($statusKey) {
                                    'EMAIL SENT' => '#16a34a',
                                    'FAILED' => '#dc2626',
                                    'PENDING' => '#d97706',
                                    default => '#64748b',
                                };
                            $emailStatusLabel = $orderEmail
                                ? ($emailStatus->status_external_name ?? $emailStatus->status_internal_name ?? 'Unknown')
                                : 'Not sent';
                        @endphp
                        <button type="button"
                                class="od-email-btn"
                                wire:click="viewEmail('{{ $recipient }}')"
                                @disabled(!$orderEmail)
                                title="{{ $orderEmail ? 'Sent to ' . $orderEmail->email_to : 'No email was recorded for this order' }}">
                            <i class="fas {{ $meta['icon'] }}"></i>
                            <span class="od-email-btn-label">{{ $meta['label'] }}</span>
                            <span class="od-email-status" style="--status-colour: {{ $emailColour }};">
                                <span class="od-status-dot"></span>
                                {{ $emailStatusLabel }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Requested By, folded into the banner as a compact meta strip --}}
        <div class="od-banner-meta">
            <div class="od-meta-item" title="Franchise Name">
                <i class="fas fa-building"></i>
                <span>{{ $this->contactDetails['franchise_name'] ?: '—' }}</span>
            </div>
            <div class="od-meta-item" title="School Name">
                <i class="fas fa-school"></i>
                <span>{{ $this->contactDetails['school_name'] ?: '—' }}</span>
            </div>
            <div class="od-meta-item" title="Contact Name">
                <i class="fas fa-user"></i>
                <span>{{ $this->contactDetails['contact_name'] ?: '—' }}</span>
            </div>
            <div class="od-meta-item" title="Contact Number">
                <i class="fas fa-phone"></i>
                <span>{{ $this->contactDetails['contact_number'] ?: '—' }}</span>
            </div>
            <div class="od-meta-item" title="Email">
                <i class="fas fa-envelope"></i>
                <span>{{ $this->contactDetails['email'] ?: '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Dashboard-style layout: two equal columns in a row, instead of
         full-width panels stacked underneath each other. --}}
    <div class="od-layout">
            {{-- Submitted product details --}}
            <div class="od-card">
                <h5 class="od-card-title">
                    Submitted Details
                </h5>

                @if (count($this->fieldRows))
                    <div class="od-columns">
                        @foreach ($this->fieldRows as $row)
                            <div class="od-tile">
                                <p class="od-info-label">{{ $row['field']->label }}</p>
                                <p class="od-info-value">{{ $row['value'] ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="od-empty">
                        <i class="fas fa-circle-info"></i>
                        <p>No fields were configured for this product at the time of order.</p>
                    </div>
                @endif
            </div>

            {{-- Artwork --}}
            <div class="od-card">
                <h5 class="od-card-title">
                    Artwork
                </h5>

                <div class="od-columns">
                    <div class="od-tile">
                        <p class="od-info-label">Artwork Required?</p>
                        @php
                            $artworkRequiredLabel = $order->artworkRequired->details ?? null;
                            $artworkRequiredNorm = $artworkRequiredLabel ? strtolower(trim($artworkRequiredLabel)) : null;
                            $artworkRequiredTone = match (true) {
                                in_array($artworkRequiredNorm, ['yes', 'y', 'true'], true) => 'yes',
                                in_array($artworkRequiredNorm, ['no', 'n', 'false'], true) => 'no',
                                default => 'neutral',
                            };
                        @endphp
                        @if ($artworkRequiredLabel)
                            <span class="od-chip od-chip-{{ $artworkRequiredTone }}">{{ $artworkRequiredLabel }}</span>
                        @else
                            <p class="od-info-value">—</p>
                        @endif
                    </div>

                    <div class="od-tile">
                        <p class="od-info-label">Due Date</p>
                        <p class="od-info-value">{{ $order->artwork_due_date?->format('d/m/Y') ?: '—' }}</p>
                    </div>

                    <div class="od-tile">
                        <p class="od-info-label">Additional Comments</p>
                        <p class="od-info-value" style="white-space: pre-line;">{{ $order->additional_comments ?: '—' }}</p>
                    </div>
                </div>

                {{-- Artwork Brief and Uploaded Files need more room than a
                     1-of-3 tile (rich text / file thumbnails), so they get
                     their own two-up row instead of sharing the 3-column
                     grid above. --}}
                <div class="od-columns od-columns-2">
                    <div class="od-tile">
                        <p class="od-info-label">Artwork Brief</p>
                        @if ($order->artwork_brief)
                            <div class="od-info-value od-rich-text">{!! $order->artwork_brief !!}</div>
                        @else
                            <p class="od-info-value">—</p>
                        @endif
                    </div>

                    <div class="od-tile">
                        <p class="od-info-label">Uploaded Files</p>
                        @if ($order->artworkFiles->count())
                            <div class="od-file-gallery">
                                @foreach ($order->artworkFiles as $file)
                                    @if (! $file->file_exists)
                                        <div class="od-file-thumb" title="{{ $file->original_name }} - removed after 90 days" style="opacity: .55; cursor: default;">
                                            <span class="od-file-thumb-icon"><i class="fas fa-clock"></i></span>
                                            <span class="od-file-thumb-name">{{ $file->original_name }} (expired)</span>
                                        </div>
                                        @continue
                                    @endif
                                    <a href="{{ $file->url }}" target="_blank" rel="noopener" class="od-file-thumb" title="{{ $file->original_name }} ({{ $file->human_size }})">
                                        @if ($file->is_image)
                                            <img src="{{ $file->url }}" alt="{{ $file->original_name }}">
                                        @else
                                            <span class="od-file-thumb-icon"><i class="fas {{ $file->file_icon }}"></i></span>
                                        @endif
                                        <span class="od-file-thumb-name">{{ $file->original_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="od-empty-inline"><i class="fas fa-folder-open"></i> No files were uploaded.</span>
                        @endif
                    </div>
                </div>
            </div>
    </div>
    </div>

    {{-- Email preview modal (Livewire-driven) --}}
    @if ($this->viewedEmail)
        @php
            $viewed = $this->viewedEmail['email'];
            $viewedStatus = $viewed->status->status_external_name ?? $viewed->status->status_internal_name ?? '—';
        @endphp
        <div class="od-modal-backdrop" wire:click.self="closeEmail" wire:keydown.escape.window="closeEmail">
            <div class="od-modal" role="dialog" aria-modal="true" aria-label="Order email preview">
                <div class="od-modal-head">
                    <h3 class="od-modal-title">
                        {{ $viewingEmail === 'print' ? 'Print Team Email' : 'Requestor Email' }}
                        <span class="od-modal-sub">{{ $order->order_number }}</span>
                    </h3>
                    <button type="button" class="od-modal-close" wire:click="closeEmail" aria-label="Close">&times;</button>
                </div>
                <div class="od-modal-meta">
                    <p><strong>To:</strong> {{ $viewed->email_to }}</p>
                    <p><strong>From:</strong> {{ $viewed->email_from }}</p>
                    <p><strong>Status:</strong> {{ $viewedStatus }}</p>
                    <p><strong>Created:</strong> {{ $viewed->created_at?->format('d M Y, g:i a') ?? '—' }}</p>
                    <p><strong>Sent:</strong> {{ $viewed->sentdate ? \Carbon\Carbon::parse($viewed->sentdate)->format('d M Y, g:i a') : '—' }}</p>
                    @if ($viewed->smtp_message)
                        <p><strong>SMTP:</strong> {{ $viewed->smtp_code }} {{ $viewed->smtp_message }}</p>
                    @endif
                </div>
                <div class="od-modal-body">
                    <iframe title="Email body"
                            sandbox="allow-popups allow-popups-to-escape-sandbox"
                            srcdoc="{{ $this->viewedEmail['html'] }}"></iframe>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
    <style>
        /* Soft wash behind the banner + cards so the white tiles read as
           a distinct dashboard surface rather than blending into the
           page's own white background. */
        .od-page {
            background: #f4f8fa;
            border-radius: 16px;
            padding: 20px;
        }

        @media (max-width: 640px) {
            .od-page {
                padding: 14px;
            }
        }

        .od-banner {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 24px 28px;
            margin-bottom: 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 60%, #eef6fa 100%);
            border: 1px solid #dcebf2;
            box-shadow: 0 8px 20px rgba(0, 88, 144, 0.06);
            position: relative;
            overflow: hidden;
        }

        .od-banner-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        /* Requested By, folded into the banner as a compact meta strip
           rather than its own card - a thin divider separates it from
           the title/status row above. */
        .od-banner-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 24px;
            padding-top: 14px;
            border-top: 1px solid #dcebf2;
        }

        .od-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #44565f;
            min-width: 0;
        }

        .od-meta-item i {
            flex: 0 0 auto;
            width: 14px;
            text-align: center;
            color: #005890;
            font-size: 13px;
        }

        .od-meta-item span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 220px;
        }

        @media (max-width: 640px) {
            .od-meta-item span {
                max-width: none;
                white-space: normal;
            }
        }

        .od-banner::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #005890, #669bbc);
        }

        .od-banner-eyebrow {
            margin: 0 0 4px 0;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #005890;
        }

        .od-banner-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1c2a33;
            line-height: 1.2;
        }

        .od-banner-sub {
            margin: 8px 0 0 0;
            font-size: 13px;
            color: #5b6b74;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .od-banner-sub i {
            color: #005890;
        }

        .od-banner-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .od-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            color: #1c2a33;
            background: color-mix(in srgb, var(--status-colour) 14%, white);
            border: 1px solid color-mix(in srgb, var(--status-colour) 30%, white);
            white-space: nowrap;
        }

        .od-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--status-colour);
            flex: 0 0 auto;
        }

        .od-email-actions {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            margin-top: 4px;
        }

        .od-email-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #005890;
            background: #ffffff;
            border: 1px solid #b9d6e6;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s, border-color 0.15s;
        }

        .od-email-btn:hover:not(:disabled) {
            background: #eef6fa;
            border-color: #669bbc;
        }

        .od-email-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .od-email-btn-label {
            flex: 1 1 auto;
            text-align: left;
        }

        .od-email-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 10px 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            color: #1c2a33;
            background: color-mix(in srgb, var(--status-colour) 14%, white);
            border: 1px solid color-mix(in srgb, var(--status-colour) 30%, white);
        }

        .od-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 30, 40, 0.55);
        }

        .od-modal {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 820px;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .od-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid #dcebf2;
        }

        .od-modal-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1c2a33;
        }

        .od-modal-sub {
            margin-left: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #5b6b74;
        }

        .od-modal-close {
            border: 0;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            color: #5b6b74;
            cursor: pointer;
        }

        .od-modal-meta {
            padding: 12px 20px;
            font-size: 13px;
            color: #44565f;
            background: #f7fbfd;
            border-bottom: 1px solid #dcebf2;
        }

        .od-modal-meta p {
            margin: 0 0 3px 0;
        }

        .od-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .od-modal-body iframe {
            display: block;
            width: 100%;
            height: 60vh;
            border: 0;
        }

        @media (max-width: 640px) {
            .od-banner-right {
                align-items: stretch;
                width: 100%;
            }
        }

        .od-product-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: #005890;
            background: #eef6fa;
            border: 1px solid #dcebf2;
            white-space: nowrap;
        }

        /* Dashboard-style page layout: Submitted Details stacked above
           Artwork, full width, rather than side by side - Artwork tends
           to run longer (brief, files) and reads better with the full
           row to itself underneath. */
        .od-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .od-card {
            position: relative;
            min-width: 0;
            padding: 18px 20px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e5edf1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .od-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef3f5;
            font-size: 16px;
            font-weight: 600;
            color: #1c2a33;
        }

        .od-card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #eef6fa;
            color: #005890;
            font-size: 13px;
            flex: 0 0 auto;
        }

        .od-info-label {
            margin: 0 0 2px 0;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #6b8a9a;
        }

        .od-info-value {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            color: #232b2b;
            word-break: break-word;
        }

        /* Tiles inside Submitted Details / Artwork arranged three across
           per row. align-items: start keeps each tile sized to its own
           content rather than stretching every tile in a row to match
           the tallest one (e.g. a long Artwork Brief next to short Due
           Date / Artwork Required tiles) - that stretching would itself
           read as empty space padded out inside the shorter tiles. */
        .od-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: start;
            gap: 12px;
        }

        @media (max-width: 900px) {
            .od-columns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .od-columns {
                grid-template-columns: 1fr;
            }
        }

        .od-columns-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 12px;
        }

        @media (max-width: 560px) {
            .od-columns-2 {
                grid-template-columns: 1fr;
            }
        }

        .od-tile {
            box-sizing: border-box;
            min-width: 0;
            padding: 12px 14px;
            border-radius: 10px;
            background: #eef6fa;
            border: 1px solid #dcebf2;
        }

        /* Empty states: a dashed placeholder box when a whole card has
           nothing to show, and a muted inline variant for a single tile
           (e.g. no files uploaded) that doesn't need its own box. */
        .od-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 32px 16px;
            border: 1px dashed #d7e3ea;
            border-radius: 10px;
            color: #8294a0;
            text-align: center;
        }

        .od-empty i {
            font-size: 20px;
            color: #a9c0cc;
        }

        .od-empty p {
            margin: 0;
            font-size: 13px;
        }

        .od-empty-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #8294a0;
            font-size: 13px;
            font-weight: 500;
        }

        .od-empty-inline i {
            color: #a9c0cc;
        }

        /* Artwork Required?, colour-coded when the answer is a plain
           yes/no so it reads at a glance; anything else falls back to
           the same neutral tone used elsewhere on the page. */
        .od-chip {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }

        .od-chip-yes {
            background: #e7f7ec;
            color: #1a7a3b;
            border: 1px solid #c9ecd4;
        }

        .od-chip-no {
            background: #fdeeee;
            color: #b3261e;
            border: 1px solid #f6d3d1;
        }

        .od-chip-neutral {
            background: #eef6fa;
            color: #005890;
            border: 1px solid #dcebf2;
        }

        .od-rich-text {
            font-weight: 400;
        }

        .od-rich-text p {
            margin: 0 0 8px 0;
        }

        .od-rich-text p:last-child {
            margin-bottom: 0;
        }

        /* Small grouped thumbnails, matching the compact group-image
           style used on the Configure Job page (fixed small box,
           object-fit: contain, thin border) rather than large previews. */
        .od-file-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .od-file-thumb {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100px;
            text-decoration: none;
        }

        .od-file-thumb img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border: 1px solid #ddd;
            padding: 2px;
            border-radius: 4px;
            background: #ffffff;
            box-sizing: border-box;
        }

        .od-file-thumb-icon {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            padding: 2px;
            border-radius: 4px;
            background: #ffffff;
            box-sizing: border-box;
            color: #005890;
            font-size: 28px;
        }

        .od-file-thumb-name {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            color: #232b2b;
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }
    </style>
@endpush
