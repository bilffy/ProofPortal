<template>
    <Link
        class="flex items-center pl-4 pr-4 pt-2 pb-2 border-white"
        :class="{'bg-primary text-white rounded-e-md' : props.activeNav}"
        :href="href">
        
        <img
            width="20px"
            height="20px"
            class="mr-4 fill-purple"
            :class="{'fill-white':props.activeNav}"
            :src="imageSrc" 
            alt="">
        <slot />
    </Link>
</template>

<script setup lang="ts">
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

const imageSrc = getAssetUrl(`images/Navigation/${props.imgSrc}.svg`);
// const activeNav = ref(true)
</script>
