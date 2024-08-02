<template>
  <button type="button" 
    class="rounded-md bg-none text-sm cursor-pointer text-[#ffffff] flex flex-row gap-1">
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
