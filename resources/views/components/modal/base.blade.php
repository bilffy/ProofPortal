@props(
    [
        'id' => 'defaultModal',
        'title' => 'Modal title',
        'body' => null,
        'footer' => null,
    ]
)
<div 
    id = "{{ $id }}"
    tabindex="-1"
    class="modal hidden overflow-y-auto overflow-x-hidden bg-[#00000060] fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
    role="dialog">
    
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <x-modal.header title="{{ $title }}" id="{{ $id }}" />
            <!-- Modal body -->
            @if($body)
                @component($body)
                @endcomponent
            @endif
            <!-- Modal footer -->
            @if($footer)
                @component($footer)
                @endcomponent
            @endif
        </div>
    </div>
    {{ $slot }}
</div>

