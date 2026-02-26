@extends('layouts.authenticated')

@php
    use App\Helpers\SchoolContextHelper;
    use App\Helpers\RoleHelper;
    
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
    
    $school = SchoolContextHelper::getSchool();
    $schoolKey = $school->schoolkey ?? '';

    $canDownloadPortraits = true;
    $canDownloadGroups = true;
    $canDownloadOthers = true;
    
    if ($school && $school->digital_download_permission_notification) {
        $permissions = json_decode($school->digital_download_permission_notification, true);
        if (isset($permissions['digital_download_permission'])) {
            $userRoleName = Auth::user()->roles->first()->name ?? '';
            $roleValueMap = [
                RoleHelper::ROLE_PHOTO_COORDINATOR => 'photocoordinator',
                RoleHelper::ROLE_SCHOOL_ADMIN => 'schooladmin',
                RoleHelper::ROLE_TEACHER => 'teacher',
            ];
            
            if (isset($roleValueMap[$userRoleName])) {
                $mappedRole = $roleValueMap[$userRoleName];
                $canDownloadPortraits = isset($permissions['digital_download_permission']['download_portrait'][$mappedRole]) && $permissions['digital_download_permission']['download_portrait'][$mappedRole] === true;
                $canDownloadGroups = isset($permissions['digital_download_permission']['download_group'][$mappedRole]) && $permissions['digital_download_permission']['download_group'][$mappedRole] === true;
                $canDownloadOthers = isset($permissions['digital_download_permission']['download_schoolPhoto'][$mappedRole]) && $permissions['digital_download_permission']['download_schoolPhoto'][$mappedRole] === true;
            }
        }
    }
@endphp

@section('content')
    <div x-data id="photography-root" class="container3 p-4">
        <x-tabs.tabContainer tabsWrapper="photography-pages">
            @role($RoleHelper::ROLE_FRANCHISE)
                @if ($configureTabV1Value)
                    <x-tabs.tab id="configure-new" isActive="{{$currentTab == 'configure'}}" route="{{route('photography.configure-new')}}">Configure</x-tabs.tab>
                @endif
            @endrole
            <x-tabs.tab id="portraits" isActive="{{$currentTab == 'portraits'}}" route="{{route('photography.portraits')}}">Portraits</x-tabs.tab>
            @if ($groupsTabValue)    
                <x-tabs.tab id="groups" isActive="{{$currentTab == 'groups'}}" route="{{route('photography.groups')}}">Groups</x-tabs.tab>
            @endif
            @if ($otherTabValue)
                <x-tabs.tab id="others" isActive="{{$currentTab == 'others'}}" route="{{route('photography.others')}}">Others</x-tabs.tab>
            @endif

            @php
                $hideDownloadBtn = false;
                if ($currentTab == 'configure' || $currentTab == 'configure-new') {
                    $hideDownloadBtn = true;
                } else if ($currentTab == 'portraits' && !$canDownloadPortraits) {
                    $hideDownloadBtn = true;
                } else if ($currentTab == 'groups' && !$canDownloadGroups) {
                    $hideDownloadBtn = true;
                } else if ($currentTab == 'others' && !$canDownloadOthers) {
                    $hideDownloadBtn = true;
                }
            @endphp

            <div id="download-section" class="absolute right-2 h-full flex align-middle justify-center items-center gap-4 {{$hideDownloadBtn ? 'hidden' : ''}}">
                <x-button.secondary id="btn-select-mode" onclick="toggleSelectMode()">Select</x-button.primary-inverse>
                <x-button.primary-inverse id="btn-download-clear" hollow class="border-none hidden transition-none hover:transition-none" onclick="resetImages()">Cancel Selection</x-button.primary-inverse>
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
                <x-tabs.tabContent id="configure-new">
                    @include('partials.photography.configure-new')
                </x-tabs.tabContent>
            @endrole
            <x-tabs.tabContent id="portraits">
                @if ($canDownloadPortraits)
                    @include('partials.photography.portraits')
                @else
                    <div class="flex items-center justify-center h-64 bg-white mt-4">
                        You do not have permission to view or download Portraits.
                    </div>
                @endif
            </x-tabs.tabContent>
            <x-tabs.tabContent id="groups">
                @if ($canDownloadGroups)
                    @include('partials.photography.groups')
                @else
                    <div class="flex items-center justify-center h-64 bg-white mt-4">
                        You do not have permission to view or download Groups.
                    </div>
                @endif
            </x-tabs.tabContent>
            <x-tabs.tabContent id="others">
                @if ($canDownloadOthers)
                    @include('partials.photography.others')
                @else
                    <div class="flex items-center justify-center h-64 bg-white mt-4">
                        You do not have permission to view or download Others.
                    </div>
                @endif
            </x-tabs.tabContent>
        </x-tabs.tabContentContainer>

        <x-modal.base id="showOptionsDownloadModal" title="{{ $configMessages['options']['title']  }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <input type="hidden" id="nonce" name="nonce" value="">
                    <div><b>{{ $configMessages['options']['sub_title']  }}</b></div>
                    <div>
                        <p>{{ $configMessages['options']['resolution_selection']  }}</p>
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <select id="image_res" class="input">
                                    @foreach($imageOptions as $option) 
                                        <option value="{{ $option->id }}" >
                                            {{ $option->display_name }}
                                        </option>
                                        
                                    @endforeach
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
                    <p id="confirm-download-notes" class="text-neutral-600"></p>
                    <p id="confirm-download-tnc" class="text-neutral-600">
                        View MSP Photography's <a href="https://www.msp.com.au/msp-terms-conditions/" target="_blank" class="text-blue-600 underline">Terms of Use & Copyright Conditions</a>
                    </p>
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
        <x-modal.base id="downloadUnavailableModal" title="{{ $configMessages['unavailable']['title'] }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="download-unavailable-body">{{ $configMessages['unavailable']['message'] }}</p>
                    <p id="download-unavailable-body-2">{{ $configMessages['unavailable']['message2'] }}</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.secondary data-modal-hide="downloadUnavailableModal">Dismiss</x-button.secondary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        <x-modal.base id="replaceOrRemovePhotoModal" title="{{ $photographyMessages['image_upload']['replace_or_remove_title'] }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="replace-or-remove-photo-body">{{ $photographyMessages['image_upload']['replace_or_remove_body'] }}</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.primary onclick="showRemovePhotoModal()" id="remove-photo-btn" data-modal-hide="replaceOrRemovePhotoModal">Remove Photo</x-button.primary>
                    <x-button.primary onclick="replaceUploadedPhoto()" id="replace-photo-btn">Replace Photo</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        <x-modal.base id="confirmRemovePhotoModal" title="{{ $photographyMessages['image_upload']['confirm_remove_title'] }}" body="components.modal.body" footer="components.modal.footer">
            <x-slot name="body">
                <x-modal.body>
                    <p id="confirm-remove-body">{{ $photographyMessages['image_upload']['confirm_remove_body'] }} <span id="confirm-remove-body-name"></span>?</p>
                </x-modal.body>
            </x-slot>
            <x-slot name="footer">
                <x-modal.footer>
                    <x-button.secondary data-modal-hide="confirmRemovePhotoModal">Cancel</x-button.secondary>
                    <x-button.primary onclick="removeUploadedPhoto()">Yes</x-button.primary>
                </x-modal.footer>
            </x-slot>
        </x-modal.base>
        <input class="hidden" type="file" id="imgUploadInput" accept="image/*" />
        @include('partials.photography.modals.lightbox-modal', ['schoolKey' => $schoolKey])
    </div>
@endsection

@push('scripts')
<script type="module">
    // TODO: Implement cloudflare-friendly encryption for photography download request
    {{-- import { decryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}" --}}
    function updateImageState(imgCheckbox, isSelected, isLightbox = false) {
        if (!imgCheckbox) {
            return;
        }
        let checkIcon = imgCheckbox.querySelector('i');
        const isSelectMode = window.localStorage.getItem('selectMode').toLowerCase() === 'true';
        if (isLightbox || isSelectMode) {
            imgCheckbox.parentElement.classList.remove('hidden');
        } else {
            imgCheckbox.parentElement.classList.add('hidden');
        }
        if (isSelected) {
            imgCheckbox.classList.add('bg-white');
            checkIcon.classList.remove('hidden');
        } else {
            imgCheckbox.classList.remove('bg-white');
            checkIcon.classList.add('hidden');
        }
    }

    function updateDownloadSection(selectedCount, isLightbox) {
        const downloadBtn = document.querySelector(isLightbox ? '#btn-lb-download' : '#btn-download');
        const clearDownloadBtn = document.querySelector(isLightbox ? '#btn-lb-download-clear' : '#btn-download-clear');
        //CODE BY IT
        if (!clearDownloadBtn || !downloadBtn) {
            return;
        }
        //CODE BY IT

        if (selectedCount > 0) {
            clearDownloadBtn.classList.remove('hidden');
            downloadBtn.innerHTML = `Download Selected (${selectedCount})`;
        } else {
            const selectMode = window.localStorage.getItem('selectMode').toLowerCase() === 'true';
            if (!selectMode) {
                clearDownloadBtn.classList.add('hidden');
            }
            downloadBtn.innerHTML = 'Download All';
        }
    }
    
    function updateDownloadSelection(isLightbox = false) {
        const images = JSON.parse(localStorage.getItem(isLightbox ? 'selectedLightboxImages' : 'selectedImages'));
        const portraits = document.querySelectorAll(isLightbox ? '.modal-content .portrait-img' : '.portrait-img');

        portraits.forEach(img => {
            let checkbox = img.querySelector('.portrait-img-checkbox');
            updateImageState(checkbox, images.includes(img.id), isLightbox);
        });

        updateDownloadSection(images.length, isLightbox);
    }

    function resetImages(isLightbox = false) {
        if (isLightbox) {
            window.localStorage.setItem('selectedLightboxImages', JSON.stringify([]));
        } else {
            const images = window.localStorage.getItem('selectedImages');
            const imagesCount = images ? JSON.parse(images).length : 0;
            window.localStorage.setItem('selectedImages', JSON.stringify([]));
            toggleSelectMode(false);
        }
        updateDownloadSelection(isLightbox);
    }

    function setCategory(category) {
        window.localStorage.setItem('photographyCategory', category);
        Livewire.dispatch('EV_CHANGE_TAB', { category: category });
    }

    function showRemovePhotoModal(event, imageKey = '', name = '') {
        if (event) {
            event.stopPropagation();
        }
        document.querySelector('#confirm-remove-body-name').textContent = name;
        const modal = document.getElementById('confirmRemovePhotoModal');
        modal.setAttribute('data-image-key', imageKey);
        confirmRemovePhotoModal.show();
    }

    function replaceUploadedPhoto(event, imageKey = '') {
        if (event) {
            event.stopPropagation();
        }

        document.getElementById('imgUploadInput').setAttribute('data-image-key', imageKey);
        document.getElementById('imgUploadInput').click();
    }

    function removeUploadedPhoto() {
        const modal = document.getElementById('confirmRemovePhotoModal');
        const imageKey = modal.getAttribute('data-image-key');

        // Show spinner component
        let imageFrame = document.getElementById(imageKey);
        Alpine.$data(imageFrame).showSpinner = true;

        fetch('{{ route('photography.remove-image') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ image_key: imageKey.split('_')[1] })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Livewire.dispatch('EV_IMAGE_DELETED', { key: data.key });
                confirmRemovePhotoModal.hide();
            } else {
                console.error('Remove failed:', data.message);
                Alpine.$data(imageFrame).showSpinner = false;
            }
        })
        .catch(error => {
            console.error('Remove failed:', error);
            Alpine.$data(imageFrame).showSpinner = false;
        });
    }

    const debouncedSetCategory = debounce((data) => {
        setCategory(data)
    }, 300);

    function handleImageClick(imageId, isLightbox = false, hasImage = false) {
        const selectMode = window.localStorage.getItem('selectMode').toLowerCase() === 'true';
        const img = document.querySelector(`#${imageId}`);
        const name = img.querySelector('.img-decoded-name').textContent;
        const imageItems = isLightbox ? 'selectedLightboxImages' : 'selectedImages';
        const isUploaded = img.classList.contains('uploaded');
        const externalSubjectId = img.dataset.externalSubjectId;
        
        if (!isLightbox) {
            if (selectMode) {
                if (!hasImage) {
                    return;
                }
            } else {
                showLightboxModal(imageId, name, externalSubjectId);
                return;
            }
        } else if (!hasImage) {
            if (!selectMode) {
                document.getElementById('imgUploadInput').setAttribute('data-image-key', imageId);
                document.getElementById('imgUploadInput').click();
                return;
            }
            return;
        }
        let selectedItems = window.localStorage.getItem(imageItems);
        selectedItems = selectedItems ? JSON.parse(selectedItems) : [];

        const isAlreadySelected = selectedItems.includes(imageId);

        if (isAlreadySelected) {
            selectedItems = selectedItems.filter(item => item !== imageId);
        } else {
            selectedItems.push(imageId);
        }

        window.localStorage.setItem(imageItems, JSON.stringify(selectedItems));

        updateImageState(img.querySelector('.portrait-img-checkbox'), !isAlreadySelected, isLightbox);
        updateDownloadSection(selectedItems.length, isLightbox);
    }

    function getActiveTabId() {
        const activeTab = document.querySelector('.tab-button[aria-selected="true"]');
        return activeTab ? activeTab.id : null;
    }
    
    window.resetImages = resetImages;
    window.handleImageClick = handleImageClick;
    window.toggleSelectMode = toggleSelectMode;
    window.updateImageCheckboxes = updateImageCheckboxes;
    window.updateDownloadSelection = updateDownloadSelection;
    resetImages();
    
    document.addEventListener('DOMContentLoaded', () => {
        window.localStorage.setItem('selectedLightboxImages', JSON.stringify([]));
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
                    
                    const tabPermissions = {
                        'portraits-tab': {{ $canDownloadPortraits ? 'true' : 'false' }},
                        'groups-tab': {{ $canDownloadGroups ? 'true' : 'false' }},
                        'others-tab': {{ $canDownloadOthers ? 'true' : 'false' }}
                    };

                    if ('configure-tab' == tab || 'configure-new-tab' == tab || !tabPermissions[tab]) {
                        downloadSection.classList.add('hidden');
                    } else {
                        downloadSection.classList.remove('hidden');
                    }

                    if ('configure-tab' != tab && 'configure-new-tab' != tab) {
                        if ('portraits-tab' == tab) {
                            if (typeof window.updateDownloadsForPortraits === 'function') window.updateDownloadsForPortraits(tab);
                            setCategory('PORTRAITS');
                        } else if ('groups-tab' == tab) {
                            if (typeof window.updateDownloadsForGroups === 'function') window.updateDownloadsForGroups(tab);
                            setCategory('GROUPS');
                        } else if ('others-tab' == tab) {
                            if (typeof window.updateDownloadsForOthers === 'function') window.updateDownloadsForOthers(tab);
                            setCategory('OTHERS');
                        }
                    }
                    // reset images selected
                    resetImages();
                }
            });
        });

        // Handle image upload from input
        document.getElementById('imgUploadInput').addEventListener('change', async function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const originalKey = event.currentTarget.dataset.imageKey;
            const imageKey = originalKey.split('_')[1];

            const formData = new FormData();
            formData.append('image', file);
            formData.append('image_key', imageKey);

            try {
                // Show spinner component
                let imageFrame = document.getElementById(originalKey);
                Alpine.$data(imageFrame).showSpinner = true;
                
                const response = await fetch('{{ route('photography.upload-image') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    if (result.uploadId) {
                        // Update livewire component
                        Livewire.dispatch('EV_IMAGE_UPLOADED', { key: result.key });
                        alert("{{ $photographyMessages['image_upload']['success'] }}");
                    } else {
                        Alpine.$data(imageFrame).showSpinner = false;
                    }
                } else {
                    console.error('Upload failed:', {result});
                    Alpine.$data(imageFrame).showSpinner = false;
                    if (result.message) {
                        alert(`Image upload failed: ${result.message}`);
                    } else if (result.errors && result.errors.image && result.errors.image.length > 0) {
                        alert(`Image upload failed: ${result.errors.image[0]}`);
                    } else {
                        alert("{{ $photographyMessages['image_upload']['fail'] }}");
                    }
                }
                event.target.value = '';
            } catch (error) {
                event.target.value = '';
                console.error('Error uploading image:', error);
                Alpine.$data(imageFrame).showSpinner = false;
                alert("{{ $photographyMessages['image_upload']['fail'] }}");
                return;
            }
        });
    });
    
    async function showOptionsDownloadRequest() {
        const lightboxModal = document.getElementById('lightbox-modal');
        const isLightbox = !lightboxModal.classList.contains('hidden');
        const category = localStorage.getItem('photographyCategory');
        const imgsOrigin = isLightbox ? $("#lightbox-modal").find(".grid") : $(`#${category.toLowerCase()} .grid`);
        if (imgsOrigin.attr('total-image-count') != 0) {
            const storateItem = isLightbox ? `hasImages_LIGHTBOX` : `hasImages_${category}`;
            if (0 == localStorage.getItem(storateItem)) {
                downloadUnavailableModal.show();
                return;
            }

            const selectedImages = JSON.parse(localStorage.getItem(isLightbox ? 'selectedLightboxImages' : 'selectedImages'));
            const selectedImagesLength = selectedImages.length === 0 ? imgsOrigin.attr('total-image-count') : selectedImages.length;
            
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

    function toggleSelectMode(enable = true) {
        window.localStorage.setItem('selectMode', enable);
        updateImageCheckboxes(enable);
    }

    function updateImageCheckboxes(enable) {
        const clearDownloadBtn = document.querySelector('#btn-download-clear');
        const selectModeBtn = document.querySelector('#btn-select-mode');
        let imgCheckboxes = document.querySelectorAll('.img-checkbox');
        if (enable) {
            selectModeBtn.classList.add('hidden');
            clearDownloadBtn.classList.remove('hidden');
            imgCheckboxes.forEach(checkbox => {
                checkbox.classList.remove('hidden');
            });
        } else {
            imgCheckboxes.forEach(checkbox => {
                checkbox.classList.add('hidden');
            });
            clearDownloadBtn.classList.add('hidden');
            selectModeBtn.classList.remove('hidden');
        }
    }
    
    function showConfirmDownloadRequest(isLightbox = false) {
        showOptionsDownloadModal.hide();
        const lightboxModal = document.getElementById('lightbox-modal');
        confirmDownloadRequest(!lightboxModal.classList.contains('hidden'));
    }
    
    function confirmDownloadRequest(isLightbox = false) {
        const selectedImages = JSON.parse(localStorage.getItem(isLightbox ? 'selectedLightboxImages' : 'selectedImages'));
        const category = localStorage.getItem('photographyCategory');
        const imgsOrigin = isLightbox ? $("#lightbox-modal").find(".grid") : $(`#${category.toLowerCase()} .grid`);
        if (imgsOrigin.attr('total-image-count') != 0) {
            confirmDownloadModal.show();
            const selectedImagesLength = selectedImages.length === 0 ? imgsOrigin.attr('total-image-count') : selectedImages.length;
            $('#confirm-download-body').html("{{ $configMessages['request']['confirm']  }} <b>" + selectedImagesLength + "</b> {{ $configMessages['request']['number_of']  }}?");

            const note = "{{ isset($configMessages['request']['note']) ? $configMessages['request']['note'] : '' }}";
            if (note) {
                $('#confirm-download-notes').html("<b>Please note:</b><br>" + note);
            } else {
                $('#confirm-download-notes').hide();
            }
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

    function toggleDisableForImgButtons(isLightboxShowing) {
        $('#btn-download').attr('disabled', isLightboxShowing);
        $('#btn-download-clear').attr('disabled', isLightboxShowing);
        $('#btn-select-mode').attr('disabled', isLightboxShowing);
    }
    
    const confirmDownloadModal = new Modal(document.getElementById('confirmDownloadModal'));
    const successDownloadModal = new Modal(document.getElementById('successDownloadModal'));
    const showOptionsDownloadModal = new Modal(document.getElementById('showOptionsDownloadModal'));
    const confirmReloadPageModal = new Modal(document.getElementById('confirmReloadPageModal'))
    const downloadUnavailableModal = new Modal(document.getElementById('downloadUnavailableModal'));
    const replaceOrRemovePhotoModal = new Modal(document.getElementById('replaceOrRemovePhotoModal'));
    const confirmRemovePhotoModal = new Modal(document.getElementById('confirmRemovePhotoModal'));
    const lightboxOptions = {
        onHide: () => {
            resetImages(true);
            toggleDisableForImgButtons(false);
        },
        onShow: () => {
            toggleDisableForImgButtons(true);
        },
    };
    let lightboxModal = new Modal(document.getElementById('lightbox-modal'), lightboxOptions);
    
    window.addEventListener('image-frame-updated', event => {
        const isLightbox = event.detail[0]['isLightbox'];
        const imageId = isLightbox ? `img-lb_${event.detail[0]['imageId']}` : `img_${event.detail[0]['imageId']}`;
        const img = document.querySelector(`#${imageId}`);
        Alpine.$data(img).showSpinner = false;
        setTimeout(() => {
            const images = JSON.parse(localStorage.getItem(isLightbox ? 'selectedLightboxImages' : 'selectedImages'));
            const selectMode = window.localStorage.getItem('selectMode').toLowerCase() === 'true';
            let checkbox = img.querySelector('.portrait-img-checkbox');
            updateImageState(checkbox, images.includes(imageId), isLightbox);
        }, 50);
    });
    
    async function submitDownloadRequest() {
        const lightboxModal = document.getElementById('lightbox-modal');
        const isLightbox = !lightboxModal.classList.contains('hidden');
        const tab = getActiveTabId();
        const tabVal = tab.replace("-tab", "");
        let selectedImages = JSON.parse(localStorage.getItem(isLightbox ? 'selectedLightboxImages' : 'selectedImages'));
        try {
            const downloadBtn = document.querySelector('#confirm-download-btn');
            const selectedYear = $(`#select_${tabVal}_year`).val();
            const selectedView = $(`#select_${tabVal}_view`).val();
            const selectedClass = $(`#select_${tabVal}_class`).val();
            const INDIVIDUAL_CATEGORY = 1;
            const BULK_CATEGORY = 2;
            const BASE_ORIGIN = 1;
            const LIGHTBOX_ORIGIN = 2;
            
            $(downloadBtn).html(`<x-spinner.button />`);
            
            let filters = {
                year: selectedYear,
                view: selectedView,
                class: JSON.stringify(selectedClass),
                resolution: $('#image_res').val(),
                folder_format: $('#folder_format').val()
            };

            if (isLightbox) {
                selectedImages = selectedImages.map(img => img.replace('img-lb_', 'img_'));
            }
            
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
            
            // check if response has error and throw error, get the error status
            if (await !response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            
            // only if selected image is one
            if (selectedImages.length === 1) {
                const imgElement = document.createElement('a');
                imgElement.href = `data:image/${result['extension']};base64,${result['data']}`;
                imgElement.download = `${result['filename']}.${result['extension']}`;
                // imgElement.download = `${decryptData(result['filename'])}.jpg`;
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
    window.replaceUploadedPhoto = replaceUploadedPhoto;
    window.removeUploadedPhoto = removeUploadedPhoto;
    window.showRemovePhotoModal = showRemovePhotoModal;
    window.showLightboxModal = function(subjectKey, name, externalSubjectId) {
        window.localStorage.setItem('selectedLightboxImages', JSON.stringify([]));
        Livewire.dispatch('EV_SELECT_IMAGE', { 
            subject: name, 
            category: localStorage.getItem('photographyCategory'),
            externalSubjectId: externalSubjectId,
            subjectKey: subjectKey
        });
        document.querySelector('#lightbox-modal').querySelector('#modal-title').textContent = name;
        updateDownloadSection(0, true);
        lightboxModal.show();
        toggleDisableForImgButtons(true);
    }

    Livewire.on('EV_UPDATE_FILENAME_FORMATS', (data) => {
        updateFormatOptions(data[0]);
    });

    Livewire.on('EV_TOGGLE_NO_IMAGES', (data) => {
        localStorage.setItem(`hasImages_${data[0]['category']}`, data[0]['hasImages'] ? 1 : 0);
    });
</script>
@endpush
