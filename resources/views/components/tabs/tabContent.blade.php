@props(['id' => 'id'])

<div 
    class="hidden py-4" 
    id="{{$id}}" 
    role="tabpanel" 
    aria-labelledby="{{$id}}-tab">
        {{ $slot }}
</div>