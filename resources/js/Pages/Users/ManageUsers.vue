<template>
    <button @click="close" type="button"><slot /></button>
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
  