<template>
    <GuestLayout>
        <Head title="Login" />
        <h1 class="text-3xl text-[#02B3DF] mb-4"> Account Setup</h1>

        <div class="mb-4 font-medium text-green-600 flex flex-row">
            <div class="w-full">
                <label for="">Name</label>
                <p class="font-semibold">{{ firstName }} {{ lastName }}</p>
            </div>
            <div class="w-full">
                <label for="">Email</label>
                <p class="font-semibold">{{email}}</p>
            </div>
        </div>

        <form @submit.prevent="submit">
          <div class="hidden">
            <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.email"
                required
                autofocus
                autocomplete="username"
            />

            <InputError class="mt-2" :message="form.errors.email" />
          </div>
            <div>
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    placeholder="Password"
                    autocomplete="new-password"
                />
  
                <InputError class="mt-2" :message="form.errors.password" />
              
            </div>

            <div class="mt-4">
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    placeholder="Repeat Password"
                    autocomplete="new-password"
                />
  
                <InputError class="mt-1 mb-2" :message="form.errors.password_confirmation" />
              
              
            </div>

            <div class="ml-4 mb-4">
                <ul class="list-disc">
                    <li class="text-success font-semibold">At least 12 characters</li>
                    <li>Include at least 1 of uppercase letter</li>
                    <li class="text-success font-semibold">At least 1 lowercase letter</li>
                    <li class="text-success font-semibold">At least 1 number</li>
                </ul>
            </div>

            <div class="flex w-full items-center justify-between">
                <ButtonPrimary :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                  Next
                </ButtonPrimary>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup lang="ts">
import GuestLayout from '@/Shared/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import TextInput from '@/components/TextInput.vue';
import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  email: {
    type: String,
    required: true,
  },
  token: {
    type: String,
    required: true,
  },
  firstName: {
    type: String,
    required: true,
  },
  lastName: {
    type: String,
    required: true,
  },
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('account.setup.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>
