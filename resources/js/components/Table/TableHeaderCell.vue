<template>
    <th scope="col" class="TableHeaderCell border-b-2 border-[#00A7D9] p-4">
        <div class="flex flex-row justify-between">
            <div class="flex flex-row gap-1 items-center">
                <slot />
                <img :src="Sort" alt="">
            </div>
            <ButtonLink><img :src="Filter" alt=""></ButtonLink>
        </div>
    </th>
</template>

<script setup lang="ts">
import ButtonLink from '../Button/ButtonLink.vue';
import { getAssetUrl } from '@/helpers/image.helper';

    const Sort = getAssetUrl(`images/Table/Sort.svg`);
    const SortUp = getAssetUrl(`images/Table/SortUp.svg`);
    const SortDown = getAssetUrl(`images/Table/SortDown.svg`);
    const Filter = getAssetUrl(`images/Table/Filter.svg`);

</script>