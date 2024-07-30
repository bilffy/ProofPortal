<template>
  <AuthenticatedLayout class="place-content-center">\
    <!-- <h1 class="text-3xl font-bold underline col-span-2 ">Welcome, {{ user?.name }}!</h1> -->
    <div class="p-4">
      <h3>Title</h3>
    </div>
    <Head title="Welcome" />
    <h1 class="text-3xl font-bold underline col-span-2 ">Welcome, {{ user?.name }}!</h1>
    <h2 class="bg-color-200">Test component: {{ user?.email }}</h2>
    
    <div class="p-4">Main Footer</div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
  import AuthenticatedLayout from '@/Shared/AuthenticatedLayout.vue';
  import { usePage } from '@inertiajs/vue3'

  const {...pageProps} = usePage().props;
  const {...user} = pageProps.auth.user;
</script>
