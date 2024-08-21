<template>
    <div class="flex flex-row gap-5">
        
        <div class=" border-neutral-300 border-[1px] h-full w-3/4 rounded-md overflow-hidden">
            <div class="p-4 flex items-center justify-between">
                <h3 class="">Title</h3>
                <div class="flex justify-center">
                    <form class="max-w-md mx-auto">
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">Search</label>
                        <div class="relative ">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input 
                            type="search" 
                            id="default-search" 
                            class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300" 
                            placeholder="Search..." 
                            required />
                        </div>
                    </form>
                </div>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right">
                    <thead>
                        <tr>
                            <TableHeaderCell>School Key</TableHeaderCell>
                            <TableHeaderCell>School Name</TableHeaderCell>
                            <TableHeaderCell class="w-[60px]"></TableHeaderCell>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <TableCell>email1@msp.com</TableCell>
                            <TableCell>Adelaide</TableCell>
                            <TableCell class="w-[100px]">
                                <ButtonLink>
                                    <img :src="moreImageUrl" alt="">
                                </ButtonLink>
                            </TableCell>
                        </tr>
                        <tr>
                            <TableCell scope="row">email1@msp.com</TableCell>
                            <TableCell scope="row">Adelaide</TableCell>
                            <TableCell scope="row" class="w-[100px]">
                                <ButtonLink class="bg-none">
                                    <img :src="moreImageUrl" alt="">
                                </ButtonLink>
                            </TableCell>
                        </tr>
                        <tr>
                            <TableCell scope="row">email1@msp.com</TableCell>
                            <TableCell scope="row">Adelaide</TableCell>
                            <TableCell scope="row" class="w-[100px]">
                                <ButtonLink>
                                    <img :src="moreImageUrl" alt="">
                                </ButtonLink>
                            </TableCell>
                        </tr>
                        <tr>
                            <TableCell scope="row">email1@msp.com</TableCell>
                            <TableCell scope="row">Adelaide</TableCell>
                            <TableCell scope="row" class="w-[100px]">
                                <ButtonLink>
                                    <img :src="moreImageUrl" alt="">
                                </ButtonLink>
                            </TableCell>
                        </tr>
                        <tr>
                            <TableCell scope="row">email1@msp.com</TableCell>
                            <TableCell scope="row">Adelaide</TableCell>
                            <TableCell scope="row" class="w-[100px]">
                                <ButtonLink class="bg-none">
                                    <img :src="moreImageUrl" alt="">
                                </ButtonLink>
                            </TableCell>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class=" border-neutral-300 border-[1px] h-full w-1/4 rounded-md overflow-hidden">
            <div class="flex flex-row items-center border-b-[1px] border-b-neutral-300 p-4">
                <span class="font-semibold text-neutral-600">My Tasks</span>
            </div>
            <div class="border-b-[1px] border-b-neutral-300 p-4">
                <p class="font-semibold ">Task 1</p>
                <div class="text-neutral-600">
                    <p class="text-neutral-600">This is the task description</p>
                </div>
            </div>
            <div class="border-b-[1px] border-b-neutral-300 p-4">
                <p class="font-semibold">Task 2</p>
                <div class="text-neutral-600">
                    <ul class="list-disc ml-8">
                        <li>sub task 1</li>
                        <li>sub task 2</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</template>
  
  <script setup lang="ts">
  import TableCell from '@/components/Table/TableCell.vue';
  import TableHeaderCell from '@/components/Table/TableHeaderCell.vue';
  import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
  </script>
  