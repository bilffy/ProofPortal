<template>
    <Link 
        class="flex-1 flex pl-4 pr-4 pt-2 pb-2 border-l-4 border-transparent" 
        :class="{'border-primary':activeNav}"
        href="/home">
        <img
            class="mr-4"
            src="../../../Images/Navigation/Home.png" alt=""> 
        <slot />
    </Link>
  </template>
  
  <script lang="ts" setup>
    import { ref } from 'vue'
    import { Link } from '@inertiajs/vue3'
    
    const activeNav = ref(true)

  </script>

<style>
</style>