<template>
    <!-- <BaseButton class="px-3 py-2 bg-primary text-sm hover:bg-primary-hover transition-all hover:transition-all"><slot/></BaseButton> -->
    <BaseButton class="px-3 py-2 bg-white text-neutral text-sm border hover:bg-neutral hover:text-white transition-all hover:transition-all"><slot/></BaseButton>
</template>

<script setup lang="ts">
import BaseButton from './BaseButton.vue';
</script>
