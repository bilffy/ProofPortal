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
])

@php
    $decodedName = html_entity_decode($name, ENT_QUOTES);
    $decodedFolder = html_entity_decode($folderName, ENT_QUOTES);
    $imgId = $isLightbox ? 'img-lb_' . $id : 'img_' . $id;
@endphp

<div id="{{ $imgId }}" class="portrait-img rounded-md w-[186] px-2 pt-2 flex flex-col align-middle relative justify-center hover:cursor-pointer" onclick="handleImageClick('{{ $imgId }}', {{ $isLightbox ? 'true' : 'false' }}, {{ $hasImage ? 'true' : 'false' }}, '{{ $decodedName }}', '{{ $decodedFolder }}')">
    <div class="relative h-[229px] overflow-hidden rounded">
        <div class="absolute flex w-full justify-end pr-2 pt-2 z-10 {{ $hasImage ? "img-checkbox" . ($isLightbox ? "" : " hidden") : 'hidden' }}">
            <div class="{{ $hasImage ? "portrait-img-checkbox" : "img-not-found"}} group transition-all 
                        w-[24px] h-[24px] p-1 pt-[3px] border-white border-2
                        flex align-middle justify-center rounded-full 
                        hover:bg-primary-100">
                <x-icon icon="check text-primary group-hover:text-white hidden"/>
            </div>
        </div>
        @if ('' == $img)
            <x-spinner.image />
        @else
            <div class="flex items-center h-full bg-[#E6E7E8] {{ $hasImage ? 'hover:scale-[1.05] hover:transition-all' : '' }}">
                <img 
                    src="data:image/jpeg;base64,{{$img}}"
                    alt=""
                    class="w-full max-w-none"
                />
            </div>
        @endif
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
