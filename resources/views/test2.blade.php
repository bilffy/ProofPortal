@extends('layouts.authenticated')

@section('content')

    <div class="container3 p-4">
        <h3 class="mb-4">Welcome Alice! Let’s get started.</h3>

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
                        <span class="font-semibold">Upload Finalized Photos</span>
                        <span class="font-semibold text-alert">Due date: 00/00/0000</span>
                    </div>
                    <div>Upload final images after proofing is complete</div>
                </div>
                <div class="p-4 border-b">
                    <div class="flex flex-row justify-between mb-2">
                        <span class="font-semibold">Upload Finalized Photos</span>
                        <span class="font-semibold text-alert">Due date: 00/00/0000</span>
                    </div>
                    <div>Upload final images after proofing is complete</div>
                </div>
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
                        <span class="font-semibold">Review Photo Proofs for Class 5A</span>
                        <span class="font-semibold text-warning">Due date: 00/00/0000</span>
                    </div>
                    <div>Proof student photos and flag any issues.</div>
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
            <div class="flex flex-col w-1/3 min-w-[400px] max-w-[400px] gap-4">
                <div class="flex flex-col w-full min-h-[300px] slideShow rounded-lg overflow-hidden relative">
                    <div class="flex flex-col mb-2">
                        <div class=" flex rounded overflow-hidden h-[140px] w-[400px] mb-4">
                            <img 
                            src="{{ Vite::asset('resources/assets/images/ads/1964138_Yearbooks.jpg') }}" 
                            alt=""
                            height="140px"
                            class="w-full"
                            />
                        </div>
                        <div class=" flex rounded overflow-hidden h-[140px] w-[400px] mb-4">
                            <img 
                            src="{{ Vite::asset('resources/assets/images/ads/1964138_Virtual Tours.jpg') }}" 
                            alt=""
                            height="140px"
                            class="w-full"
                            />
                        </div>
                        <div class=" flex rounded overflow-hidden h-[140px] w-[400px] mb-4">
                            <img 
                            src="{{ Vite::asset('resources/assets/images/ads/1964138_Printing.jpg') }}" 
                            alt=""
                            height="140px"
                            class="w-full"
                            />
                        </div>
                        <div class=" flex rounded overflow-hidden h-[140px] w-[400px] mb-4">
                            <img 
                            src="{{ Vite::asset('resources/assets/images/ads/1964138_Marketing.jpg') }}" 
                            alt=""
                            height="140px"
                            class="w-full"
                            />
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

@endsection

<style>
    .slideShow {
        /* background-color: red; */
        /* background-image: url("../assets/images/ads/1964138_p2.png"); */
    }
</style>