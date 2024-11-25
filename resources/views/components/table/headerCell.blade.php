@props(['sortable' => true, 'filterable' => true, 'filterModel' => '', 'filterOptions' => [], 'isLivewire' => false, 'wireEvent' => '', 'sortBy' => '', 'sortDirection' => 'asc'])

<th scope="col" {{ $attributes->merge([ 'class' => "TableHeaderCell border-b-2  border-neutral-300 p-4" ]) }}>
    <div class="flex flex-row justify-between">
        <div class="flex flex-row gap-2 items-center hover:cursor-pointer"  @if ($isLivewire) wire:click.prevent="{{ $wireEvent }}" @endif>
            {{ $slot }}
            @if ($sortable)
                @if ($sortBy === $attributes['id'])
                    {{-- sort-desc | sort-asc --}}
                    @if ($sortDirection === 'asc')
                        <x-icon icon="sort-asc fa-sm" class="text-[#CFD1DE]"/>
                    @else
                        <x-icon icon="sort-desc fa-sm" class="text-[#CFD1DE]"/>
                    @endif
                @else
                    <x-icon icon="sort fa-sm" class="text-[#CFD1DE]"/>
                @endif
            @endif
        </div>
        @if (!empty($filterModel) && !empty($filterOptions))
        <div>
            <x-form.multiSelectDropdown id="filterDropdown-{{ $filterModel }}" wire:model="{{$filterModel}}" :options="$filterOptions" />
        </div>
        @endif
    </div>
</th>