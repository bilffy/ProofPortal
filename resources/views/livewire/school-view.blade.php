<div class="relative overflow-x-auto">
    <div class="container3 p-4">
        <h3 class="mb-4">Welcome {{ $user->firstname }}! Let’s get started.</h3>

        <div class="flex w-full gap-4  min-h-[600px]">
            <div class=" w-full flex flex-col border rounded">
                <div class=" bg-neutral-200 p-4 w-full border-b"><h5>Header</h5></div>
                <div class="p-4 border-b">
                    <div class="flex flex-row justify-between mb-2">
                        <span class="font-semibold">Upload Finalized Photos</span>
                        <span class="font-semibold text-alert">Due date: 00/00/0000</span>
                    </div>
                    <div>Upload final images after proofing is complete</div>
                </div>
                <div class="p-4 border-b">
                    <div class="flex flex-row justify-between mb-2">
                        <span class="font-semibold">Review Photo Proofs for Class 5A</span>
                        <span class="font-semibold text-warning">Due date: 00/00/0000</span>
                    </div>
                    <div>Proof student photos and flag any issues.</div>
                </div>
                <div class="p-4 border-b">
                    <div class="flex flex-row justify-between mb-2">
                        <span class="font-semibold">Submit Edits for Student Portraits</span>
                        <span class="font-semibold text-warning">Due date: 00/00/0000</span>
                    </div>
                    <div>Make necessary changes to the selected photos before approval.</div>
                </div>
            </div>
            <div class="flex flex-col w-1/3 min-w-[500px] max-w-[500px] gap-4">
                <div class="flex flex-col w-full min-h-[300px] slideShow rounded-lg overflow-hidden relative">
                    <div class="flex flex-col mb-2">
                        <div class=" flex rounded overflow-hidden mb-2 h-[140px]">
                            <a href="https://www.msp.com.au/schools/yearbooks" alt="All-in-one Yearbook solution" target="_blank">
                                <img
                                        src="{{ Vite::asset('resources/assets/images/ads/1964138_Yearbooks.jpg') }}"
                                        alt="All-in-one Yearbook solution"
                                        height="100px"
                                        class="w-full h-fit"
                                />
                            </a>
                        </div>
                        <div class=" flex rounded overflow-hidden mb-2 h-[140px]">
                            <a href="https://www.msp.com.au/schools/marketing/virtualtours" alt="360 virtual tours" target="_blank">
                                <img
                                        src="{{ Vite::asset('resources/assets/images/ads/1964138_Virtual_Tours.jpg') }}"
                                        alt="360 virtual tours"
                                        height="100px"
                                        class="w-full h-fit"
                                />
                            </a>
                        </div>
                        <div class=" flex rounded overflow-hidden mb-2 h-[140px]">
                            <a href="https://www.msp.com.au/schools/marketing/design/" alt="Effective school marketing and promotions" target="_blank">
                                <img
                                        src="{{ Vite::asset('resources/assets/images/ads/1964138_Marketing.jpg') }}"
                                        alt="Effective school marketing and promotions"
                                        height="100px"
                                        class="w-full h-fit"
                                />
                            </a>
                        </div>
                        <div class=" flex rounded overflow-hidden mb-2 h-[140px]">
                            <a href="https://www.msp.com.au/schools/marketing/printing" alt="Branded stationery, promotional material, banners and more" target="_blank">
                                <img
                                        src="{{ Vite::asset('resources/assets/images/ads/1964138_Printing.jpg') }}"
                                        alt="Branded stationery, promotional material, banners and more"
                                        height="100px"
                                        class="w-full h-fit"
                                />
                            </a>
                        </div>
                    </div>
                    {{-- <div class="flex text-center absolute bottom-8 gap-1 w-full justify-center">
                        <div class="w-[12px] h-[12px] bg-neutral-400 rounded-full overflow-hidden"></div>
                        <div class="w-[12px] h-[12px] bg-neutral-400 rounded-full overflow-hidden"></div>
                        <div class="w-[12px] h-[12px] bg-neutral-400 rounded-full overflow-hidden"></div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    

    <style>
        .slideShow {
            /* background-color: red; */
            /* background-image: url("../assets/images/ads/1964138_p2.png"); */
        }
    </style>
    
</div>

