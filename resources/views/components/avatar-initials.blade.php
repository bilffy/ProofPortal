@props(['id' => null, 'text' => null])

<div id="{{ $id }}" class="relative inline-flex items-center justify-center w-10 h-10 overflow-hidden bg-primary rounded-full">
    <span class="font-medium bg-primary text-white">{{ $text }}</span>
</div>   