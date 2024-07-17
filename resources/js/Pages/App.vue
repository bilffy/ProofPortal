<template>
  <Layout class="place-content-center">
    <Head title="Welcome" />
    <h1 class="text-3xl font-bold underline col-span-2 ">Welcome, {{ user?.name }}!</h1>
    <h2 class="bg-color-200">Test component: {{ test_val }}</h2>
  </Layout>
</template>

<script setup lang="ts">
  import Layout from '../Shared/Layout.vue'
  import { Head } from '@inertiajs/vue3'
  import {User} from '../models/User.model'
import type { PropType } from 'vue';

  let props = defineProps({
    test_val: String,
    user: Object as PropType<User>
  });

  console.log({u: props.user});
</script>

<style scoped>
</style>
