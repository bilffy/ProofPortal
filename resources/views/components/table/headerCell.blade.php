@props(['sortable' => true, 'filterable' => false, 'filterModel' => '', 'filterOptions' => [], 'isLivewire' => false, 'wireEvent' => '', 'sortBy' => '', 'sortDirection' => 'asc'])

<th scope="col" {{ $attributes->merge([ 'class' => "TableHeaderCell border-b-2  border-neutral-300 p-4" ]) }}>
    @php
        $isFilterable = $filterable && !empty($filterModel) && !empty($filterOptions);
    @endphp
    <div
        class="flex flex-row justify-between hover:cursor-pointer"
        @if ($isLivewire && $sortable) wire:click.prevent="{{ $wireEvent }}" @endif
        @if ($isFilterable)
            x-data="{
                open: false,
                selected: @entangle($filterModel),
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
            @click="open = !open"
        @endif
    >
        <div class="flex flex-row gap-2 items-center hover:bg-primary-100 rounded px-2 py-2">
            @if ($sortable && $sortBy === $attributes['id'])
                {{-- sort-desc | sort-asc --}}
                @if ($sortDirection === 'asc')
                    <x-icon icon="arrow-up fa-sm" />
                @else
                    <x-icon icon="arrow-down fa-sm" />
                @endif
            @endif
            {{ $slot }}
            @if ($filterable)
                @if ($isFilterable)
                    <x-icon icon="filter fa-sm" x-show="selected.length > 0" class="text-primary"/>
                @endif
                <x-icon icon="chevron-down fa-sm" />
            @endif
        </div>
        @if ($isFilterable)
            {{-- <x-form.multiSelectDropdown id="filterDropdown-{{ $filterModel }}" model="{{$filterModel}}" :options="$filterOptions" dataType="{{$attributes->get('id')}}" /> --}}
            @php
                $dataType = $attributes->get('id');
            @endphp
            <div id="filterDropdown-{{ $filterModel }}" class="relative">
                <div x-show="open" @click.away="open = false" class="absolute z-10 top-9 -start-8 bg-white divide-y divide-gray-100 rounded-lg shadow" x-cloak>
                    <ul class="py-2 text-sm text-gray-700 max-h-96 overflow-y-auto" aria-labelledby="dropdownDefaultButton-0">
                        @foreach ($filterOptions as $key => $option)
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
        @endif
    </div>
</th>