@props(['placeholder' => '', 'inputType' => 'text', 'labelText' => '', 'value' => ''])

<div
    {{ $attributes->merge([
        'class' => "flex flex-col mb-4"
    ]) }}
>
    @if (!empty($labelText))
        <label class="mb-2" for="">{{ $labelText }}</label>
    @endif
    <input
        class="border rounded-md p-2 border-neutral"
        placeholder="{{ $placeholder }}"
        ref="input"
        type="{{ $inputType }}"
        value="{{ $value }}"
    />
</div>