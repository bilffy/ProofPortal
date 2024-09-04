@props(['errorMessage' => ''])

@if (!empty($errorMessage))
    <div>
        <p class="text-sm text-red-600">
            {{ $errorMessage }}
        </p>
    </div>
@endif