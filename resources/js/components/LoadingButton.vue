<template>
  <button :disabled="loading" class="flex items-center">
    <div v-if="loading" class="btn-spinner mr-2" />
    <slot />
  </button>
</template>

<script lang="ts">
  export default {
    props: {
      loading: Boolean,
    },
  }
</script>