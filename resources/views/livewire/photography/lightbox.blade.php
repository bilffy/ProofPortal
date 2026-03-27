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
                    // $imageId = $image['id']; // No longer needed, accessed directly
                    // $name = $isFolder ? $image['classGroup'] : $image['portal_firstname'] . ' ' . $image['portal_lastname']; //CODE BY Chromedia
                    // $folderName = $image['year']; // No longer needed, accessed directly
                    // $landscape = !$image['isPortrait']; // No longer needed, accessed directly
                    // $key = "img-lb_{{$imageId}}"; // No longer needed, wire:key used
                    // $isLightbox = true; // No longer needed, passed directly
                    // $isUploaded = $image['isUploaded'] ?? false; // No longer needed, passed directly
                @endphp
                {{-- <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isLightbox :$isUploaded :key="$key" lazy="on-load"/> --}} {{--CODE BY chromedia--}}
                {{--CODE BY IT--}}
                <livewire:photography.image-frame
                    wire:key="lb-{{ $image['id'] }}"
                    :imageId="$image['id']"
                    :name="$image['firstname'] . ' ' . $image['lastname']"
                    :landscape="!$image['isPortrait']"
                    :folderName="$image['classGroup']"
                    :isUploaded="$image['isUploaded']"
                    :hasImage="$image['hasPhoto']"
                    :externalSubjectId="$image['externalSubjectId']"
                    :category="$category"
                    :isLightbox="true"
                    lazy="on-load"
                />
                {{--CODE BY IT--}}
            @endforeach
        </div>
    </div>
</div>
