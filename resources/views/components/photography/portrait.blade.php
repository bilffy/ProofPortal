@props([
    'id' => 'id',
    'name' => 'Name',
    'active' => false
])

<div class="rounded-md w-[186]  px-2 pt-2 {{ $active ? "bg-primary-100":"bg-transparent" }}">
    <img 
        src="{{ Vite::asset('resources/assets/images/Portrait.png') }}" 
        alt=""
        width=125px
        class="w-[200px] max-w-none rounded"
    />
    <div class="flex justify-between p- py-2">
        <span>{{$name}}</span>
        <button>
            <x-icon icon="download"/>
        </button>
    </div>
</div>