<template>
    <nav aria-label="Page navigation example" v-if="linksValid && finalLinks.length > 0">
        <ul class="inline-flex -space-x-px text-sm" >
            <li v-for="({ url, active, label, isNext, isPrev, isEllipsis }) in finalLinks">
                <Link
                    :href="url || '#'"
                    class="flex items-center justify-center px-3 h-8"
                    :class="[ active ? activeClass : inactiveClass, {[nextClass]: isNext, [previousClass]: isPrev, ['text-neutral-400']: url === null} ]"
                    v-if="!isEllipsis"
                >
                    <img :class="[{['opacity-50']: url === null}]" :src="chevronLeftUrl" alt="" v-if="isPrev">
                    {{ isPrev ? "Previous" : isNext ? "Next" : label }}
                    <img :class="[{['opacity-50']: url === null}]" :src="chevronRightUrl" alt="" v-if="isNext">
                </Link>
                <div class="flex items-center justify-center px-3 h-8" v-if="url === null && isEllipsis">
                    {{ label }}
                </div>
            </li>
        </ul>
    </nav>
</template>
  
<script setup lang="ts">
import { computed, ref, type PropType } from 'vue';
import { getImgAssetUrl } from '@/helpers/image.helper';
import type { Pagination } from '@/types/pagination.type';
import { Link } from '@inertiajs/vue3';
import { buildLinkIndices, buildLinks } from '@/helpers/pagination.helper';

const chevronLeftUrl = getImgAssetUrl('Chevron_Left.png');
const chevronRightUrl = getImgAssetUrl('Chevron_Right.png');

const activeClass = ref('text-blue-600 bg-[#e9e6e3] hover:bg-blue-100 hover:text-blue-700');
const inactiveClass = ref('flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700');
const previousClass = ref('ms-0 rounded-s-lg');
const nextClass = ref('rounded-e-lg');

const props = defineProps({
    pagination: Object as PropType<Pagination>,
});

const { pagination } = props;
const { meta } = pagination || {};
const { links } = meta || { links: [] };

const linkIndices = buildLinkIndices(links);
const finalLinks = buildLinks(links, linkIndices);

const linksValid = computed(() => {
    const invalidLinks = linkIndices.filter((index) => index && 0 > index);
    return 0 === invalidLinks.length;
});

</script>
  