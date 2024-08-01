<template>
  <footer class="bg-neutral-100 p-4 w-full bg-sky-500 flex justify-evenly text-sm fancy-border-top mt-4">
    <div>Copyright ⓒ 2024 MSP Photography - Blueprint 1.9.1</div>
    <!-- <div>You're logged in as <strong>[User]</strong> with <strong>[privilege]</strong> privileges</div> -->
    <div class="flex flex-row gap-4">
      <div class="flex flex-row items-center">
        <img :src="Location" alt="">
        MSP Melbourne 36-38 Burwood Road Hawthorn VIC 3122</div>
      <div class="flex flex-row items-center">
        <img :src="Address" alt="">
        91151642 | www.msp.com.au | melbourne@msp.com.au</div>
    </div>
  </footer> 
</template>

<script setup lang="ts">
import { getAssetUrl } from '@/helpers/image.helper';

  const Location = getAssetUrl(`images/location.svg`);
  const Address = getAssetUrl(`images/nearMe.svg`);

</script>

<style scoped>
   .fancy-border-top {
      border-width: 0.5rem;
      border-style: solid;
      border-image: linear-gradient(to right, #6059A9, #447AC7, #19ABD7, #8CE5D3, #F9F8EB, #FDCF8D, #FE9F6E, #FF6573, #FF456E) 1;
      border-bottom: none;
      border-left: none;
      border-right: none;
   }
</style>
