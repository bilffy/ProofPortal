<script lang="ts" setup>
  import { Link } from '@inertiajs/vue3'
  import Navbar from '../components/Global/Navbar.vue'
  import Footer from '../components/Global/Footer.vue'
</script>

<template>
  <main>
    <header class="w-full flex bg-zinc-600">
      <Link class="flex-1" href="/home">Home</Link>
      <Link class="flex-1" href="/test">Test</Link>
    </header>
    <Navbar />
    <article class="w-100 bg-zinc-600">
      <slot />
    </article>
    <footer>
      <Footer />
    </footer>
  </main>
</template>