<th scope="col" class="TableHeaderCell border-b-2  border-neutral-300 p-4">
    <div class="flex flex-row justify-between">
        <div class="flex flex-row gap-1 items-center">
            {{ $slot }}
            <x-icon icon="sort fa-sm" /> 
            {{-- sort-desc | sort-asc --}}
            
            <!-- <img :src="sortImgUrl" alt="" v-if="sortable" @click="$emit('sortWithField', mySort)"/> -->
        </div>
         <x-button.link>
            <x-icon icon="filter fa-sm"/>
        </x-button.link>
    </div>
</th>