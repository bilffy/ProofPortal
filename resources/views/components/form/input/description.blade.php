@props(['description' => ''])

@if (!empty($description))
    <div>
        <p class="text-sm">
            {{ $description }}
        </p>
    </div>
@endif