@props(
    [
        'id' => 'lightboxModal',
        'title' => 'Modal title',
        'body' => null,
        'footer' => null,
    ]
)
<div 
    id = "{{ $id }}"
    tabindex="-1"
    class="modal hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[45] justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
    role="dialog"
    >
    <div class="modal-dialog relative p-4 w-full max-w-4xl max-h-full" role="document">
        <!-- Modal content -->
        <div class="modal-content relative bg-white rounded-lg shadow">
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

