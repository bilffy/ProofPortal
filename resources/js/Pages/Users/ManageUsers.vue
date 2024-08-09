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
                          class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 bg-[#F5F7FA]" 
                          placeholder="Search..." 
                          required
                          v-model="queries.search"
                        />
                    </div>
                </form>
              <div class="ml-4 mr-4 border-r-2 border-[#D9DDE2] my-3"></div>
              <ButtonPrimary @click="router.get(route('users.create'))">Add New User</ButtonPrimary>
            </div>
        </div>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead>
                    <tr>
                        <TableHeaderCell id="email" :sort="queries.sort" :filterable="false" @sort-with-field="sortList">Email</TableHeaderCell>
                        <TableHeaderCell id="firstname" :sort="queries.sort" :filterable="false" @sort-with-field="sortList">First Name</TableHeaderCell>
                        <TableHeaderCell id="lastname" :sort="queries.sort" :filterable="false" @sort-with-field="sortList">Last Name</TableHeaderCell>
                        <TableHeaderCell id="role">Role</TableHeaderCell>
                        <TableHeaderCell id="organization">Franchise/School</TableHeaderCell>
                        <TableHeaderCell id="status">User Status</TableHeaderCell>
                        <TableHeaderCell class="w-[60px]" :sortable="false" :filterable="false"></TableHeaderCell>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="({ email, firstname, lastname }) in users">
                        <TableCell>{{ email }}</TableCell>
                        <TableCell>{{ firstname }}</TableCell>
                        <TableCell>{{ lastname }}</TableCell>
                        <TableCell>[role]</TableCell>
                        <TableCell>[school/franchise]</TableCell>
                        <TableCell>[status]</TableCell>
                        <TableCell class="w-[100px]">
                            <ButtonLink>
                                <img :src="moreImageUrl" alt="">
                            </ButtonLink>
                        </TableCell>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="w-full flex items-center justify-center py-4">
            <Pagination :pagination="{...pagination}" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { computed, ref, watch, type Ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Shared/AuthenticatedLayout.vue';
import ButtonPrimary from '@/components/Button/ButtonPrimary.vue';
import ButtonLink from '@/components/Button/ButtonLink.vue';
import TableHeaderCell from '@/components/Table/TableHeaderCell.vue';
import TableCell from '@/components/Table/TableCell.vue';
import { getImgAssetUrl } from '@/helpers/image.helper';
import Pagination from '@/components/Pagination.vue';
import type { PaginatedList }  from '@/types/pagination.type';
import { User } from '@/models/User.model';

let delayTimeout: any;
let delayTime: number;

const moreImageUrl = getImgAssetUrl('more.svg');
const {...pageProps} = usePage().props;
const {data: users, ...pagination}: PaginatedList<User> = pageProps.results as PaginatedList<User>;

const queries: Ref<any> = ref({
  sort: '',
  search: '',
  page: 1,
  ...route().queryParams
});

let usersUrl = computed(() => {
    let url = new URL(route('users.manage'));
    const queryValue = queries.value;
    delayTime = 0;
    if (queryValue.search) {
        url.searchParams.append('search', queryValue.search);
        url.searchParams.delete('page');
        delayTime = 200;
    }
    if (queryValue.sort) {
        url.searchParams.append('sort', queryValue.sort);
    }
    return url;
});

watch(
    () => usersUrl.value,
    (url) => {
        clearTimeout(delayTimeout);
        delayTimeout = setTimeout(() => {
            router.visit(url, {
                preserveScroll: true,
                // preserveState: true,
                replace: true
            });
        }, delayTime);
    }
);

function sortList(val: any) {
    queries.value.sort = val;
}
</script>
