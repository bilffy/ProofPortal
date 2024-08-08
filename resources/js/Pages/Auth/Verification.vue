<template>
    <GuestLayout>
        <Head title="Confirm Password" />
        <h1 class="text-3xl text-[#02B3DF]  ">Verification Code</h1>
        <div class="mb-4 text-sm text-gray-600">
            A Verification Code has been sent to your email
        </div>
  
        <form @submit.prevent="submit">
          <div class="hidden">
            <TextInput
                id="email"
                type="text"
                class="mt-1 block w-full"
                v-model="form.email"
                required
                autocomplete="email"
                autofocus
            />
            <InputError class="mt-2" :message="form.errors.email" />
          </div>
            <div>
                <TextInput
                    id="otp"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.otp"
                    required
                    autocomplete="otp"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.otp" />
            </div>
  
            <div class="flex w-full items-center justify-between">
                <ButtonPrimary>Verify</ButtonPrimary>
                <ButtonLink>Resend Verification Code</ButtonLink>
            </div>
        </form>
    </GuestLayout>
  </template>
  
  <script setup lang="ts">
  import GuestLayout from '@/Shared/GuestLayout.vue';
  import InputError from '@/components/InputError.vue';
  import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
  import ButtonLink from '@/components/Button/ButtonLink.vue';
  import TextInput from '@/components/TextInput.vue';
  import { Head, useForm } from '@inertiajs/vue3';

  const props = defineProps({
    otp: {
      type: String,
      required: true,
    },
    token: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
    }
  });

  const form = useForm({
    token: props.token,
    otp: props.otp,
    email: props.email,
  });
  
  const submit = () => {
      form.post(route('otp.verify'), {
          onFinish: () => form.reset(),
      });
  };
</script>
  