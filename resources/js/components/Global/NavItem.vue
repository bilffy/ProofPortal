<template>
    <Link
        class="flex pl-4 pr-4 pt-2 pb-2 border-l-4 border-transparent"
        :class="{'border-l-blue' : props.activeNav}"
        :href="href">
        <img
            class="mr-4"
            :src="imageSrc" alt=""> 
        <slot />
    </Link>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { getAssetUrl } from '@/helpers/image.helper';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    imgSrc: {
        type: String,
        default: "Home"
    },
    activeNav: {
        type: Boolean,
        default: false
    }
});

console.log({img: props.imgSrc, active: props.activeNav})

const imageSrc = getAssetUrl(`images/Navigation/${props.imgSrc}.png`);
// const activeNav = ref(true)
</script>
