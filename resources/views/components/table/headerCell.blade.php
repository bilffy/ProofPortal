<th scope="col" class="TableHeaderCell border-b-2  border-neutral-300 p-4">
    <div class="flex flex-row justify-between">
        <div class="flex flex-row gap-1 items-center">
            {{ $slot }}
            <img src="{{ Vite::asset('resources/assets/images/Table/Sort.svg') }}" alt=""/>
            <!-- <img :src="sortImgUrl" alt="" v-if="sortable" @click="$emit('sortWithField', mySort)"/> -->
        </div>
        <!-- <ButtonLink v-if="filterable"><img :src="getAssetUrl(`images/Table/Filter.svg`)" alt=""></ButtonLink> -->
         <x-button.buttonLink><img src="{{ Vite::asset('resources/assets/images/Table/Filter.svg') }}" alt=""/></x-button.buttonLink>
    </div>
</th>