@props([
    'id' => 'id',
    'tabbButton' => '',
    'isActive' => false,
    'route' => '#',
    'click' => null
])
<li class="me-2" role="presentation">
    <button 
        id="{{$id}}-tab" 
        data-tabs-target="#{{$id}}" 
        type="button" 
        role="tab" 
        aria-controls="{{ $id }}" 
        aria-selected="{{$isActive ? 'true' : 'false'}}"
        class="inline-block p-4 border-solid border-x-0 border-t-0 border-primary font-bold tab-button"
        href="{{$route}}"
        @click="{{$click}}"
        >
            {{ $slot }}
    </button>
</li>