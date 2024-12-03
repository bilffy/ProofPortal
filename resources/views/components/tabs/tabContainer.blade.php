@props(['tabsWrapper' => 'default-tab-content'])
<ul 
    class="flex flex-wrap text-sm font-medium text-center mb-4 relative" 
    id="default-tab" 
    data-tabs-toggle="#{{$tabsWrapper}}"
    data-tabs-active-classes="text-primary border-b-2" 
    data-tabs-inactive-classes="text-gray hover:text-primary border-b-0" 
    role="tablist">
        {{ $slot }}
</ul>