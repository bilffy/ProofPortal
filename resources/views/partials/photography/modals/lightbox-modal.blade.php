@props([
    'title' => "Lightbox Modal",
    'subject' => null,
    'schoolKey' => '',
    'externalSubjectId' => null,
])

<x-modal.lightbox id='lightbox-modal' :$title body="components.modal.body">
    <x-slot name="body">
        <x-modal.body class="max-h-[calc(100vh-12rem)] overflow-y-auto">
            <div class="flex justify-between items-center">
                <h3 class="text-sm text-gray-900 font-semibold dark:text-white">
                    MSP Photography Timeline
                </h3>
                <div id="lb-download-section" class="h-full flex align-middle justify-center items-center gap-4">
                    <x-button.primary-inverse id="btn-lb-download-clear" hollow class="border-none hidden transition-none hover:transition-none" onclick="resetImages(true)">Cancel Selection</x-button.primary-inverse>
                    <x-button.primary 
                            id="btn-lb-download"
                            onclick="showOptionsDownloadRequest(true)"
                    >
                        Download All
                    </x-button.primary>
                </div>
            </div>
            <livewire:photography.lightbox :$subject :$schoolKey :$externalSubjectId/>
        </x-modal.body>
    </x-slot>
</x-modal.lightbox>

