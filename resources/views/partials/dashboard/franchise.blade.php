<x-tabs.tabTest/>
<div class="flex flex-row gap-5">
    <div class=" border-neutral-300 border-[1px] h-full w-3/4 rounded-md overflow-hidden">
        <div class="p-4 flex items-center justify-between">
            <h3 class="text-2xl">Title</h3>
            <div class="flex justify-center">
                <form class="max-w-md mx-auto">
                    <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">Search</label>
                    <div class="relative ">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <x-icon icon="search"/>
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
                        <x-table.headerCell>School Key</x-table.headerCell>
                        <x-table.headerCell>School Name</x-table.headerCell>
                        <x-table.headerCell class="w-[60px]"></x-table.headerCell>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <x-table.cell>email1@msp.com</x-table.cell>
                        <x-table.cell>Adelaide</x-table.cell>
                        <x-table.cell class="w-[100px]">
                            <x-button.link>
                                <img :src="moreImageUrl" alt="">
                            </x-button.link>
                        </x-table.cell>
                    </tr>
                    <tr>
                        <x-table.cell scope="row">email1@msp.com</x-table.cell>
                        <x-table.cell scope="row">Adelaide</x-table.cell>
                        <x-table.cell scope="row" class="w-[100px]">
                            <x-button.link class="bg-none">
                                <img :src="moreImageUrl" alt="">
                            </x-button.x-button.link>
                        </x-table.cell>
                    </tr>
                    <tr>
                        <x-table.cell scope="row">email1@msp.com</x-table.cell>
                        <x-table.cell scope="row">Adelaide</x-table.cell>
                        <x-table.cell scope="row" class="w-[100px]">
                            <x-button.link>
                                <img :src="moreImageUrl" alt="">
                            </x-button.link>
                        </x-table.cell>
                    </tr>
                    <tr>
                        <x-table.cell scope="row">email1@msp.com</x-table.cell>
                        <x-table.cell scope="row">Adelaide</x-table.cell>
                        <x-table.cell scope="row" class="w-[100px]">
                            <x-button.link>
                                <img :src="moreImageUrl" alt="">
                            </x-button.link>
                        </x-table.cell>
                    </tr>
                    <tr>
                        <x-table.cell scope="row">email1@msp.com</x-table.cell>
                        <x-table.cell scope="row">Adelaide</x-table.cell>
                        <x-table.cell scope="row" class="w-[100px]">
                            <x-button.link class="bg-none">
                                <img :src="moreImageUrl" alt="">
                            </x-button.link>
                        </x-table.cell>
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
