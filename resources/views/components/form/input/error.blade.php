@props(['errorMessage' => ''])

@if (!empty($errorMessage))
    <div {{ $attributes->merge([
        'class' => "mt-1"
    ]) }}>
        <p class="text-sm text-alert mb-0">
            {{ $errorMessage }}
        </p>
    </div>
@endif