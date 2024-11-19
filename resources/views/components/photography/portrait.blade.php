@props([
    'id' => 'id',
    'name' => 'Name',
    'active' => false
])

<div class="rounded-md w-[186] px-2 pt-2 flex flex-col align-middle justify-center {{ $active ? "bg-primary-100":"bg-transparent" }}">
    <div class="relative">
        <div class="absolute flex w-full justify-end pr-2 pt-2">
            <div class="w-[24px] h-[24px] align-middle justify-center p-1 bg-white flex rounded-full group hover:cursor-pointer transition-all {{ $active ? 'hover:bg-primary':'hover:bg-primary-100'}}">
                <x-icon icon="{{ $active ? 'check text-primary group-hover:text-white':'check text-white'}}"/>
            </div>
        </div>
        <img 
        src="{{ Vite::asset('resources/assets/images/Portrait.png') }}" 
        alt=""
        width=125px
        class="w-full max-w-none rounded"
    />
    <div class="absolute flex w-full justify-end pr-2 pt-2 bottom-2">
        <button>
            <x-icon icon="download text-white fa-xl"/>
        </button>
    </div>
    </div>
    
    <div class="flex justify-between p- py-2">
        <span>{{$name}}</span>
        {{-- <button>
            <x-icon icon="download"/>
        </button> --}}
    </div>
</div>