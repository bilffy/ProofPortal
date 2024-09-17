@props(['placeholder' => '', 'inputType' => 'text', 'labelText' => '', 'name' => '', 'value' => '', 'required' => false, 'description' => '', 'errorMessage' => ''])

<div
    {{ $attributes->merge([
        'class' => "flex flex-col mb-4"
    ]) }}
>
    @if (!empty($labelText))
        <label class="mb-2" for="">
            {{ $labelText }}
            @if ($required)
                <span class="text-alert">*</span>
            @endif
        </label>
    @endif
    <input
        name="{{ $name }}"
        class="border rounded-md p-2 border-neutral"
        placeholder="{{ $placeholder }}"
        ref="input"
        type="{{ $inputType }}"
        value="{{ $value }}"
        required="{{ $required }}"
    />
    <x-form.input.error class="mt-1 mb-2" errorMessage="{{$errorMessage}}" />
    <x-form.input.description class="mt-1 mb-2" description="{{$description}}" />
</div>