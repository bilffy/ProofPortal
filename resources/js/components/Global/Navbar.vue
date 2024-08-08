<template>
    <div class="flex flex-col w-[210px] mt-2">
        <div>
          <img :src="Logo" alt="">
        </div>
        <NavItem imgSrc="Home" :href="route('dashboard')" :activeNav=true>Home</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">PHOTOGRAPHY</span>
        <NavItem imgSrc="Portraits" :href="route('dashboard')">Portraits</NavItem>
        <NavItem imgSrc="Groups" :href="route('dashboard')">Group</NavItem>
        <NavItem imgSrc="Special Events" :href="route('dashboard')">Special Events</NavItem>
        <NavItem imgSrc="Promo Photos" :href="route('dashboard')">Promo Photos</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">PROOFING</span>
        <NavItem imgSrc="Proofing" :href="route('dashboard')">Proofing</NavItem>
        <span class="text-sm ml-4 font-bold mt-4">ADMIN TOOLS</span>
        <NavItem imgSrc="Manage Users" :href="route('users.manage')">Manage Users</NavItem>
        <NavItem imgSrc="Reports" :href="route('dashboard')">Reports</NavItem>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import NavItem from './NavItem.vue';
import { getAssetUrl } from '@/helpers/image.helper';

const Logo = getAssetUrl(`images/MSP-Logo_400x400.png`);

const activeNav = ref(true)

const navItem = ref([
{ id: 0, navText: "Home", navIcon: "../../../assets/images/Navigation/Home.png" },
{ id: 1, navText: "Portraits", navIcon: "../../../assets/images/Navigation/Portraits.png" },
{ id: 1, navText: "Group", navIcon: "../../../assets/images/Navigation/Groups.png" },
{ id: 1, navText: "Special Events", navIcon: "../../../assets/images/Navigation/Special Events.png" },
{ id: 1, navText: "Promo Photos", navIcon: "../../../assets/images/Navigation/Promo Photos.png" },
{ id: 1, navText: "Proofing", navIcon: "../../../assets/images/Navigation/Proofing.png" },
{ id: 1, navText: "Manage Users", navIcon: "../../../assets/images/Navigation/Manage Users.png" },
{ id: 1, navText: "Reports", navIcon: "../../../assets/images/Navigation/Reports.png" },
])
</script>

<style scoped>
  .example {
    color: var(--accent-teacher);
    background-color: var(--secondary);;
  }
</style>