<template>
    <BaseButton :type="type" @click="handleClick" class="px-3 py-2 bg-none text-neutral-600 hover:bg-[#f0eeec] transition-all hover:transition-all"><slot/></BaseButton>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';
import BaseButton from './BaseButton.vue';

const props = defineProps({
  type: {
    type: String,
    default: 'button',
  },
  onClick: {
    type: Function,
    default: null,
  },
});

const handleClick = (event: Event) => {
  if (props.onClick) {
    event.preventDefault();
    props.onClick(event);
  }
};

</script>