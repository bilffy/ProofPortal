@props(['sortable' => true, 'filterable' => true, 'isLivewire' => false, 'wireEvent' => '' ])

<th scope="col" {{ $attributes->merge([ 'class' => "TableHeaderCell border-b-2  border-neutral-300 p-4" ]) }}
    @if ($isLivewire) wire:click.prevent="{{ $wireEvent }}" @endif />
    <div class="flex flex-row justify-between">
        <div class="flex flex-row gap-1 items-center">
            {{ $slot }}
            @if ($sortable)
                <x-icon icon="sort fa-sm"/>
                {{-- sort-desc | sort-asc --}} 
                {{-- <img :src="sortImgUrl" alt="" v-if="sortable" @click="$emit('sortWithField', mySort)"/> --}}
            @endif
        </div>
        @if ($filterable)
            <x-button.link>
                <x-icon icon="filter fa-sm"/>
            </x-button.link>
        @endif
    </div>
</th>