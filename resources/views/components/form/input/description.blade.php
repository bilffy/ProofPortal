@props(['description' => ''])

@if (!empty($description))
    <div {{ $attributes->merge([
        'class' => "mt-1"
    ]) }}>
        <p class="text-sm mb-2">
            {{ $description }}
        </p>
    </div>
@endif