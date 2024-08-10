<template>
  <GuestLayout>
      <Head title="Reset Password" />
      <h3 class="text-2xl text-[#02B3DF] mb-4"> Link Expired</h3>
      <p class="text-gray-600 mb-6">
        The link is invalid or has expired.
      </p>
  </GuestLayout>
</template>

<script setup lang="ts">
import GuestLayout from '@/Shared/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
