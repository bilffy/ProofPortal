<div class="flex items-center justify-between">
    <span>{{ $field->label }}</span>
    <span class="text-xs text-neutral">{{ \App\Models\CustomField::TYPES[$field->type] ?? $field->type }}</span>
</div>

{{-- Dropdown / radio / checkbox fields: pick which options this product shows.
     Ticked = shown. All ticked = no limit. Only visible once the field is in the
     Assigned list (see .pf-opts rule); the picker JS reads these boxes. --}}
@if (in_array($field->type, \App\Models\CustomField::OPTION_TYPES, true))
    @php
        $pfOptions = $field->fieldOptions->filter(fn ($o) => (string) $o->option_value !== '')->values();
        $pfAllowed = $allowedOptions[$field->id] ?? null;
    @endphp
    @if ($pfOptions->count() > 1)
        <details class="pf-opts mt-2">
            <summary class="text-xs text-primary cursor-pointer">
                Options shown for this product (<span data-pf-count></span>)
            </summary>
            <div class="mt-2 flex flex-col gap-1 cursor-default">
                @foreach ($pfOptions as $option)
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="checkbox" data-pf-option value="{{ $option->option_value }}" class="h-3.5 w-3.5 rounded border-neutral text-primary focus:ring-primary" @checked($pfAllowed === null || in_array((string) $option->option_value, array_map('strval', $pfAllowed), true)) />
                        {{ $option->option_name }}
                    </label>
                @endforeach
                <p class="mt-1 mb-0 text-xs text-neutral">Untick the options this product should not show. Leave all ticked to show every option.</p>
            </div>
        </details>
    @endif
@endif
