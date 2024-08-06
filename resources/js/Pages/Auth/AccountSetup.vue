<template>
    <GuestLayout>
        <Head title="Login" />
        <h1 class="text-3xl text-[#02B3DF] mb-4"> Account Setup</h1>

        <div class="mb-4 font-medium text-green-600 flex flex-row">
            <div class="w-full">
                <label for="">Name</label>
                <p class="font-semibold">FirstName LastName</p>
            </div>
            <div class="w-full">
                <label for="">Email</label>
                <p class="font-semibold">name@mail.com</p>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    label="Password"
                    placeholder="Password"
                />

                <InputError class="mt-1 mb-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    label="Repeat Password"
                    placeholder="Repeat Password"
                />

                <InputError class="mt-1 mb-2" :message="form.errors.password" />
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
                <ButtonPrimary>Next</ButtonPrimary>
               
            </div>
        </form>
    </GuestLayout>
</template>

<script setup lang="ts">
import GuestLayout from '@/Shared/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import TextInput from '@/components/TextInput.vue';
import BaseButton from '@/components/Button/BaseButton.vue';
import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
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
