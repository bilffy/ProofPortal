@props(['options' => [], 'dataType' => '', 'model' => ''])
<div
    x-data="{
                open: false,
                selected: @entangle($model),
                toggle(item, type) {
                    console.log({item, type});
                    if (this.selected.includes(item)) {
                        this.selected = this.selected.filter(i => i !== item);
                    } else {
                        this.selected.push(item);
                    }
                    @this.performFilter(this.selected, type).then(() => {});
                }
            }"
    class="relative"
>
    <x-button.link @click="open = !open">
        <i class="fa fa-filter fa-sm" :class="selected.length > 0 ? 'text-primary' : 'text-[#CFD1DE]'"></i>
    </x-button.link>

    <div x-show="open" @click.away="open = false" class="absolute z-10 top-8 bg-white divide-y divide-gray-100 rounded-lg shadow">
        <ul class="py-2 text-sm text-gray-700 max-h-96 overflow-y-auto" aria-labelledby="dropdownDefaultButton-0">
            @foreach ($options as $key => $option)
                @if (is_array($option))
                    <li class="flex items-center px-4 py-2">
                        <div class="px-4 py-2 whitespace-nowrap">
                            {{ ucfirst($key) }}
                        </div>
                    </li>
                    @foreach ($option as $k => $op)
                        <li class="flex items-center px-4 py-2 hover:cursor-pointer hover:bg-gray-100" @click="toggle('{{ $k }}', '{{$dataType}}')">
                            <input
                                type="checkbox"
                                :value="'{{ $k }}'"
                                :checked="selected.includes('{{ $k }}')"
                                class="mr-2 hover:cursor-pointer"
                            >
                            <div
                                class="px-4 py-2 whitespace-nowrap"
                                :class="{ 'bg-blue-100': selected.includes('{{ $k }}') }"
                            >
                                {{ $op }}
                            </div>
                        </li>
                    @endforeach
                @else
                    <li class="flex items-center px-4 py-2 hover:cursor-pointer hover:bg-gray-100" @click="toggle('{{ $key }}', '{{$dataType}}')">
                        <input
                            type="checkbox"
                            :value="'{{ $key }}'"
                            :checked="selected.includes('{{ $key }}')"
                            class="mr-2 hover:cursor-pointer"
                        >
                        <div
                            class="px-4 py-2 whitespace-nowrap"
                            :class="{ 'bg-blue-100': selected.includes('{{ $key }}') }"
                        >
                            {{ $option }}
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>