@section('css')

    {{-- Flatpickr --}}
    
    <link href="{{ URL::asset('proofing-assets/vendors/css/flatpickr.min.css') }}" rel="stylesheet" />
    {{-- Bootstrap Multiselect --}}
    {{-- <link href="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/css/bootstrap-multiselect.css')}}" rel="stylesheet" /> --}}
    {{-- Select2 --}}
    <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />

    <style>
        .custom-file-label:hover {
            background-color: #0056b3; /* Darker shade on hover */
        }
        /* Ensure table respects cell content sizes */
        .table {
            width: 50%; /* Full table width */
        }
        .select2 {
            width:100% !important;
        }
        .job-dependent-section {
            display: none;
        }
        #jobs-needing-archive-banner {
            background: #fff5f0;
            border: 1px solid #fbd0b8;
            border-left: 6px solid #f97316;
            border-radius: 10px;
            overflow: hidden;
        }
        .jobs-archive-banner-inner {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            width: 100%;
        }
        .jobs-archive-left {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            flex: 1 1 auto;
            min-width: 0;
        }
        .jobs-archive-bell-wrap {
            position: relative;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }
        .jobs-archive-bell-circle {
            width: 48px;
            height: 48px;
            border-radius: 9999px;
            border: 1.5px solid #fdba74;
            background: #ffedd5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f97316;
        }
        .jobs-archive-bell-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 9999px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
            border: 2px solid #fff5f0;
        }
        .jobs-archive-heading {
            color: #ea580c;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .jobs-archive-count-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.5rem;
            border-radius: 9999px;
            background: #ffedd5;
            color: #c2410c;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .jobs-archive-title {
            color: #111827;
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .jobs-archive-description {
            color: #4b5563;
            font-size: 0.82rem;
            line-height: 1.4;
            margin-bottom: 0;
        }
        .jobs-archive-illustration {
            flex: 0 0 auto;
            width: 96px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .jobs-archive-banner-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: auto;
            max-width: none;
            flex: 0 0 auto;
            padding: 0.65rem 0.95rem;
            border: none;
            border-radius: 8px;
            background: #005890;
            color: #fff;
            font-size: 0.84rem;
            font-weight: 600;
            white-space: nowrap;
            transition: background 0.15s ease;
            cursor: pointer;
        }
        .jobs-archive-banner-btn:hover {
            background: #00436e;
        }
        .jobs-archive-banner-btn svg {
            flex-shrink: 0;
        }
        @media (max-width: 900px) {
            .jobs-archive-illustration {
                display: none;
            }
        }
        #jobsNeedingArchiveModal .archive-job-row:last-child {
            border-bottom: none;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-panel {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
        }
        #jobsNeedingArchiveModal > .relative {
            width: 50%;
            max-width: calc(100vw - 2rem);
            margin-left: auto;
            margin-right: auto;
        }
        @media (max-width: 900px) {
            #jobsNeedingArchiveModal > .relative {
                width: calc(100vw - 2rem);
            }
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-header {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.5rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-header-main {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            z-index: 1;
            min-width: 0;
            flex: 1 1 auto;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            background: #005890;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-title {
            margin: 0 0 0.35rem;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.25;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 0.84rem;
            line-height: 1.45;
            max-width: 36rem;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-header-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            margin-left: auto;
            padding-right: 2rem;
            z-index: 1;
            flex-shrink: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-close-top {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-close-top:hover {
            background: #f1f5f9;
            color: #475569;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-info {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1rem 1.5rem;
            padding: 0.85rem 1rem;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 0.82rem;
            line-height: 1.45;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-info-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            margin-top: 1px;
            color: #2563eb;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-body {
            padding: 0 1.5rem 1.25rem;
            max-height: 52vh;
            overflow-y: auto;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }
        @media (max-width: 1200px) {
            #jobsNeedingArchiveModal .jobs-archive-modal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 900px) {
            #jobsNeedingArchiveModal .jobs-archive-modal-grid {
                grid-template-columns: 1fr;
            }
            #jobsNeedingArchiveModal .jobs-archive-modal-header-actions {
                padding-right: 0;
                margin-top: 0.5rem;
                margin-left: 0;
                width: 100%;
                align-items: flex-start;
            }
            #jobsNeedingArchiveModal .jobs-archive-modal-header {
                flex-wrap: wrap;
            }
        }
        #jobsNeedingArchiveModal .jobs-archive-job-card {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.9rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            min-width: 0;
            min-height: 100%;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #e8f2fa;
            color: #005890;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-season {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.2;
            white-space: nowrap;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-season svg {
            flex-shrink: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-content {
            flex: 1 1 auto;
            min-width: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-name {
            margin: 0 0 0.35rem;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1.3;
            word-break: break-word;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-due,
        #jobsNeedingArchiveModal .jobs-archive-job-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin: 0;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.35;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-due svg,
        #jobsNeedingArchiveModal .jobs-archive-job-status svg {
            flex-shrink: 0;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-status {
            margin-top: 0.2rem;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-card-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: auto;
            padding-top: 0.15rem;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-btn,
        #jobsNeedingArchiveModal .jobs-archive-modal-footer-all {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border: 1px solid #005890;
            border-radius: 8px;
            background: #fff;
            color: #005890;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-btn {
            width: auto;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-btn:hover,
        #jobsNeedingArchiveModal .jobs-archive-modal-footer-all:hover {
            background: #e8f2fa;
        }
        #jobsNeedingArchiveModal .jobs-archive-job-btn:disabled,
        #jobsNeedingArchiveModal .jobs-archive-modal-footer-all:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-footer {
            display: flex;
            justify-content: flex-end;
            padding: 0.85rem 1.5rem 1.25rem;
            border-top: 1px solid #e5e7eb;
            background: #fff;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-close-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        #jobsNeedingArchiveModal .jobs-archive-modal-close-btn:hover {
            background: #f8fafc;
        }
        /* Keep photography configure scrollable inside the authenticated layout main panel */
        main:has(#photography-root) {
            overflow-y: auto !important;
            min-height: 0;
        }
    </style>
@stop

@php
    use Carbon\Carbon;
    use App\Helpers\ImageHelper;
    use App\Helpers\SchoolContextHelper;
    use App\Helpers\SchoolLogoHelper;
    use App\Services\Proofing\SchoolService;
    use App\Services\Proofing\SeasonService;
    use App\Services\Proofing\StatusService;
    use Illuminate\Support\Facades\Crypt;

    $defaultDate = Carbon::now();
    $schoolService = new SchoolService();
    $statusService = new StatusService();
    $jobService = app(\App\Services\Proofing\JobService::class);
    $seasonService = new SeasonService();

    $selectOptionsEmailTo = [
        'schooladmin' => 'School Administrator',
        'photocoordinator' => 'Photo Coordinator',
        'teacher' => 'Teacher',
    ];

    $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
    $selectedSchool = $schoolContext
        ? $schoolService->getSchoolById($schoolContext->id)->with('franchises')->first()
        : null;
    $filePath = '';
    if ($selectedSchool && $selectedSchool->school_logo) {
        $filePath = SchoolLogoHelper::relativePath($selectedSchool, $selectedSchool->school_logo);
    }
    // schoolHash carries schools.id (portal ownership); schoolkey alone is not unique
    $hash = $selectedSchool ? Crypt::encryptString((string) $selectedSchool->id) : '';
    $encryptedPath = $filePath !== '' ? Crypt::encryptString($filePath) : '';
    $seasons = $seasonService->getAllSeasonDataForPortal('code', 'show_in_proofing', 'is_default', 'ts_season_id')->orderby('code','desc')->get();
    $jobsGroupedBySeason = $selectedSchool
        ? $jobService->getPortalConfigureJobsGroupedBySeason($selectedSchool->id)
        : [];
    $jobsNeedingArchiving = $selectedSchool
        ? $jobService->getProofingJobsNeedingArchive($selectedSchool->id)
        : collect();
    $selectedFolders = [];

    $notificationsMatrix = $selectedSchool?->digital_download_permission_notification;
    $notificationsMatrix = $notificationsMatrix ? json_decode($notificationsMatrix, true) : [];
    $imageUrl = '';
    if ($selectedSchool && $selectedSchool->school_logo) {

     $imageUrl = $encryptedPath ? route('school.logo', ['encryptedPath' => $encryptedPath]) : '';
	}
    $schoollogo = $imageUrl;

    $seasonOptions['none'] = 'Choose a Season';
    $configureJobsBySeason = [];
    foreach ($seasons as $season) {
        $encryptedSeasonKey = Crypt::encryptString($season->ts_season_id);
        $seasonOptions[$encryptedSeasonKey] = $season->code;

        $seasonJobs = $jobsGroupedBySeason[$season->ts_season_id] ?? [];
        $configureJobsBySeason[$encryptedSeasonKey] = array_map(function ($job) {
            return [
                'ts_jobkey' => Crypt::encryptString($job['ts_jobkey']),
                'ts_jobname' => $job['ts_jobname'],
                'has_visible_portrait' => $job['has_visible_portrait'],
            ];
        }, $seasonJobs);
    }

    $groupsTab = $AppSettingsHelper::getByPropertyKey('groups_tab');
    $groupsTabValue = $groupsTab ? $groupsTab->property_value === 'true' ? true : false : true;
    
    $folderTypes['all'] = 'Show All';

    if ($groupsTabValue) {
        $folderTypes['portrait'] = 'Portrait / Group';
        $folderTypes['special_group'] = 'Speciality';
    }
    $imageTypes = ImageHelper::getExtensionsAsString('.');

    $schoolAddress = [];
    if (!empty($selectedSchool?->address)) {
        $schoolAddress[] = $selectedSchool->address;
    }
    if (!empty($selectedSchool?->suburb)) {
        $schoolAddress[] = $selectedSchool->suburb;
    }
    if (!empty($selectedSchool?->postcode)) {
        $schoolAddress[] = $selectedSchool->postcode;
    }
@endphp

@if (!$schoolContext || !$selectedSchool)
<div class="relative p-6 bg-white rounded-lg border border-gray-200">
    <h3 class="mb-2 text-black">School Settings</h3>
    <p class="text-sm text-gray-600">Select a school from the header to configure photography settings.</p>
</div>
@else
<div class="relative">
    <h3 class="mb-4 text-black">School Settings</h3>
    <div class="flex w-full mb-16 flex-col">
        <input type='hidden' value='{{$hash}}' name='schoolHash' id="schoolHash">
        <div class="bg-neutral-200 w-full p-4 rounded flex gap-4">
            <div id="schoolLogo_container" class="relative bg-white w-[341px] h-[246px] mb-4 p-4 items-center flex justify-center">
                <img id="schoolLogoPreview"
                    src="{{ $imageUrl }}"
                    alt="School Logo Preview"
                    class="object-contain w-full h-full"
                    style="{{ !isset($selectedSchool->school_logo) ? 'display: none;' : '' }}"
                />

                <input type="file"
                    class="form-control-file schoolLogo d-none"
                    id="schoolLogo"
                    name="schoolLogo" accept="{{ $imageTypes }}"> 
                <button 
                    id="schoolLogoBtn"
                    class="p-2 rounded-s absolute bottom-[16px]
                        right-[16px] bg-white bg-opacity-75 transition-all
                        hover:bg-primary-100 hover:transition-all"
                >
                    Change Logo
                </button>
            </div>
            <div>
                <p class="mb-2"><strong>{{ $selectedSchool->name }}</strong></p>
                <p class="mb-0">{{ implode(', ', $schoolAddress) }}</p>
            </div>
        </div>
        <form id="digital_download_form" class="pt-8">
        @if($jobsNeedingArchiving->isNotEmpty())
        <div
            id="jobs-needing-archive-banner"
            class="w-50 mb-4 p-4"
            role="alert"
            aria-live="polite"
        >
            <div class="jobs-archive-banner-inner">
                <div class="jobs-archive-left">
                    <div class="jobs-archive-bell-wrap" aria-hidden="true">
                        <div class="jobs-archive-bell-circle">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 4.5C9.51472 4.5 7.5 6.51472 7.5 9V11.25C7.5 11.6642 7.33579 12.0623 7.04289 12.3552L6.21967 13.1784C5.65893 13.7392 6.05279 14.75 6.85355 14.75H17.1464C17.9472 14.75 18.3411 13.7392 17.7803 13.1784L16.9571 12.3552C16.6642 12.0623 16.5 11.6642 16.5 11.25V9C16.5 6.51472 14.4853 4.5 12 4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 16.25C10.2761 17.2175 11.0537 17.95 12 17.95C12.9463 17.95 13.7239 17.2175 14 16.25" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span id="jobs-needing-archive-badge-count" class="jobs-archive-bell-badge">{{ $jobsNeedingArchiving->count() }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="jobs-archive-heading">Action Required</span>
                            <!-- <span id="jobs-needing-archive-count-label" class="jobs-archive-count-pill">
                                <span id="jobs-needing-archive-count">{{ $jobsNeedingArchiving->count() }}</span>{{ $jobsNeedingArchiving->count() === 1 ? ' job' : ' jobs' }}
                            </span> -->
                        </div>
                        <p class="jobs-archive-title">Proofing Jobs Ready for Archive</p>
                        <p class="jobs-archive-description">
                            These jobs have finished proofing but are not archived yet.
                            Archive them to configure and view digital images in Photography.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    id="open-jobs-needing-archive-modal"
                    class="jobs-archive-banner-btn"
                >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M2.5 4.5H13.5L12 13.5H4L2.5 4.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        <path d="M6 6.5V11.5M10 6.5V11.5M5 4.5L5.5 2.5H10.5L11 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>View &amp; Archive Jobs</span>
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M5 3L9 7L5 11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif
        <div class="w-full p-4">
            <div>
                <p class="mb-2"><strong>Digital Images Permissions</strong></p>
                <p>Select which User Roles are permitted to View & Download the Digital Images via the Portal</p>
            </div>
            <div>
                <div class="w-full border rounded lg:w-full xl:w-1/2">
                    <table class=" w-full">
                        <thead>
                            <x-table.headerCell sortable="{{false}}"> </x-table.headerCell>
                            <x-table.headerCell sortable="{{false}}">School Administrator</x-table.headerCell>
                            <x-table.headerCell sortable="{{false}}">Photo Coordinator</x-table.headerCell>
                            <x-table.headerCell sortable="{{false}}">Teacher</x-table.headerCell>
                        </thead>
                        <tbody>
                            <tr>
                                <x-table.cell>Portrait</x-table.cell>
                                @foreach($selectOptionsEmailTo as $key => $value)
                                    <x-table.cell data-model="digital_download_permission" data-field="download_portrait">
                                        <input
                                            type="checkbox"
                                            class="img-permission"
                                            name="permissions[{{$key}}]"
                                            value="{{ $key }}"
                                            @if(isset($notificationsMatrix['digital_download_permission']['download_portrait'][$key]) 
                                                && $notificationsMatrix['digital_download_permission']['download_portrait'][$key] == true)
                                                checked
                                            @endif
                                            >
                                    </x-table.cell>
                                @endforeach
                            </tr>
                            {{-- <tr>
                                <x-table.cell>Group Photo</x-table.cell>
                                @foreach($selectOptionsEmailTo as $key => $value)
                                    <x-table.cell data-model="digital_download_permission" data-field="download_group">
                                        <input
                                            type="checkbox"
                                            class="img-permission"
                                            name="permissions[{{$key}}]"
                                            value="{{ $key }}"
                                            @if(isset($notificationsMatrix['digital_download_permission']['download_group'][$key]) 
                                                && $notificationsMatrix['digital_download_permission']['download_group'][$key] == true)
                                                checked
                                            @endif
                                            >
                                    </x-table.cell>
                                @endforeach
                            </tr>
                            <tr>
                                <x-table.cell>Other</x-table.cell>
                                @foreach($selectOptionsEmailTo as $key => $value)
                                    <x-table.cell data-model="digital_download_permission" data-field="download_schoolPhoto">
                                        <input
                                            type="checkbox"
                                            class="img-permission"
                                            name="permissions[{{$key}}]"
                                            value="{{ $key }}"
                                            @if(isset($notificationsMatrix['digital_download_permission']['download_schoolPhoto'][$key]) 
                                                && $notificationsMatrix['digital_download_permission']['download_schoolPhoto'][$key] == true)
                                                checked
                                            @endif
                                            >
                                    </x-table.cell>
                                @endforeach
                            </tr> --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </form>
    </div>

    <h3 class="mb-4 text-black">Digital Image Configuration</h3>
    <p>Control which Digital Images are shared with the school via the portal, using the settings for each Job (TNJ). Settings on this page are saved automatically.</p>
    <div class="flex w-full gap-4 flex-col ml-4">
        <div class="flex gap-4">
            <input id="is-group-visible" type="hidden" value="@if($groupsTabValue)1 @else 0 @endif">
            <input type='hidden' value='{{$hash}}' name='jobHash'>
            <div class="w-[213px]">
                <x-form.select context="season" :options="$seasonOptions" class="w-full">Choose a Season</x-form.select>
            </div>
            <div id="job-selection-section" class="w-[502px]"> 
                <x-form.select context="job" :options="[]" class="w-full">Choose a Job (TNJ) </x-form.select>
                <div id="no-jobs-msg" class="d-none p-2">
                    <span class="text-alert">No jobs found. Please select a different season.</span>
                </div>
                <div id="job-select-loading" class="d-none">
                    <x-spinner.icon :size="8"/>
                </div>
            </div>
        </div>
        <div>
            <p class=" text-neutral-600">Note: Multiple Jobs (TNJs) can be configured per season to control which images are shared with the school through the portal. Select and configure each Job (TNJ) individually before proceeding to the next. Each of the settings below apply only to the Job selected above.</p>
        </div>

        <div id="release-dates-section" class="job-dependent-section">
            <h5 class="mb-4 text-black">Set Digital Image Release Dates</h5>
            <p>Select when Portrait 
                {{-- and Group  --}}
                Digital Images will be available on the portal for the school to view. The default date displayed is the date set in K2 for Parent Digital Downloads, which is also the earliest possible date. You can update the dates below if you wish to push the release of photos in the portal to a later date.</p>

            <div class="flex gap-4">
                <div class="w-[213px] flex items-center">
                    <strong>Portraits</strong>
                </div>
                <div class="w-[502px]"> 
                    <div class="relative" id="portrait_download_start_container">
                        <input
                            {{-- datepicker --}}
                            id="portrait_download_start_picker"
                            type="text" 
                            class="bg-gray-50 border border-neutral text-gray-900 mb-2 
                                    text-sm rounded-lg focus:ring-blue-500 focus:border-primary 
                                    block w-full pr-10 p-2.5"
                            placeholder="Select date">
                        <div class="absolute right-[16px] flex items-center top-[14px] pointer-events-none">
                            <span class="fa fa-calendar"></span>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="flex gap-4">
                <div class="w-[213px] flex items-center">
                    <strong>Groups</strong>
                </div>
                <div class="w-[502px]"> 
                    <div class="relative" id="group_download_start_container">
                        <input
                            id="group_download_start_picker"
                            type="text" 
                            class="bg-gray-50 border border-neutral text-gray-900 mb-2 
                                    text-sm rounded-lg focus:ring-blue-500 focus:border-primary 
                                    block w-full pr-10 p-2.5"
                            placeholder="Select date">
                        <div class="absolute right-[16px] flex items-center top-[14px] pointer-events-none">
                            <span class="fa fa-calendar"></span>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <div id="folders-table-section" class="job-dependent-section">
            <h5 id="folders-section" class="mb-4 text-black">Select Folders to Display</h5>
            <p>Tick the folders below to make their images available on the portal once the release dates have passed. Unticked folders will remain hidden.</p>
            <div class="d-none" id="jobTypeMsg"></div>
            {{-- <div class="w-[502px] mb-4" id="jobType"> 
                <x-form.select context="job_access_image" :options="$folderTypes" class="w-full">Optional: Filter folders by type </x-form.select>
            </div> --}}
            <div id="folder_config">
                @include('partials.photography.configure.folders')
            </div>
        </div>
    </div>
</div>

@if($jobsNeedingArchiving->isNotEmpty())
<div
    id="jobsNeedingArchiveModal"
    tabindex="-1"
    class="modal hidden overflow-y-auto overflow-x-hidden bg-[#00000060] fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
    role="dialog"
    aria-labelledby="jobs-archive-modal-title"
    aria-modal="true"
>
    <div class="relative p-4 w-50 max-w-[calc(100vw-2rem)] max-h-full mx-auto">
        <div class="jobs-archive-modal-panel">
            <div class="jobs-archive-modal-header">
                <div class="jobs-archive-modal-header-main">
                    <div class="jobs-archive-modal-header-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.5 4.5H13.5L12 13.5H4L2.5 4.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                            <path d="M6 6.5V11.5M10 6.5V11.5M5 4.5L5.5 2.5H10.5L11 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 id="jobs-archive-modal-title" class="jobs-archive-modal-title">Proofing Jobs Ready for Archive</h2>
                        <p class="jobs-archive-modal-subtitle">
                            These proofing jobs must be archived before they can be configured or viewed in Photography.
                        </p>
                    </div>
                </div>
                <div class="jobs-archive-modal-header-actions">
                    <button type="button" id="archive-all-photography-jobs-btn" class="jobs-archive-modal-footer-all">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2.5 4.5H13.5L12 13.5H4L2.5 4.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                            <path d="M6 6.5V11.5M10 6.5V11.5M5 4.5L5.5 2.5H10.5L11 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Archive All
                    </button>
                </div>
                <button type="button" class="jobs-archive-modal-close-top jobs-archive-modal-close" aria-label="Close modal">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="jobs-archive-modal-info" role="status">
                <svg class="jobs-archive-modal-info-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10 9V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="10" cy="6.25" r="0.9" fill="currentColor"/>
                </svg>
                <p class="mb-0">
                    These jobs have finished proofing (proofing completed date has passed or job is marked as completed) but are not archived yet.
                    Archive them to configure and view digital images in Photography.
                </p>
            </div>

            <div class="jobs-archive-modal-body">
                <div id="jobs-needing-archive-list" class="jobs-archive-modal-grid">
                    @foreach($jobsNeedingArchiving as $job)
                        @php
                            $encryptedJobId = Crypt::encryptString((string) $job->ts_job_id);
                            $proofDueLabel = $job->proof_due
                                ? Carbon::parse($job->proof_due)->format('d/m/Y g:i A')
                                : '—';
                            $jobStatusName = $job->job_status_name
                                ?? $job->job_status_internal_name
                                ?? '—';
                        @endphp
                        <div
                            class="archive-job-row jobs-archive-job-card"
                            data-job-id="{{ $encryptedJobId }}"
                            data-ts-job-id="{{ $job->ts_job_id }}"
                        >
                            <div class="jobs-archive-job-card-top">
                                <div class="jobs-archive-job-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 2.5H10.5L13.5 5.5V14.5C13.5 15.0523 13.0523 15.5 12.5 15.5H5C4.44772 15.5 4 15.0523 4 14.5V3.5C4 2.94772 4.44772 2.5 5 2.5Z" stroke="currentColor" stroke-width="1.3"/>
                                        <path d="M10.5 2.5V5.5H13.5" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                                        <path d="M6.5 8.5H10.5M6.5 11H9.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <span class="jobs-archive-job-season">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <rect x="1.5" y="2.5" width="9" height="8" rx="1" stroke="currentColor" stroke-width="1"/>
                                        <path d="M1.5 5H10.5" stroke="currentColor" stroke-width="1"/>
                                    </svg>
                                    Season {{ $job->season_code }}
                                </span>
                            </div>
                            <div class="jobs-archive-job-content">
                                <p class="jobs-archive-job-name">{{ $job->ts_jobname }}</p>
                                <p class="jobs-archive-job-due">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <circle cx="6" cy="6" r="4.5" stroke="currentColor" stroke-width="1"/>
                                        <path d="M6 4V6.5L7.5 7.5" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                    </svg>
                                    Proof due {{ $proofDueLabel }}
                                </p>
                                <p class="jobs-archive-job-status">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <circle cx="6" cy="6" r="4.5" stroke="currentColor" stroke-width="1"/>
                                        <path d="M4 6H8" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                    </svg>
                                    Job status: {{ $jobStatusName }}
                                </p>
                            </div>
                            <div class="jobs-archive-job-card-footer">
                                <button
                                    type="button"
                                    class="archive-photography-job-btn jobs-archive-job-btn"
                                    data-job-id="{{ $encryptedJobId }}"
                                    data-job-name="{{ $job->ts_jobname }}"
                                >
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M2.5 4.5H13.5L12 13.5H4L2.5 4.5Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                                        <path d="M6 6.5V11.5M10 6.5V11.5M5 4.5L5.5 2.5H10.5L11 4.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Archive
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="jobs-archive-modal-footer">
                <button type="button" class="jobs-archive-modal-close-btn jobs-archive-modal-close">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="{{ URL::asset('proofing-assets/vendors/moment/moment.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/vendors/js/flatpickr.js') }}"></script>
{{-- <script src="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/js/bootstrap-multiselect.js') }}"></script> --}}
<script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
<script>window.configureJobsBySeason = @json($configureJobsBySeason ?? new \stdClass());</script>
<script src="{{ URL::asset('proofing-assets/js/school/configure-new.js') }}?v={{ filemtime(public_path('proofing-assets/js/school/configure-new.js')) }}"></script>
@endpush
@endif
