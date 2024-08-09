<template>
  <button :type="type"  
    class="rounded-md bg-none text-sm cursor-pointer text-[#ffffff] flex flex-row gap-1">
    <slot />
  </button>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'submit',
  },
});
</script>