<template>
  <button type="button" 
    class="px-3 py-2 rounded-md bg-none text-sm cursor-pointer text-[#ffffff]">
    <slot />
  </button>
</template>

<!-- <script lang="ts">
  export default {
    setup(props, { emit }) {
      const close = () => {
        emit("close");
      };
      return { close };
    },
  };
</script> -->
