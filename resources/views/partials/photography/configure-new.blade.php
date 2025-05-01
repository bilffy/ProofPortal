@section('css')
    {{-- Flatpickr --}}
    
    <link href="{{ URL::asset('proofing-assets/vendors/css/flatpickr.min.css') }}" rel="stylesheet" />
    {{-- Bootstrap Multiselect --}}
    <link href="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/css/bootstrap-multiselect.css')}}" rel="stylesheet" />
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
    </style>
@stop

@php
    use Carbon\Carbon;
    use App\Helpers\SchoolContextHelper;
    use App\Services\SchoolService;
    use App\Services\JobService;
    use App\Services\SeasonService;
    use App\Services\StatusService;
    use Illuminate\Support\Facades\Crypt;

    $defaultDate = Carbon::now();
    $schoolService = new SchoolService();
    $statusService = new StatusService();
    $jobService = new JobService($statusService);
    $seasonService = new SeasonService();
    
    $selectOptionsEmailTo = [
        'photocoordinator' => 'Photo Coordinator',
        'schooladmin' => 'School Admin',
        'teacher' => 'Teacher',
    ];

    $decryptedSchoolKey = SchoolContextHelper::getCurrentSchoolContext()->schoolkey;
    $selectedSchool = $schoolService->getSchoolBySchoolKey($decryptedSchoolKey)->first();
    $filePath = '';
    if ($selectedSchool && $selectedSchool->school_logo) {
        $filePath = 'school_logos/' . $selectedSchool->school_logo;
    }
    $hash = Crypt::encryptString(SchoolContextHelper::getCurrentSchoolContext()->schoolkey);
    $encryptedPath = $selectedSchool->school_logo ? Crypt::encryptString($filePath) : '';
    $seasons = $seasonService->getAllSeasonData('code', 'is_default', 'ts_season_id')->orderby('code','desc')->get();
    $defaultSeasonCode = $seasons->where('is_default', 1)->select('code', 'ts_season_id')->first();
    $syncJobsbySchoolkey =  $jobService->getActiveSyncJobsBySchoolkey($decryptedSchoolKey);
    $selectedFolders = [];

    $notificationsMatrix = $selectedSchool->digital_download_permission_notification;
    $notificationsMatrix = $notificationsMatrix ? json_decode($notificationsMatrix, true) : [];
    $imageUrl = $encryptedPath ? route('school.logo', ['encryptedPath' => $encryptedPath]) : '';
    $schoollogo = $encryptedPath ? route('school.logo', ['encryptedPath' => $encryptedPath]) : '';

    $seasonOptions['none'] = 'Choose a Season';
    foreach ($seasons as $season) {
        $seasonOptions[Crypt::encryptString($season->ts_season_id)] = $season->code;
    }

    $groupsTab = $AppSettingsHelper::getByPropertyKey('groups_tab');
    $groupsTabValue = $groupsTab ? $groupsTab->property_value === 'true' ? true : false : true;
    
    $folderTypes['all'] = 'Show All';

    if ($groupsTabValue) {
        $folderTypes['portrait'] = 'Portrait / Group';
        $folderTypes['special_group'] = 'Speciality';
    }

@endphp
<div class="relative">
    <h3 class="mb-4 text-black">School Settings</h3>
    <div class="flex w-full mb-16 flex-col">
        <input type='hidden' value='{{$hash}}' name='schoolHash' id="schoolHash">
        <div class="bg-neutral-200 w-full p-4 rounded flex gap-4">
            <div id="schoolLogo_container" class="relative bg-white w-[341px] h-[246px] mb-4 p-4 items-center flex justify-center">
                <img id="schoolLogoPreview"
                    src={{ $imageUrl }}
                    alt="School Logo Preview"
                    class="object-contain w-full h-full"
                    style="{{ !isset($selectedSchool->school_logo) ? 'display: none;' : '' }}"
                />

                <input type="file"
                    class="form-control-file schoolLogo d-none"
                    id="schoolLogo"
                    name="schoolLogo" accept=".jpg, .jpeg, .png">
                
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
                <p class="mb-0">{{ "$selectedSchool->address, $selectedSchool->suburb, $selectedSchool->postcode" }}</p>
            </div>
        </div>
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
                            <x-table.headerCell sortable="{{false}}">Photo Coordinator</x-table.headerCell>
                            <x-table.headerCell sortable="{{false}}">School Admin</x-table.headerCell>
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
                            <tr>
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
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
            <p>Select when Portrait and Group Digital Images will be available on the portal for the school to view. The default date displayed is the date set in K2 for Parent Digital Downloads, which is also the earliest possible date. You can update the dates below if you wish to push the release of photos in the portal to a later date.</p>

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
            <div class="flex gap-4">
                <div class="w-[213px] flex items-center">
                    <strong>Groups</strong>
                </div>
                <div class="w-[502px]"> 
                    <div class="relative" id="group_download_start_container">
                        <input
                            {{-- datepicker --}}
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
            </div>
        </div>

        <div class="job-dependent-section">
            <h5 id="folders-section" class="mb-4 text-black">Select Folders to Display</h5>
            <p>Tick the folders below to make their images available on the portal once the release dates have passed. Unticked folders will remain hidden.</p>
            <div class="d-none" id="jobTypeMsg"></div>
            <div class="w-[502px] mb-4" id="jobType"> 
                <x-form.select context="job_access_image" :options="$folderTypes" class="w-full">Optional: Filter folders by type </x-form.select>
            </div>
            <div id="folder_config">
                @include('partials.photography.configure.folders')
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    jQuery.noConflict();
</script>
<script src="{{ URL::asset('proofing-assets/vendors/moment/moment.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/vendors/js/flatpickr.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/js/bootstrap-multiselect.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/js/school/configure-new.js') }}"></script>
@endpush
