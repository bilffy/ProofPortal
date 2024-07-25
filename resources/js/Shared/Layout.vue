<template>
    <header class="w-full flex justify-between pl-4 pr-4 header-border">
      <img src="../../Images/MSP-Logo_400x400.png" alt="">
      <div class="flex flex-col justify-end">
        <div>HILLVIEW PRIMARY SCHOOL</div>
        <div>James Limpiado</div>
      </div>
    </header>
    <div class="flex flex-row">
      <Navbar/>
      <main class="w-full p-4">
        <slot />
      </main>
    </div>
    <Footer />
</template>

<script lang="ts" setup>
  import Navbar from '../components/Global/Navbar.vue'
  import Footer from '../components/Global/Footer.vue'
</script>

<style scoped>
  .header-border {
    border-width: 0.5rem;
    border-style: solid;
    border-image: linear-gradient(to right, #6059A9, #447AC7, #19ABD7, #8CE5D3, #F9F8EB, #FDCF8D, #FE9F6E, #FF6573, #FF456E) 1;
    border-top: none;
    border-left: none;
    border-right: none;
  }
</style>