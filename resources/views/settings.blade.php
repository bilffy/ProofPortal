@extends('layouts.authenticated')

@section('content')
    <div id="settings-root" class="px-4">
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">App Settings</h3>
            <div></div>
        </div>
        <div id="file-format-section" class="relative mb-8 gap-4">
            @livewire('settings.filename-format')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.feature-control')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.role-permission')
        </div>
        <div class="relative mb-8 gap-4">
            @livewire('settings.proofing-season-enable')
        </div>
        <div class="relative mb-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h5 class="text-xl font-bold dark:text-white mb-2">Sync Timestone Data</h5>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Manually sync Seasons, Franchises, and Schools from the Timestone.</p>
            
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
                <button id="btn-sync-schools" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-xs font-semibold uppercase tracking-wider rounded shadow hover:bg-opacity-95 active:bg-opacity-90 disabled:opacity-50 transition duration-150 ease-in-out">
                    <svg class="spinner-icon hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sync Schools
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    document.addEventListener('DOMContentLoaded', () => {
        const syncSeasonsBtn = document.getElementById('btn-sync-seasons');
        const syncFranchisesBtn = document.getElementById('btn-sync-franchises');
        const syncSchoolsBtn = document.getElementById('btn-sync-schools');

        async function handleSync(button, url) {
            const spinner = button.querySelector('.spinner-icon');
            
            // Disable all sync buttons during operation
            [syncSeasonsBtn, syncFranchisesBtn, syncSchoolsBtn].forEach(btn => {
                if (btn) btn.setAttribute('disabled', 'disabled');
            });
            if (spinner) spinner.classList.remove('hidden');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

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
                [syncSeasonsBtn, syncFranchisesBtn, syncSchoolsBtn].forEach(btn => {
                    if (btn) btn.removeAttribute('disabled');
                });
            }
        }

        if (syncSeasonsBtn) {
            syncSeasonsBtn.addEventListener('click', () => {
                handleSync(syncSeasonsBtn, '{{ route('settings.sync.seasons') }}');
            });
        }

        if (syncFranchisesBtn) {
            syncFranchisesBtn.addEventListener('click', () => {
                handleSync(syncFranchisesBtn, '{{ route('settings.sync.franchises') }}');
            });
        }

        if (syncSchoolsBtn) {
            syncSchoolsBtn.addEventListener('click', () => {
                handleSync(syncSchoolsBtn, '{{ route('settings.sync.schools') }}');
            });
        }
    });
</script>
@endpush