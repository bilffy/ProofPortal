@extends('layouts.authenticated')

@section('content')
    <div x-data id="photography-root" class="container3 p-4">
        <x-tabs.tabContainer tabsWrapper="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tab id="configure" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Configure</x-tabs.tab>
            @endrole
            <x-tabs.tab id="portraits" isActive="{{$currentTab == 'portraits'}}" route="{{route('photography.portraits')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Portraits</x-tabs.tab>
            <x-tabs.tab id="groups" isActive="{{$currentTab == 'groups'}}" route="{{route('photography.groups')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Groups</x-tabs.tab>
            <x-tabs.tab id="others" isActive="{{$currentTab == 'others'}}" route="{{route('photography.others')}}" click="$dispatch('{{$PhotographyHelper::EV_CHANGE_TAB}}')">Others</x-tabs.tab>
            <div class="absolute right-2 h-full flex align-middle justify-center items-center gap-4">
                <x-button.primary id="btn-download-clear" hollow class="border-none hidden" onclick="resetImages()">Clear Selection</x-button.primary>
                <x-button.primary 
                        id="btn-download" 
                        onclick="showOptionsDownloadRequest()"
                        >Download All
                </x-button.primary>
            </div>
        </x-tabs.tabContainer>
        <x-tabs.tabContentContainer id="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tabContent id="configure">
                    @include('partials.photography.configure')
                </x-tabs.tabContent>
            @endrole
            <x-tabs.tabContent id="portraits">
                @include('partials.photography.portraits')
            </x-tabs.tabContent>
            <x-tabs.tabContent id="groups">
                @include('partials.photography.groups')
            </x-tabs.tabContent>
            <x-tabs.tabContent id="others">
                @include('partials.photography.others')
            </x-tabs.tabContent>
        </x-tabs.tabContentContainer>

        <x-modal.base id="showOptionsDownloadModal" title="Download Options" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <div><b>Selections required</b></div>
                    <p>Please select the resolution and folder structure for your images</p>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <select id="image_res" class="input">
                                <option value="low">Small/Low Res (72 DPI - suitable for viewing on screen)</option>
                                <option value="high">High Res (300 DPI - suitable for printing)</option>
                            </select>
                        </div>
                    </div>
                    <p>Select folder format</p>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <select id="folder_format" class="input">
                                <option value="all">All images in one folder</option>
                                <option value="organize">Organise images in folders</option>
                            </select>
                        </div>
                    </div>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.secondary data-modal-hide="showOptionsDownloadModal">Cancel</x-button.secondary>
                    <x-button.primary onclick="showConfirmDownloadRequest()" id="show-confirm-download-btn">Download</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        
        <x-modal.base id="confirmDownloadModal" title="Download Request" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="confirm-download-body"></p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.secondary data-modal-hide="confirmDownloadModal">Cancel</x-button.secondary>
                    <x-button.primary onclick="submitDownloadRequest()"  id="confirm-download-btn">Download</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        <x-modal.base id="successDownloadModal" title="Download Request" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="success-download-body">Download request successful</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.primary data-modal-hide="successDownloadModal">Ok</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
    </div>
@endsection

@push('scripts')
<script type="module">
    
    function updateImageState(imgCheckbox, isSelected) {
        let checkIcon = imgCheckbox.querySelector('i');
        if (isSelected) {
            imgCheckbox.classList.add('bg-white');
            checkIcon.classList.remove('hidden');
        } else {
            imgCheckbox.classList.remove('bg-white');
            checkIcon.classList.add('hidden');
        }
    }

    function updateDownloadSection(selectedCount) {
        const downloadBtn = document.querySelector('#btn-download');
        const clearDownloadBtn = document.querySelector('#btn-download-clear');

        if (selectedCount > 0) {
            clearDownloadBtn.classList.remove('hidden');
            downloadBtn.innerHTML = `Download Selected (${selectedCount})`;
        } else {
            clearDownloadBtn.classList.add('hidden');
            downloadBtn.innerHTML = 'Download All';
        }
    }
    
    function updateDownloadSelection() {
        const images = JSON.parse(localStorage.getItem('selectedImages'));
        const portaits = document.querySelectorAll('.portrait-img');

        portaits.forEach(img => {
            let checkbox = img.querySelector('.portrait-img-checkbox');
            updateImageState(checkbox, images.includes(img.id));
        });

        updateDownloadSection(images.length);
    }

    function resetImages() {
        window.localStorage.setItem('selectedImages', JSON.stringify([]));
        updateDownloadSelection();
    }

    function handleImageClick(imageId) {
        let selectedItems = window.localStorage.getItem('selectedImages');
        selectedItems = selectedItems ? JSON.parse(selectedItems) : [];

        const isAlreadySelected = selectedItems.includes(imageId);
        const img = document.querySelector(`#${imageId}`);

        if (isAlreadySelected) {
            selectedItems = selectedItems.filter(item => item !== imageId);
        } else {
            selectedItems.push(imageId);
        }
        
        window.localStorage.setItem('selectedImages', JSON.stringify(selectedItems));

        updateImageState(img.querySelector('.portrait-img-checkbox'), !isAlreadySelected);
        updateDownloadSection(selectedItems.length);

        console.log('Selected Images:', selectedItems);
        
    }
    
    window.resetImages = resetImages;
    window.handleImageClick = handleImageClick;
    window.updateDownloadSelection = updateDownloadSelection;
    resetImages();
    
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-button');
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                // reset images selected
                resetImages();
                const url = tab.getAttribute('href');
                history.pushState({ path: url }, '', url);
            });
        });
    });
    
    function showOptionsDownloadRequest() {
        if ($(".grid").attr('total-image-count') != 0) {
            showOptionsDownloadModal.show();
        } else {
            alert('No images found');
        }
    }
    
    function showConfirmDownloadRequest() {
        showOptionsDownloadModal.hide()
        confirmDownloadRequest();
    }
    
    function confirmDownloadRequest() {
        const selectedImages = JSON.parse(localStorage.getItem('selectedImages'));
        if ($(".grid").attr('total-image-count') != 0) {
            confirmDownloadModal.show();
            const selectedImagesLength = selectedImages.length === 0 ? $(".grid").attr('total-image-count') : selectedImages.length;
            $('#confirm-download-body').html("Are you sure you want to download <b>" + selectedImagesLength + "</b> images?");
        } else {
            alert('No images found');
        }
    }
    
    const confirmDownloadModal = new Modal(document.getElementById('confirmDownloadModal'));
    const successDownloadModal = new Modal(document.getElementById('successDownloadModal'));
    const showOptionsDownloadModal = new Modal(document.getElementById('showOptionsDownloadModal'));
    
    
    window.addEventListener('image-frame-updated', event => {
        setTimeout(() => {
            const images = JSON.parse(localStorage.getItem('selectedImages'));
            const imageId = `img_${event.detail[0]['imageId']}`;
            const img = document.querySelector(`#${imageId}`);
            let checkbox = img.querySelector('.portrait-img-checkbox');
            updateImageState(checkbox, images.includes(imageId));
        }, 50);
    });
    
    
    
    async function submitDownloadRequest() {
        const selectedImages = JSON.parse(localStorage.getItem('selectedImages'));
        try {
            const downloadBtn = document.querySelector('#confirm-download-btn');
            const selectedYear = $('#select_portaits_year').val();
            const selectedView = $('#select_portaits_view').val();
            const selectedClass = $('#select_portaits_class').val();
            const INDIVIDUAL_CATEGORY = 1;
            const BULK_CATEGORY = 2;
            
            console.log('Selected Year:', selectedYear);
            console.log('Selected View:', selectedView);
            console.log('Selected Class:', JSON.stringify(selectedClass));
            
            $(downloadBtn).html(`<x-spinner.button />`);
            const response = await fetch('{{ route('photography.request-download') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(
                    { 
                        images: selectedImages,
                        category: selectedImages.length === 1 ? INDIVIDUAL_CATEGORY : BULK_CATEGORY,
                        filters: {
                            year: selectedYear,
                            view: selectedView,
                            class: JSON.stringify(selectedClass),
                            resolution: $('#image_res').val(),
                            folder_format: $('#folder_format').val()
                        }
                    }
                )
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            //console.log('Download request successful:', result);
            
            // only if selected image is one
            if (selectedImages.length === 1) {
                const imgElement = document.createElement('a');
                imgElement.href = `data:image/jpeg;base64,${result['data']}`;
                imgElement.download = `${Math.random().toString(36).substring(2, 15)}.jpg`;
                imgElement.click();
            }
            
            $(downloadBtn).html("Download");
            resetImages();
            document.querySelector('[data-modal-hide="confirmDownloadModal"]').click();
            successDownloadModal.show();
        } catch (error) {
            console.error('Error submitting download request:', error);
        }
    }
    
    window.showOptionsDownloadRequest = showOptionsDownloadRequest;
    window.showConfirmDownloadRequest = showConfirmDownloadRequest;
    window.submitDownloadRequest = submitDownloadRequest;
    window.confirmDownloadRequest = confirmDownloadRequest;
    
</script>
@endpush
