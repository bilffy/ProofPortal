@extends('layouts.authenticated')

@php
    $highResDownloadOption = $AppSettingsHelper::getByPropertyKey('high_res_download_option');
    $lowResDownloadOption = $AppSettingsHelper::getByPropertyKey('low_res_download_option');
    $groupsTab = $AppSettingsHelper::getByPropertyKey('groups_tab');
    $otherTab = $AppSettingsHelper::getByPropertyKey('other_tab');
    $configureTabV1 = $AppSettingsHelper::getByPropertyKey('configure_tab_v1');
    $configureTabV2 = $AppSettingsHelper::getByPropertyKey('configure_tab_v2');
    
    $highResDownloadOptionValue = $highResDownloadOption ? $highResDownloadOption->property_value === 'true' ? true : false : true; 
    $lowResDownloadOptionValue = $lowResDownloadOption ? $lowResDownloadOption->property_value === 'true' ? true : false : true;
    $groupsTabValue = $groupsTab ? $groupsTab->property_value === 'true' ? true : false : true;
    $otherTabValue = $otherTab ? $otherTab->property_value === 'true' ? true : false : true;
    $configureTabV1Value = $configureTabV1 ? $configureTabV1->property_value === 'true' ? true : false : true;
    $configureTabV2Value = $configureTabV2 ? $configureTabV2->property_value === 'true' ? true : false : true;
    
    // check if any of $highResDownloadOptionValue or $lowResDownloadOptionValue is false disable the resolution selection
    $isDisabledResolution = !$highResDownloadOptionValue || !$lowResDownloadOptionValue;
    
    
@endphp

@section('content')
    <div x-data id="photography-root" class="container3 p-4">
        <x-tabs.tabContainer tabsWrapper="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                @if ($configureTabV1Value)
                    <x-tabs.tab id="configure" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure')}}">Configure</x-tabs.tab>
                @endif
                @if ($configureTabV2Value)
                    <x-tabs.tab id="configure-new" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure-new')}}">Configure-new</x-tabs.tab>
                @endif
            @endrole
            <x-tabs.tab id="portraits" isActive="{{$currentTab == 'portraits'}}" route="{{route('photography.portraits')}}">Portraits</x-tabs.tab>
            @if ($groupsTabValue)    
                <x-tabs.tab id="groups" isActive="{{$currentTab == 'groups'}}" route="{{route('photography.groups')}}">Groups</x-tabs.tab>
            @endif
            @if ($otherTabValue)
                <x-tabs.tab id="others" isActive="{{$currentTab == 'others'}}" route="{{route('photography.others')}}">Others</x-tabs.tab>
            @endif
            <div id="download-section" class="absolute right-2 h-full flex align-middle justify-center items-center gap-4 {{$currentTab == 'configure' ? 'hidden' : ''}}">
                <x-button.primary id="btn-download-clear" hollow class="border-none hidden" onclick="resetImages()">Clear Selection</x-button.primary>
                <x-button.primary 
                        id="btn-download"
                        onclick="showOptionsDownloadRequest()"
                >
                    Download All
                </x-button.primary>
            </div>
        </x-tabs.tabContainer>
        <x-tabs.tabContentContainer id="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                <x-tabs.tabContent id="configure">
                    @include('partials.photography.configure')
                </x-tabs.tabContent>
                <x-tabs.tabContent id="configure-new">
                    @include('partials.photography.configure-new')
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

        <x-modal.base id="showOptionsDownloadModal" title="{{ $configMessages['options']['title']  }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <input type="hidden" id="nonce" name="nonce" value="">
                    <div><b>{{ $configMessages['options']['sub_title']  }}</b></div>
                    <div @if (!$lowResDownloadOptionValue && !$highResDownloadOptionValue) class="hidden" @endif>
                        <p>{{ $configMessages['options']['resolution_selection']  }}</p>
                        <div class="flex flex-col gap-4 @if ($isDisabledResolution) opacity-50 @endif">
                            <div class="flex flex-col gap-2">
                                <select id="image_res" class="input" @if ($isDisabledResolution) disabled @endif>
                                    @if ($lowResDownloadOptionValue)
                                        <option value="low">Small/Low Res (72 DPI - suitable for viewing on screen)</option>
                                    @endif
                                    @if ($highResDownloadOptionValue)
                                        <option value="high">High Res (300 DPI - suitable for printing)</option>
                                    @endif    
                                </select>
                            </div>
                        </div>
                    </div>    
                    <div id="folder_format_selection">
                        <p>{{ $configMessages['options']['folder_format_selection']  }}</p>
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <select id="folder_format" class="input">
                                    <option value="all">All images in one folder</option>
                                    <option value="organize">Organise images in folders</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="filename_format_selection">
                        <p>{{ $configMessages['options']['filename_format_selection'] }}</p>
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <select id="filename_format" class="input">
                                </select>
                            </div>
                        </div>
                    </div>  
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.secondary data-modal-hide="showOptionsDownloadModal">Cancel</x-button.secondary>
                    <x-button.primary onclick="showConfirmDownloadRequest()" id="show-confirm-download-btn">Next</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        
        <x-modal.base id="confirmDownloadModal" title="{{ $configMessages['request']['title']  }}" body="components.modal.body" footer="components.modal.footer">
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
        <x-modal.base id="successDownloadModal" title="{{ $configMessages['request']['title']  }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="success-download-body">{{ $configMessages['request']['success']  }}</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.primary data-modal-hide="successDownloadModal">Dismiss</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        <x-modal.base id="confirmReloadPageModal" title="{{ config('app.dialog_config.configuration.reload.title') }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="success-download-body">{{ config('app.dialog_config.configuration.reload.message') }}</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.primary onclick="reloadPage()" data-modal-hide="confirmReloadPageModal">Continue</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
    </div>
@endsection

@push('scripts')
<script type="module">
    import { decryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}"
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
        const portraits = document.querySelectorAll('.portrait-img');

        portraits.forEach(img => {
            let checkbox = img.querySelector('.portrait-img-checkbox');
            updateImageState(checkbox, images.includes(img.id));
        });

        updateDownloadSection(images.length);
    }

    function resetImages() {
        window.localStorage.setItem('selectedImages', JSON.stringify([]));
        updateDownloadSelection();
    }

    function setCategory(category) {
        window.localStorage.setItem('photographyCategory', category);
        Livewire.dispatch('EV_CHANGE_TAB', { category: category });
    }

    const debouncedSetCategory = debounce((data) => {
        setCategory(data)
    }, 300);

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

    function getActiveTabId() {
        const activeTab = document.querySelector('.tab-button[aria-selected="true"]');
        return activeTab ? activeTab.id : null;
    }
    
    window.resetImages = resetImages;
    window.handleImageClick = handleImageClick;
    window.updateDownloadSelection = updateDownloadSelection;
    resetImages();
    
    document.addEventListener('DOMContentLoaded', () => {
        window.localStorage.removeItem('reloadPhotography');
        const tabs = document.querySelectorAll('.tab-button');
        const activeTabId = getActiveTabId();
        switch (activeTabId) {
            case 'portraits-tab':
                debouncedSetCategory('PORTRAITS');
                break;
            case 'groups-tab':
                debouncedSetCategory('GROUPS');
                break;
            case 'others-tab':
                debouncedSetCategory('OTHERS');
                break;
        }
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const url = tab.getAttribute('href');
                history.pushState({ path: url }, '', url);
                if (window.localStorage.getItem('reloadPhotography')) {
                    confirmReloadPageModal.show();
                    const reloadModal = document.getElementById('confirmReloadPageModal');
                    const reloadModalCloseBtn = document.getElementById('cls-btn-confirmReloadPageModal');
                    // Reload when clicking close button
                    reloadModalCloseBtn.addEventListener('click', () => {
                        reloadPage();
                    });
                    // Reload when clicking outside of the modal
                    document.addEventListener('click', (e) => {
                        if (e.target === reloadModal) {
                            reloadPage();
                        }
                    }, false);
                } else {
                    const tab = e.target.id;
                    // Hide download section when in configuration tab
                    const downloadSection = document.querySelector('#download-section');
                    if ('configure-tab' == tab || 'configure-new-tab' == tab) {
                        downloadSection.classList.add('hidden');
                    } else {
                        downloadSection.classList.remove('hidden');
                        if ('portraits-tab' == tab) {
                            window.updateDownloadsForPortraits(tab);
                            setCategory('PORTRAITS');
                        } else if ('groups-tab' == tab) {
                            window.updateDownloadsForGroups(tab);
                            setCategory('GROUPS');
                        } else if ('others-tab' == tab) {
                            window.updateDownloadsForOthers(tab);
                            setCategory('OTHERS');
                        }
                    }
                    // reset images selected
                    resetImages();
                }
            });
        });
    });
    
    async function showOptionsDownloadRequest() {
        if ($(".grid").attr('total-image-count') != 0) {

            const selectedImages = JSON.parse(localStorage.getItem('selectedImages'));

            const selectedImagesLength = selectedImages.length === 0 ? $(".grid").attr('total-image-count') : selectedImages.length;
            
            if (selectedImagesLength == 1) {
                $("#folder_format_selection").hide();
            } else {
                $("#folder_format_selection").show();
            }

            let response = await fetch('{{ route('photography.request-download-nonce') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: {}
            });
            
            let result = await response.json();
            $(`#nonce`).val(result.data.nonce);
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
            $('#confirm-download-body').html("{{ $configMessages['request']['confirm']  }} <b>" + selectedImagesLength + "</b> {{ $configMessages['request']['number_of']  }}?");
        } else {
            alert('No images found');
        }
    }

    function updateSchoolConfig() {
        $('#configure-tab').text('Configure*');
        $('#configure-new-tab').text('Configure-new*');
        window.localStorage.setItem('reloadPhotography', true);
    }

    function reloadPage() {
        window.location.reload();
    }

    function updateFormatOptions(options) {
        const formatSelect = document.getElementById('filename_format');
        // Clear existing options
        formatSelect.innerHTML = '';
        // Add new options
        Object.values(options).forEach(({name, format_key}) => {
            const newOption = document.createElement('option');
            newOption.value = format_key;
            newOption.textContent = name;
            formatSelect.appendChild(newOption);
        });
    }
    
    const confirmDownloadModal = new Modal(document.getElementById('confirmDownloadModal'));
    const successDownloadModal = new Modal(document.getElementById('successDownloadModal'));
    const showOptionsDownloadModal = new Modal(document.getElementById('showOptionsDownloadModal'));
    const confirmReloadPageModal = new Modal(document.getElementById('confirmReloadPageModal'))
    
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
        const tab = getActiveTabId();
        const tabVal = tab.replace("-tab", "");
        const selectedImages = JSON.parse(localStorage.getItem('selectedImages'));
        try {
            const downloadBtn = document.querySelector('#confirm-download-btn');
            const selectedYear = $(`#select_${tabVal}_year`).val();
            const selectedView = $(`#select_${tabVal}_view`).val();
            const selectedClass = $(`#select_${tabVal}_class`).val();
            const INDIVIDUAL_CATEGORY = 1;
            const BULK_CATEGORY = 2;
            
            console.log('Selected Year:', selectedYear);
            console.log('Selected View:', selectedView);
            console.log('Selected Class:', JSON.stringify(selectedClass));
            
            $(downloadBtn).html(`<x-spinner.button />`);
            
            let filters = {
                year: selectedYear,
                view: selectedView,
                class: JSON.stringify(selectedClass),
                resolution: $('#image_res').val(),
                folder_format: $('#folder_format').val()
            };
            
            console.log('Download Filters:', filters);
            
            const response = await fetch('{{ route('photography.request-download') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'MSP-Nonce': $(`#nonce`).val()
                },
                body: JSON.stringify(
                    { 
                        images: selectedImages,
                        category: selectedImages.length === 1 ? INDIVIDUAL_CATEGORY : BULK_CATEGORY,
                        filters: filters,
                        filenameFormat: parseInt($('#filename_format').val()),
                        tab: localStorage.getItem('photographyCategory'),
                    }
                )
            });
            
            console.log(response);
            
            // check if response has error and throw error, get the error status
            if (await !response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            //console.log('Download request successful:', result);
            
            console.log(result);
            
            // only if selected image is one
            if (selectedImages.length === 1) {
                const imgElement = document.createElement('a');
                imgElement.href = `data:image/jpeg;base64,${result['data']}`;
                imgElement.download = `${decryptData(result['filename'])}.jpg`;
                imgElement.click();
            }
            
            $(downloadBtn).html("Download");
            resetImages();
            document.querySelector('[data-modal-hide="confirmDownloadModal"]').click();

            if (selectedImages.length !== 1) {
                successDownloadModal.show();
            }
        } catch (error) {
            console.error('Error submitting download request:', error);
        }
    }
    
    window.showOptionsDownloadRequest = showOptionsDownloadRequest;
    window.showConfirmDownloadRequest = showConfirmDownloadRequest;
    window.submitDownloadRequest = submitDownloadRequest;
    window.confirmDownloadRequest = confirmDownloadRequest;
    window.reloadPage = reloadPage;
    window.updateSchoolConfig = updateSchoolConfig;

    Livewire.on('EV_UPDATE_FILENAME_FORMATS', (data) => {
        updateFormatOptions(data[0]);
    });
</script>
@endpush
