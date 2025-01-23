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
                <x-button.primary id="btn-download" onclick="submitDownloadRequest()">Download All</x-button.primary>
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
        if (selectedImages.length === 0) {
            alert('Please select images to download');
            return;
        }

        try {
            const response = await fetch('{{ route('photography.request-download') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(
                    { 
                        images: selectedImages,
                        category: selectedImages.length > 1 ? 'Bulk' : 'Individual'
                    }
                )
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            console.log('Download request successful:', result);
            // reload the page and show a success message
            alert('Download request submitted successfully');
            window.location.reload();
        } catch (error) {
            console.error('Error submitting download request:', error);
        }
    }

    window.submitDownloadRequest = submitDownloadRequest;
    
</script>
@endpush
