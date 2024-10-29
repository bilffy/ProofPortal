@extends('layouts.authenticated')

@section('content')

    <div class="container3 p-4">
        <h3 class="mb-4">Welcome Alice! Let’s get started.</h3>

        <div class="flex w-full gap-4  min-h-[600px]">
            <div class="w-full flex flex-col border rounded">
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
            <div class="flex flex-col w-full gap-4">
                <div class="flex flex-col w-full min-h-[300px] bg-neutral-200 p-4">
                    <div class=" bg-primary flex mb-2">
                        <div class=" flex h-full">
                            <img src="https://placehold.co/600x250" alt="">
                        </div>
                    </div>
                    <div class="text-center">
                        Pagination
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

