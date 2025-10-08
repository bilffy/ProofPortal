@props([
    'id' => 'id',
    'name' => 'Name',
    'folderName' => '',
    'active' => false,
    'landscape' => false,
    'event' => null,
    'payload' => null,
    'img' => null,
    'hasImage' => false,
    'isLightbox' => false,
    'isUploaded' => false,
])

@php
    $decodedName = html_entity_decode($name, ENT_QUOTES);
    $decodedFolder = html_entity_decode($folderName, ENT_QUOTES);
    $imgId = $isLightbox ? 'img-lb_' . $id : 'img_' . $id;
    $noImage = empty($hasImage) || !$hasImage;
@endphp

<div id="{{ $imgId }}" class="portrait-img {{ $isUploaded ? 'uploaded' : ''}} rounded-md w-[186] px-2 pt-2 flex flex-col align-middle relative justify-center hover:cursor-pointer" onclick="handleImageClick('{{ $imgId }}', {{ $isLightbox ? 'true' : 'false' }}, {{ $hasImage ? 'true' : 'false' }})">
    <div class="relative h-[229px] overflow-hidden rounded group transition-all">
        <div class="absolute flex w-full justify-end pr-2 pt-2 z-10 {{ $hasImage ? "img-checkbox" . ($isLightbox ? "" : " hidden") : 'hidden' }}">
            <div class="{{ $hasImage ? "portrait-img-checkbox" : "img-not-found"}} group transition-all 
                        w-[24px] h-[24px] p-1 pt-[3px] border-white border-2
                        flex align-middle justify-center rounded-full 
                        hover:bg-primary-100">
                <x-icon icon="check text-primary hidden"/>
            </div>
        </div>
        @if ($isUploaded)
            <div class="absolute m-2 z-10 bottom-0 end-0">
                <div class="relative w-fit h-6 overflow-hidden group flex space-x-1">
                    <div class="flex items-center justify-center w-6 h-6 bg-tertiary rounded-md invisible 
                        {{ $isLightbox
                            ? "hover:bg-tertiary-hover group-hover:visible transition-transform translate-x-full duration-300 group-hover:translate-x-0 z-5"
                            : ""
                        }}"
                        @if($isLightbox) onclick="showRemovePhotoModal(event, '{{ $imgId }}', '{{ $decodedName }}')" @endif
                    >
                        <x-icon icon="trash" class="text-white w-6 h-6 text-sm mt-1"/>
                    </div>
                    <div class="flex items-center justify-center w-6 h-6 bg-primary rounded-md z-10 {{ $isLightbox ? "hover:bg-primary-hover" : "" }}"
                        @if($isLightbox) onclick="replaceUploadedPhoto(event, '{{ $imgId }}')" @endif
                    >
                        <x-icon icon="pencil" class="text-white w-6 h-6 text-sm mt-1"/>
                    </div>
                </div>
            </div>
        @endif
        <div id="img-content-{{ $id }}" class="h-full w-full">
            @if ('' == $img)
                <x-spinner.image />
            @else
                <div class="flex items-center h-full bg-[#E6E7E8] group {{ $hasImage ? 'hover:scale-[1.05] hover:transition-all' : '' }}">
                    @if ($isLightbox && $noImage)
                        <div class="absolute inset-0 flex items-center justify-center z-10 invisible group-hover:visible">
                            <span class="text-white font-semibold text-xl">+ Add Photo</span>
                        </div>
                    @endif
                    @php
                        $imageData = base64_decode($img);
                        $imageInfo = getimagesizefromstring($imageData);
                        $mimeType = $imageInfo['mime'] ?? 'image/jpeg';
                    @endphp
                    <img 
                        src="data:{{$mimeType}};base64,{{$img}}"
                        alt=""
                        class="w-full max-w-none {{ ($isLightbox && $noImage) ? 'group-hover:brightness-[70%]' : '' }}"
                    />
                </div>
            @endif
        </div>
    </div>
    <div class="justify-between py-2 text-sm" data-toggle="tooltip" title="{{ $decodedName . "\n" . $decodedFolder }}">
        <div>
            <span class="flex truncate font-semibold img-decoded-name {{ $isLightbox ? "hidden" : "" }}">{{$decodedName}}</span>
            @if ('' != $decodedFolder)
                <span class="flex text-gray-500 truncate {{ $isLightbox ? "justify-center" : "" }}">{{$decodedFolder}}</span>
            @endif
        </div>
    </div>
</div>
