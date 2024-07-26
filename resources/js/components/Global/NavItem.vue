<template>
    <Link 
        class="flex pl-4 pr-4 pt-2 pb-2 border-l-4 border-l-blue" 
        href="/home">
        <img
            class="mr-4"
            src="../../../Images/Navigation/Home.png" alt=""> 
        <slot />
    </Link>
  </template>
  
  <script lang="ts" setup>
    import { Link } from '@inertiajs/vue3'
    
  </script>

<style>
</style>