@props(['href' => '', 'activeNav' => false, 'navIcon' => '', 'id' => '', 'collapsed'])

<a
    id="{{ $id }}"
    class="flex gap-2 items-center pl-4 pr-4 pt-2 pb-2 mr-2  rounded-e-md hover:transition whitespace-nowrap  {{ $activeNav ? 'bg-primary hover:bg-primary hover:text-gray-900' : 'hover:bg-primary-100' }}"
    href="{{ $href }}">
    <span class=" w-[20px] h-[20px] flex items-center justify-center">
        <x-icon icon="{{ $navIcon }}"/>
    </span>
    {{-- <span class="{{ $collapsed? 'hidden':''}}">{{ $slot }}</span> --}}
    <span class="hideOnCollapse">{{ $slot }}</span>
</a>