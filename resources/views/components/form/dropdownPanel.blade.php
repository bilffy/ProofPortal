@props(['id' => 'dropOptions'])
<ul
        {{ $attributes->merge([
            'id' => $id,
        ]) }}
        class="absolute top-10 bg-white rounded shadow right-3 overflow-hidden"        
        role="list"
        hidden 
>
    {{ $slot}}
</ul>