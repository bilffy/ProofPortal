@props(['errorMessage' => ''])

@if (!empty($errorMessage))
    <div>
        <p class="text-sm text-alert">
            {{ $errorMessage }}
        </p>
    </div>
@endif