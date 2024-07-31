<template>
    <AuthenticatedLayout class="place-content-center">
      <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Manage Users</h3>
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
                    class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-gray-50 bg-[#F5F7FA]" 
                    placeholder="Search..." 
                    required />
              </div>
          </form>
          <div class="ml-4 mr-4 border-r-2 border-[#D9DDE2] my-3"></div>
          <ButtonPrimary>Add New User</ButtonPrimary>
        </div>
      </div>
      <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right">
          <thead>
            <tr>
              <TableHeaderCell>Email</TableHeaderCell>
              <TableHeaderCell>First Name</TableHeaderCell>
              <TableHeaderCell>Last Name</TableHeaderCell>
              <TableHeaderCell>Role</TableHeaderCell>
              <TableHeaderCell>Franchise/School</TableHeaderCell>
              <TableHeaderCell>User Status</TableHeaderCell>
              <TableHeaderCell class="w-[60px]"></TableHeaderCell>
            </tr>
          </thead>
          <tbody>
            <tr>
              <TableCell>email1@msp.com</TableCell>
              <TableCell>Adelaide</TableCell>
              <TableCell>Hernandez</TableCell>
              <TableCell>School Admin</TableCell>
              <TableCell>MSP Adelaide South</TableCell>
              <TableCell>New</TableCell>
              <TableCell class="w-[100px]">
                <ButtonLink>
                  <img :src="moreImageUrl" alt="">
                </ButtonLink>
              </TableCell>
            </tr>
            <tr>
              <TableCell scope="row">email1@msp.com</TableCell>
              <TableCell scope="row">Adelaide</TableCell>
              <TableCell scope="row">Hernandez</TableCell>
              <TableCell scope="row">School Admin</TableCell>
              <TableCell scope="row">MSP Adelaide South</TableCell>
              <TableCell scope="row">New</TableCell>
              <TableCell scope="row" class="w-[100px]">
                <ButtonLink class="bg-none">
                  <img :src="moreImageUrl" alt="">
                </ButtonLink>
              </TableCell>
            </tr>
            <tr>
              <TableCell scope="row">email1@msp.com</TableCell>
              <TableCell scope="row">Adelaide</TableCell>
              <TableCell scope="row">Hernandez</TableCell>
              <TableCell scope="row">School Admin</TableCell>
              <TableCell scope="row">MSP Adelaide South</TableCell>
              <TableCell scope="row">New</TableCell>
              <TableCell scope="row" class="w-[100px]">
                <ButtonLink>
                  <img :src="moreImageUrl" alt="">
                </ButtonLink>
              </TableCell>
            </tr>
            <tr>
              <TableCell scope="row">email1@msp.com</TableCell>
              <TableCell scope="row">Adelaide</TableCell>
              <TableCell scope="row">Hernandez</TableCell>
              <TableCell scope="row">School Admin</TableCell>
              <TableCell scope="row">MSP Adelaide South</TableCell>
              <TableCell scope="row">New</TableCell>
              <TableCell scope="row" class="w-[100px]">
                <ButtonLink>
                  <img :src="moreImageUrl" alt="">
                </ButtonLink>
              </TableCell>
            </tr>
            <tr>
              <TableCell scope="row">email1@msp.com</TableCell>
              <TableCell scope="row">Adelaide</TableCell>
              <TableCell scope="row">Hernandez</TableCell>
              <TableCell scope="row">School Admin</TableCell>
              <TableCell scope="row">MSP Adelaide South</TableCell>
              <TableCell scope="row">New</TableCell>
              <TableCell scope="row" class="w-[100px]">
                <ButtonLink class="bg-none">
                  <img :src="moreImageUrl" alt="">
                </ButtonLink>
              </TableCell>
            </tr>
          </tbody>
        </table>
      </div>

        <div class="w-full flex items-center justify-center py-4">

        <nav aria-label="Page navigation example">
          <ul class="inline-flex -space-x-px text-sm">
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white rounded-s-lg hover:bg-gray-100 hover:text-gray-700"><img :src="chevronLeftUrl" alt=""> Previous</a>
            </li>
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700">1</a>
            </li>
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700">2</a>
            </li>
            <li>
              <a href="#" aria-current="page" class="flex items-center justify-center px-3 h-8 text-blue-600 bg-[#e9e6e3] hover:bg-blue-100 hover:text-blue-700">3</a>
            </li>
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700">4</a>
            </li>
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700">5</a>
            </li>
            <li>
              <a href="#" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white rounded-e-lg hover:bg-gray-100 hover:text-gray-700">Next <img :src="chevronRightUrl" alt=""></a>
            </li>
          </ul>
        </nav>

      </div>
      <!-- <div class="p-4">Main Footer</div> -->
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Shared/AuthenticatedLayout.vue';
import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
import ButtonLink from '@/components/Button/ButtonLink.vue';
import TableHeaderCell from '@/components/Table/TableHeaderCell.vue';
import TableCell from '@/components/Table/TableCell.vue';
import { getAssetUrl } from '@/helpers/image.helper';

const moreImageUrl = getAssetUrl('images/more.svg');
const chevronLeftUrl = getAssetUrl('images/Chevron_Left.png');
const chevronRightUrl = getAssetUrl('images/Chevron_Right.png');
</script>
