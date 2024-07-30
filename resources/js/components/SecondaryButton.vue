<template>
  <button
      :type="type"
      class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
  >
      <slot />
  </button>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import type { ButtonType } from '@/helpers/types.helper';

defineProps({
    type: {
        type: String as PropType<ButtonType>,
        default: 'button',
    },
});
</script>
