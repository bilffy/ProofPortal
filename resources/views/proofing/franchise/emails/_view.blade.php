<div class="mb-3">
    <p class="mb-1"><strong>{{ __('To') }}:</strong> {{ $email->email_to }}</p>
    <p class="mb-1"><strong>{{ __('From') }}:</strong> {{ $email->email_from }}</p>
    @if ($email->email_cc)
        <p class="mb-1"><strong>{{ __('CC') }}:</strong> {{ $email->email_cc }}</p>
    @endif
    <p class="mb-1"><strong>{{ __('Template') }}:</strong> {{ $email->template->external_template_name ?? '—' }}</p>
    <p class="mb-1"><strong>{{ __('Status') }}:</strong> {{ $email->status->status_external_name ?? $email->status->status_internal_name ?? '—' }}</p>
    <p class="mb-1"><strong>{{ __('Created') }}:</strong> {{ $email->created_at ? $email->created_at->format('Y-m-d H:i') : '—' }}</p>
    <p class="mb-3"><strong>{{ __('Sent') }}:</strong> {{ $email->sentdate ? \Carbon\Carbon::parse($email->sentdate)->format('Y-m-d H:i') : '—' }}</p>
</div>

<div class="border rounded bg-white" style="max-height: 60vh; overflow: auto;">
    <iframe
        title="{{ __('Email body') }}"
        sandbox="allow-popups allow-popups-to-escape-sandbox"
        style="width: 100%; min-height: 420px; border: 0;"
        srcdoc="{!! htmlspecialchars($htmlBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') !!}"
    ></iframe>
</div>
