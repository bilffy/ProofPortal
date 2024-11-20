@props([
    'id' => 'id',
    'name' => 'Name',
    'active' => false
])

<div class="rounded-md w-[186] px-2 pt-2 flex flex-col align-middle justify-center "> {{-- {{ $active ? "bg-primary-100":"bg-transparent" }} --}}
    <div class="relative">
        <div class="absolute flex w-full justify-end pr-2 pt-2">
            <div class="group hover:cursor-pointer transition-all 
                        w-[24px] h-[24px] p-1 pt-[3px] border-white border-2
                        flex align-middle justify-center rounded-full 
                        {{ $active ? 'bg-white hover:bg-primary-100':'hover:bg-primary-100'}}">
                <x-icon icon="{{ $active ? 'check text-primary group-hover:text-white':''}}"/>
            </div>
        </div>
        <img 
        src="{{ Vite::asset('resources/assets/images/Portrait.png') }}" 
        alt=""
        width=125px
        class="w-full max-w-none rounded"
    />
    </div>
    
    <div class="flex justify-between py-2 text-sm">
        <span>{{$name}}</span>
    </div>
</div>