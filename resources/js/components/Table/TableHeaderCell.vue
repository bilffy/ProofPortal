<template>
    <th scope="col" class="TableHeaderCell border-b-2  border-neutral-300 p-4">
        <div class="flex flex-row justify-between">
            <div class="flex flex-row gap-1 items-center">
                <slot />
                <img :src="sortImgUrl" alt="" v-if="sortable" @click="$emit('sortWithField', mySort)"/>
            </div>
            <ButtonLink v-if="filterable"><img :src="getAssetUrl(`images/Table/Filter.svg`)" alt=""></ButtonLink>
        </div>
    </th>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ButtonLink from '../Button/ButtonLink.vue';
import { getAssetUrl } from '@/helpers/image.helper';

const props = defineProps({
    id: {
        type: String,
        default: '',
    },
    sortable: {
        type: Boolean,
        default: true,
    },
    filterable: {
        type: Boolean,
        default: true,
    },
    sort: {
        type: String,
        default: '',
    },
    filters: {
        type: Array,
        default: [],
    }
});
const { id, sortable, filterable, sort } = props;

const mySort = computed(() => {
    if (id === sort) {
        return `-${id}`;
    }
    return id;
});

const sortImgUrl = computed(() => {
    if (id === sort) {
        return getAssetUrl(`images/Table/SortUp.svg`);
    }
    if (sort.startsWith('-') && sort === `-${id}`) {
        return getAssetUrl(`images/Table/SortDown.svg`);
    }
    return getAssetUrl(`images/Table/Sort.svg`);
});
</script>