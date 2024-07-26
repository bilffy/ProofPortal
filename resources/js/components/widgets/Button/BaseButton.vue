<template>
  <button type="button" 
    class="px-3 py-2 rounded-md">
    <slot />
  </button>
</template>

<script lang="ts">
  export default {
    setup(props, { emit }) {
      const close = () => {
        emit("close");
      };
      return { close };
    },
  };
</script>

