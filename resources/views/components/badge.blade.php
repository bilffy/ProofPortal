@props(['text' => null, 'badge' => 'gray'])

<span class="inline-flex items-center px-2 py-1 me-2 text-sm font-medium text-{{ $badge }}-800 bg-{{ $badge }}-100 rounded dark:bg-{{ $badge }}-900 dark:text-{{ $badge }}-300">   
    {{ $text }}
</span>