<template>
    <div class="flex flex-row test">
        <Navbar/>
        <div class="flex flex-col w-full h-screen">
            <header class="w-full flex justify-between pl-4 pr-4 mr-2 py-2">
                <div class="flex flex-1 items-center justify-center">
                    <div class="flex flex-row bg-[#F5F7FA] gap-4 p-1 border fancy-border rounded border-primary">
                        <div class="flex flex-row items-center gap-2 text-primary">
                            <img :src="Info" alt="" width="20px" height="20px">
                            You’re viewing as <span class="font-semibold">[User]</span> with <span class="font-semibold">[privilege]</span> privilege
                            </div>
                        <div>
                            <BaseButton class="bg-alert p-1">
                                Exit Preview
                                <img :src="CloseAlert" alt="" width="20px" height="20px">
                            </BaseButton>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row items-center">
                    <div class="flex items-center text-[#586B78] bg-[#D9D9D9] rounded-full px-2 py-0.5">MSP RESOURCE CENTRE</div>
                    <div class="ms-3 relative">
                        <Dropdown width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md float-right">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800"
                                    >
                                        {{ user.name }}

                                        <svg
                                            class="ms-2 -me-0.5 h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>
            <main class="w-full p-4 bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden">
              <slot />
          </main>
          <Footer />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3'
import Navbar from '@/components/Global/Navbar.vue'
import Footer from '../components/Global/Footer.vue'
import DropdownLink from '@/components/DropdownLink.vue';
import Dropdown from '@/components/Dropdown.vue';
import { getAssetUrl } from '@/helpers/image.helper';
import BaseButton from '@/components/Button/BaseButton.vue';

const Info = getAssetUrl(`images/Info.svg`);
const CloseAlert = getAssetUrl(`images/close-round-alert.svg`);

const {...pageProps} = usePage().props;
const {...user} = pageProps.auth.user;

console.log({user});
</script>

<style scoped>
.fancy-border {
  border-width: 0.5rem;
  border-style: solid;
  /* border-image: linear-gradient(to right, #6059A9, #447AC7, #19ABD7, #8CE5D3, #F9F8EB, #FDCF8D, #FE9F6E, #FF6573, #FF456E) 1; */
}
</style>
