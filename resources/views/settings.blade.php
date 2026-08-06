@extends('layouts.authenticated')

@section('content')
    <div id="settings-root" class="px-4">
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">App Settings</h3>
            <div></div>
        </div>
        <div class="relative mb-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h5 class="text-xl font-bold dark:text-white mb-2">Sync Timestone Data</h5>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Sync missing Timestone seasons and franchises to the portal.</p>
            
            <div class="flex flex-row flex-wrap gap-4">
                <button id="btn-sync-seasons" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-xs font-semibold uppercase tracking-wider rounded shadow hover:bg-opacity-95 active:bg-opacity-90 disabled:opacity-50 transition duration-150 ease-in-out">
                    <svg class="spinner-icon hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sync Seasons
                </button>
                <button id="btn-sync-franchises" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-xs font-semibold uppercase tracking-wider rounded shadow hover:bg-opacity-95 active:bg-opacity-90 disabled:opacity-50 transition duration-150 ease-in-out">
                    <svg class="spinner-icon hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sync Franchises
                </button>
                <!-- <button id="btn-sync-schools" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-xs font-semibold uppercase tracking-wider rounded shadow hover:bg-opacity-95 active:bg-opacity-90 disabled:opacity-50 transition duration-150 ease-in-out">
                    <svg class="spinner-icon hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sync Schools
                </button> -->
            </div>
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.role-permission')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.proofing-season-enable')
        </div>

        
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">Photography Settings</h3>
            <div></div>
        </div>
        <div id="file-format-section" class="relative mb-8 gap-4">
            @livewire('settings.filename-format')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.feature-control')
        </div>


        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">Proofing Settings</h3>
            <div></div>
        </div>
        <div class="relative mb-8 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="settings-split">
                <div class="settings-split__panel">
                    @livewire('settings.revoke-users-jobs-season')
                </div>
                <div class="settings-split__panel settings-split__panel--right">
                    @livewire('settings.archive-jobs-season')
                </div>
            </div>
        </div>
    </div>
    <style>
        #settings-root .settings-split {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        #settings-root .settings-split__panel--right {
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        @media (min-width: 976px) {
            #settings-root .settings-split {
                flex-direction: row;
                align-items: stretch;
                gap: 0;
            }

            #settings-root .settings-split__panel {
                flex: 1 1 0;
                min-width: 0;
                padding-right: 1.5rem;
            }

            #settings-root .settings-split__panel--right {
                padding-top: 0;
                padding-right: 0;
                padding-left: 1.5rem;
                border-top: none;
                border-left: 1px solid #e5e7eb;
            }
        }
    </style>
@endsection

@push('scripts')
{{-- Local Select2 (jQuery plugin). CDN module in <head> may load before jQuery. --}}
<script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js') }}"></script>
<script>
    function initSeasonSelect2(selectId, changeNamespace) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }

        var $ = jQuery;
        var $el = $('#' + selectId);
        if (!$el.length) {
            return;
        }

        var changeEvent = 'change.' + changeNamespace;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.off(changeEvent);
            $el.select2('destroy');
        }

        $el.select2({
            placeholder: 'Choose a season',
            allowClear: true,
            width: '16rem',
            dropdownParent: $(document.body),
        });

        $el.next('.select2-container').find('.select2-selection').addClass('border-neutral');

        // Select is inside wire:ignore — push value into Livewire on change
        $el.on(changeEvent, function () {
            var value = $(this).val() || '';
            var root = $el.closest('[wire\\:id]');
            var componentId = root.attr('wire:id');
            var component = componentId && window.Livewire ? window.Livewire.find(componentId) : null;
            if (component) {
                component.set('selectedTsSeasonId', value);
            }
        });
    }

    function resetSeasonSelect2(selectId) {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $el = jQuery('#' + selectId);
        if ($el.length) {
            $el.val(null).trigger('change');
        }
    }

    // Read select value, set Livewire prop, then call action (fixes "Please select a season")
    window.runSeasonAction = async function (selectId, methodName) {
        var confirms = {
            revokeUsersForSeason: 'This will remove all users assigned to every job in the selected season. Continue?',
            archiveJobsForSeason: 'This will archive every job in the selected season. Continue?',
        };

        if (confirms[methodName] && !window.confirm(confirms[methodName])) {
            return;
        }

        var $ = window.jQuery;
        var $el = $ ? $('#' + selectId) : null;
        var value = $el && $el.length ? ($el.val() || '') : (document.getElementById(selectId)?.value || '');
        var root = $el && $el.length
            ? $el.closest('[wire\\:id]')
            : document.getElementById(selectId)?.closest('[wire\\:id]');
        var componentId = root
            ? (typeof root.attr === 'function' ? root.attr('wire:id') : root.getAttribute('wire:id'))
            : null;
        var component = componentId && window.Livewire ? window.Livewire.find(componentId) : null;

        if (!component) {
            return;
        }

        await component.set('selectedTsSeasonId', value);
        await component.call(methodName);
    };

    document.addEventListener('DOMContentLoaded', function () {
        initSeasonSelect2('revoke-users-season', 'revokeUsersSeason');
        initSeasonSelect2('archive-jobs-season', 'archiveJobsSeason');

        var syncSeasonsBtn = document.getElementById('btn-sync-seasons');
        var syncFranchisesBtn = document.getElementById('btn-sync-franchises');
        var syncSchoolsBtn = document.getElementById('btn-sync-schools');

        async function handleSync(button, url) {
            var spinner = button.querySelector('.spinner-icon');

            [syncSeasonsBtn, syncFranchisesBtn, syncSchoolsBtn].forEach(function (btn) {
                if (btn) btn.setAttribute('disabled', 'disabled');
            });
            if (spinner) spinner.classList.remove('hidden');

            try {
                var response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                var result = await response.json();

                if (response.ok && result.success) {
                    window.dispatchEvent(new CustomEvent('show-toast-message', {
                        detail: { status: 'success', message: result.message }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast-message', {
                        detail: { status: 'error', message: result.message || 'sync failed.' }
                    }));
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast-message', {
                    detail: { status: 'error', message: error.message || 'An error occurred during sync.' }
                }));
            } finally {
                if (spinner) spinner.classList.add('hidden');
                [syncSeasonsBtn, syncFranchisesBtn, syncSchoolsBtn].forEach(function (btn) {
                    if (btn) btn.removeAttribute('disabled');
                });
            }
        }

        if (syncSeasonsBtn) {
            syncSeasonsBtn.addEventListener('click', function () {
                handleSync(syncSeasonsBtn, '{{ route('settings.sync.seasons') }}');
            });
        }

        if (syncFranchisesBtn) {
            syncFranchisesBtn.addEventListener('click', function () {
                handleSync(syncFranchisesBtn, '{{ route('settings.sync.franchises') }}');
            });
        }

        if (syncSchoolsBtn) {
            syncSchoolsBtn.addEventListener('click', function () {
                handleSync(syncSchoolsBtn, '{{ route('settings.sync.schools') }}');
            });
        }
    });

    document.addEventListener('livewire:initialized', function () {
        initSeasonSelect2('revoke-users-season', 'revokeUsersSeason');
        initSeasonSelect2('archive-jobs-season', 'archiveJobsSeason');

        window.Livewire.on('revoke-users-season-reset', function () {
            resetSeasonSelect2('revoke-users-season');
        });

        window.Livewire.on('archive-jobs-season-reset', function () {
            resetSeasonSelect2('archive-jobs-season');
        });
    });
</script>
@endpush
