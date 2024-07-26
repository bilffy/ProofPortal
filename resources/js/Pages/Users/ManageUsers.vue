<template>
  <Layout class="place-content-center">
    <div class="py-4 flex items-center justify-between">
      <h3 class="text-3xl">Title</h3>
      <div class="flex justify-center">
        <form class="max-w-md mx-auto">
            <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
            <div class="relative ">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input 
                  type="search" 
                  id="default-search" 
                  class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-gray-50 dark:text-white bg-[#F5F7FA]" 
                  placeholder="Search..." 
                  required />
            </div>
        </form>
        <div class="ml-4 mr-4 border-r-2 border-[#D9DDE2] my-3"></div>
        <ButtonPrimary>Add New user</ButtonPrimary>
      </div>
    </div>
    <div>
      <div class="Table w-full">
        <div class="TableRow TableHeader flex flex-row justify-evenly">
          <TableHeaderCell>Email</TableHeaderCell>
          <TableHeaderCell>First Name</TableHeaderCell>
          <TableHeaderCell>Last Name</TableHeaderCell>
          <TableHeaderCell>Role</TableHeaderCell>
          <TableHeaderCell>Franchise/School</TableHeaderCell>
          <TableHeaderCell>User Status</TableHeaderCell>
          <TableHeaderCellEmpty></TableHeaderCellEmpty>
        </div>
        <div class="TableRow flex flex-row">
          <TableCell>email1@msp.com</TableCell>
          <TableCell>Adelaide</TableCell>
          <TableCell>Hernandez</TableCell>
          <TableCell>School Admin</TableCell>
          <TableCell>MSP Adelaide South</TableCell>
          <TableCell>New</TableCell>
          <TableCellMore>
            <ButtonPrimary>test
              <img width="16px" class="w-4 h-4" src="../../../Images/more.png" alt="">
            </ButtonPrimary>
          </TableCellMore>
        </div>
      </div>
    </div>
    <h1 class="text-3xl font-bold underline col-span-2 ">Welcome!</h1>
    <h2 class="bg-color-200">Test component: </h2>
    
    <div class="p-4">Main Footer</div>
  </Layout>
</template>

<script lang="ts" setup>
  import Layout from '../../Shared/Layout.vue'
  import ButtonPrimary from '../../components/widgets/Button/ButtonPrimary.vue';

  import TableHeaderCell from '../../components/widgets/Table/TableHeaderCell.vue';
  import TableHeaderCellEmpty from '../../components/widgets/Table/TableHeaderCellEmpty.vue';
  import TableCell from '../../components/widgets/Table/TableCell.vue';
  import TableCellMore from '../../components/widgets/Table/TableCellMore.vue';

</script>

<style scoped>
</style>
