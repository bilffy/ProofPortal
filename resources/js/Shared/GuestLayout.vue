<template>
    <div class="flex items-center justify-center p-6 min-h-screen">
        <div class="w-full max-w-md">
            <!-- <logo class="block mx-auto w-full max-w-xs fill-white" height="50" /> -->
            <div class="flex align-middle justify-center">
                <img src="../../assets/images/MSP-Logo_400x400.png" alt="">
            </div>
            <main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
                <slot/>
            </main>
        </div>
    </div>
</template>
