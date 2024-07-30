<template>
  <GuestLayout>
      <Head title="Login" />
      <h1 class="text-3xl text-[#02B3DF] mb-4"> Login</h1>

      <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
          {{ status }}
      </div>

      <form @submit.prevent="submit">
          <div>
              <TextInput
                  id="email"
                  type="email"
                  class="mt-1 block w-full"
                  v-model="form.email"
                  required
                  autofocus
                  autocomplete="username"
                  label="Email"
                  placeholder="Email"
              />

              <InputError class="mt-1 mb-2" :message="form.errors.email" />
          </div>

          <div class="mt-4">
              <TextInput
                  id="password"
                  type="password"
                  class="mt-1 block w-full"
                  v-model="form.password"
                  required
                  autocomplete="current-password"
                  label="Password"
                  placeholder="Password"
              />

              <InputError class="mt-1 mb-2" :message="form.errors.password" />
          </div>

          <div class="flex w-full items-center justify-between">
              <BaseButton
                  :type="submit"
                  :class="{ 'opacity-25': form.processing }"
                  :disabled="form.processing"
              >
                  Login
                </BaseButton>
              <Link
                  v-if="canResetPassword"
                  :href="route('password.request')"
                  class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                  Forgot your password?
              </Link>
          </div>
      </form>
  </GuestLayout>
</template>

<script setup lang="ts">
import GuestLayout from '@/Shared/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import TextInput from '@/components/TextInput.vue';
import BaseButton from '@/components/Button/BaseButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>
