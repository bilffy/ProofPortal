<template>
    <div class="flex flex-col w-[210px] mt-2">
        <div>
          <img :src="Logo" alt="">
        </div>
        <NavItem imgSrc="Home" :href="route('dashboard')" :activeNav="activeNav === HOME">Home</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">PHOTOGRAPHY</span>
        <NavItem imgSrc="Portraits" :href="route('dashboard')" :activeNav="activeNav === PORTRAITS">Portraits</NavItem>
        <NavItem imgSrc="Groups" :href="route('dashboard')" :activeNav="activeNav === GROUP">Group</NavItem>
        <NavItem imgSrc="Special Events" :href="route('dashboard')" :activeNav="activeNav === SPECIAL_EVENTS">Special Events</NavItem>
        <NavItem imgSrc="Promo Photos" :href="route('dashboard')" :activeNav="activeNav === PROMO_PHOTOS">Promo Photos</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">PROOFING</span>
        <NavItem imgSrc="Proofing" :href="route('dashboard')" :activeNav="activeNav === PROOFING">Proofing</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">ADMIN TOOLS</span>
        <NavItem imgSrc="Manage Users" :href="route('users.manage')" :activeNav="activeNav === MANAGE_USERS">Manage Users</NavItem>
        <NavItem imgSrc="Reports" :href="route('dashboard')" :activeNav="activeNav === REPORTS">Reports</NavItem>
    </div>
</template>

<script setup lang="ts">
import NavItem from './NavItem.vue';
import { getAssetUrl } from '@/helpers/image.helper';
import { getCurrentNav } from '@/helpers/utils.helper';
import { NAV_TABS } from '@/helpers/constants.helper';

const { HOME, PORTRAITS, GROUP, SPECIAL_EVENTS, PROMO_PHOTOS, PROOFING, MANAGE_USERS, REPORTS } = NAV_TABS;
const Logo = getAssetUrl(`images/MSP-Logo_400x400.png`);
const activeNav = getCurrentNav();
</script>

<style scoped>
  .example {
    color: var(--accent-teacher);
    background-color: var(--secondary);;
  }
</style>
