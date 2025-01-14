<div class="w-full">
    <div x-data="{ selectedImages: @entangle('selectedImages') }" class="grid grid-cols-[repeat(auto-fit,195px)] gap-auto">
        @foreach ($paginatedImages as $image)
            <x-photography.portrait
                event="'{{$PhotographyHelper::EV_SELECT_IMAGE}}'"
                payload="{imageKey: '{{$image['id']}}'}"
                active="{{in_array($image['id'], $selectedImages)}}"
                img="{{$image['base64']}}"
                name="{{$image['firstname']}} {{$image['lastname']}} - {{$image['classGroup']}}"
                filename="{{$image['filename']}}"
                landscape="{{array_key_exists('isPortrait', $image) && !$image['isPortrait']}}"
            />
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
