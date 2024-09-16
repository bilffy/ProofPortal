@props([
    'id' => 'id',
    'active' => false
])
<li class="me-2" role="presentation">
    <button

            id="{{$id}}-tab"
            data-tabs-target="#{{$id}}"
            type="button"
            role="tab"
            aria-controls="{{ $id }}"
            aria-selected="false"
            {{
                $attributes->merge([
                    'class'=>"inline-block p-4 border-solid border-x-0 border-t-0 border-primary font-bold" . ($active? "border-b-2":"")
                ])
            }}
    >
        {{ $slot }}
    </button>
</li>