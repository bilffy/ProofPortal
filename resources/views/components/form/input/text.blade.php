@props(['label' => '', 'required' => false])

@if (!empty($label))
    <label class="mb-2" for="">
        {{ $label }}
        @if ($required)
            <span class="text-alert">*</span>
        @endif
    </label>
@endif
<input
    {{ $attributes->merge([
        'ref' => "input",
        'class' => "border rounded-md p-2 border-neutral read-only:opacity-50 read-only:cursor-not-allowed read-only:bg-neutral-300",
    ]) }}
/>