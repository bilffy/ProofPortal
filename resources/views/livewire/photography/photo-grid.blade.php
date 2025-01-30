<div class="w-full text-center">
    <div class="mt-4" wire:loading>
        <x-spinner.icon :size="10"/>
    </div>
    <div wire:loading.remove>
        <div class="grid grid-cols-[repeat(auto-fit,195px)] gap-auto" total-image-count="{{ $paginatedImages->total() }}">
            @foreach ($paginatedImages as $image)
                @php
                    $imageId = $image['id'];
                    $name = $image['firstname'] . ' ' . $image['lastname'] . ' - ' . $image['classGroup'];
                    $landscape = !$image['isPortrait'];
                    $key = "img_{{$imageId}}";
                @endphp
                <livewire:photography.image-frame :$imageId :$name :$landscape :key="$key" lazy="on-load"/>
            @endforeach
        </div>
        <div class="mt-4 mb-4 flex justify-center">
            <div>
                @if (count($paginatedImages) == 0)
                    No images found
                @else
                    {{ $paginatedImages->onEachSide(1)->links('vendor.livewire.pagination') }}
                @endif
            </div>
        </div>
    </div>
</div>
