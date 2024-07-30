<template>
    <BaseButton class="bg-[#E86C00] text-sm hover:bg-[#ee8224] transition-all hover:transition-all">{{ testMessage }}<slot/></BaseButton>
</template>

<script setup lang="ts">
import BaseButton from './BaseButton.vue';

// defineProps<{
//     border?: string
// }>()
// defineProps({
//     type: String
// })

// if type === "borderless" {
    
// }
const testMessage = "testMessage";
</script>   