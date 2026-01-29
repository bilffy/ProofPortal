<div class="w-full text-center">
    <div class="mt-4" wire:loading>
        <x-spinner.icon :size="10"/>
    </div>
    <div wire:loading.remove>
        <div class="grid grid-cols-4 gap-auto" total-image-count="{{ count($images) }}">
            @if (count($images) <= 0)
                <div class="col-span-full">
                    <p class="text-gray-500">No images available.</p>
                </div>
            @endif
            @foreach ($images as $image)
                @php
                    $isFolder = $image['category'] == 'FOLDER';
                    $imageId = $image['id'];
                    // $name = $isFolder ? $image['classGroup'] : $image['firstname'] . ' ' . $image['lastname']; //code by Chromedia
                    $name = ''; //code by IT
                    $folderName = $image['year'];
                    $landscape = !$image['isPortrait'];
                    $key = "img-lb_{{$imageId}}";
                    $isLightbox = true;
                    $isUploaded = $image['isUploaded'] ?? false;
                @endphp
                {{-- <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isLightbox :$isUploaded :key="$key" lazy="on-load"/> --}} {{--code by chromedia--}}
                {{--code by IT--}}
                <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isLightbox :$isUploaded wire:key="lb-{{ $imageId }}"  lazy="on-load"/>
                {{--code by IT--}}
            @endforeach
        </div>
    </div>
</div>
