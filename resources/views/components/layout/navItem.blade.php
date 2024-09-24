@props(['href' => '', 'activeNav' => false, 'navIcon' => '', 'id' => ''])

<a
    id="{{ $id }}"
    class="flex gap-2 items-center pl-4 pr-4 pt-2 pb-2 mr-2 border-white {{ $activeNav ? 'bg-primary text-white rounded-e-md' : '' }}"
    href="{{ $href }}">
    <span class=" w-[20px] h-[20px] flex items-center justify-center">
        <x-icon icon="{{ $navIcon }}"/>
    </span>
    {{ $slot }}
</a>
