@props(['href' => '', 'activeNav' => false, 'imageSrc' => ''])

<a
    class="flex items-center pl-4 pr-4 pt-2 pb-2 border-white {{ $activeNav ? 'bg-primary text-white rounded-e-md' : '' }}"
    href="{{ $href }}">
    <img
        width="20px"
        height="20px"
        class="mr-4 fill-purple {{ $activeNav ? 'fill-white' : '' }}"
        src="imageSrc"
        alt="" />
    {{ $slot }}
</a>
