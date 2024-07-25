<template>
  <div class="flex flex-col w-[300px]">
    <NavItem imgSrc="../../../Images/Navigation/Home.png">Home</NavItem>
    <span class="text-sm ml-4 text-primary">PHOTOGRAPHY</span>
    <NavItem>Portraits</NavItem>
    <NavItem>Group</NavItem>
    <NavItem>Special Events</NavItem>
    <NavItem>Promo Photos</NavItem>
    <span class="text-sm ml-4 text-primary">PROOFING</span>
    <NavItem>Proofing</NavItem>
    <span class="text-sm ml-4 text-primary">ADMIN TOOLS</span>
    <NavItem>Manage Users</NavItem>
    <NavItem>Reports</NavItem>
  </div>
</template>

<script lang="ts" setup>
  import { ref } from 'vue'
  import NavItem from './NavItem.vue';

  const activeNav = ref(true)
  
  const navItem = ref([
  { id: 0, navText: "Home", navIcon: "../../../Images/Navigation/Home.png" },
  { id: 1, navText: "Portraits", navIcon: "../../../Images/Navigation/Portraits.png" },
  { id: 1, navText: "Group", navIcon: "../../../Images/Navigation/Groups.png" },
  { id: 1, navText: "Special Events", navIcon: "../../../Images/Navigation/Special Events.png" },
  { id: 1, navText: "Promo Photos", navIcon: "../../../Images/Navigation/Promo Photos.png" },
  { id: 1, navText: "Proofing", navIcon: "../../../Images/Navigation/Proofing.png" },
  { id: 1, navText: "Manage Users", navIcon: "../../../Images/Navigation/Manage Users.png" },
  { id: 1, navText: "Reports", navIcon: "../../../Images/Navigation/Reports.png" },
  ])
</script>

<style scoped>
  .example {
    color: var(--accent-teacher);
    background-color: var(--secondary);;
  }
</style>