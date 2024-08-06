<template>
    <AuthenticatedLayout class="place-content-center">
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">Add New User</h3>
            <div></div>
        </div>
        <div class="relative overflow-x-auto mb-8">
            <div class="max-w-screen-md">
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    label="Email"
                    placeholder="Email"
                />

                <InputError class="mt-1 mb-2" />
            </div>
            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <TextInput
                        id="fname"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="First Name"
                        label="First Name"
                        placeholder="First Name"
                    />
                    <InputError class="mt-1 mb-2" />
                </div>
                <div class="w-full">
                    <TextInput
                        id="lname"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="Last Name"
                        label="Last Name"
                        placeholder="Last Name"
                    />
                    <InputError class="mt-1 mb-2" />
                </div>
            </div>

            <div class="max-w-screen-md">
                <Select>User Role</Select>
            </div>
        </div>

          
      <div class="py-4 max-w-screen-md flex flex-row gap-4 justify-end border-t-[1px] border-t-neutral-400">
        <ButtonSecondary>Cancel</ButtonSecondary>
        <ButtonPrimary>Save</ButtonPrimary>
      </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Shared/AuthenticatedLayout.vue';
import TextInput from '@/components/TextInput.vue';
import InputError from '@/components/InputError.vue';
import Select from '@/components/Select.vue';
import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/components/Button/ButtonSecondary.vue';

import { getAssetUrl } from '@/helpers/image.helper';

const moreImageUrl = getAssetUrl('images/more.svg');
const chevronLeftUrl = getAssetUrl('images/Chevron_Left.png');
const chevronRightUrl = getAssetUrl('images/Chevron_Right.png');
</script>
