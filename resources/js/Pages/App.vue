<template>
    <AuthenticatedLayout class="place-content-center">
        <h1 class="text-3xl font-bold underline col-span-2 ">Welcome, {{ user?.name }}!</h1>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Shared/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3'

const {...pageProps} = usePage().props;
const {...user} = pageProps.auth.user;
</script>
