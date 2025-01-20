@props([
    'id' => 'id',
    'name' => 'Name',
    'active' => false,
    'landscape' => false,
    'event' => null,
    'payload' => null,
    'img' => null,
    'filename' => 'NOT_FOUND' // For testing only, remove this line once the image issue is fixed 
])

<div id="img_{{$id}}" class="portrait-img rounded-md w-[186] px-2 pt-2 flex flex-col align-middle relative justify-center {{ $landscape ? 'col-span-2 ':'' }}">
    <div class="relative h-[229px] overflow-hidden rounded" onclick="handleImageClick('img_{{ $id }}')">
        <div class="absolute flex w-full justify-end pr-2 pt-2">
            <div class="portrait-img-checkbox group hover:cursor-pointer transition-all 
                        w-[24px] h-[24px] p-1 pt-[3px] border-white border-2
                        flex align-middle justify-center rounded-full 
                        hover:bg-primary-100">
                <x-icon icon="check text-primary group-hover:text-white hidden"/>
            </div>
        </div>
        <img 
            src="data:image/jpeg;base64,{{$img}}"
            alt=""
            class="w-full max-w-none h-max"
        />
    </div>
    <div class="flex justify-between py-2 text-l absolute w-full">
        <span class="truncate">{{$filename}}</span>
    </div>
    <div class="flex justify-between py-2 text-sm">
        <span class="truncate">{{html_entity_decode($name, ENT_QUOTES)}}</span>
    </div>
</div>
