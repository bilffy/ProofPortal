@section('css')
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

<div>
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
@endphp
<h3 class="mb-4 text-black">School Settings</h3>
    <div class="flex w-full mb-16 flex-col">
        <div class="bg-neutral-200 w-full p-4 rounded flex gap-4">
            <div class="relative bg-white w-[341px] h-[246px] mb-4 p-4 items-center flex justify-center">
                <img src={{ Vite::asset('public/proofing-assets/img/schoolLogo/5.png') }} alt="" class="object-contain w-full h-full">
                <button 
                    class="
                        p-2 rounded-s
                        absolute bottom-[16px] right-[16px] bg-white bg-opacity-75 transition-all
                        hover:bg-primary-100 hover:transition-all 
                        ">Change Logo</button>
            </div>
            <div>
                <p class="mb-2"><strong>Bundaberg State High School</strong></p>
                <p>123 School Road, BUNDABERG SOUTH, 4670</p>
                
            </div>
        </div>
        <div class="w-full p-4">
            <div>
                <p class="mb-2"><strong>Digital Images Permissions</strong></p>
                <p>Select which User Roles are permitted to View & Download the Digital Images via the Portal </p>
            </div>
            <div>
                <div class="w-full border rounded lg:w-full xl:w-1/2">
                    <table class=" w-full">
                        <thead>
                            <x-table.headerCell sortable="false"> </x-table.headerCell>
                            <x-table.headerCell sortable="false">Photo Coordinator</x-table.headerCell>
                            <x-table.headerCell sortable="false">School Admin</x-table.headerCell>
                            <x-table.headerCell sortable="false">Teacher</x-table.headerCell>
                        </thead>
                        <tbody>
                            <tr>
                                <x-table.cell>Portrait</x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[photocoordinator]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[schooladmin]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[teacher]" value="1" >
                                </x-table.cell>
                            </tr>
                            <tr>
                                <x-table.cell>Group Photo</x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[photocoordinator]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[schooladmin]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[teacher]" value="1" >
                                </x-table.cell>
                            </tr>
                            <tr>
                                <x-table.cell>Other</x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[photocoordinator]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[schooladmin]" value="1" >
                                </x-table.cell>
                                <x-table.cell>
                                    <input type="checkbox" name="permissions[teacher]" value="1" >
                                </x-table.cell>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-4 text-black">Digital Image Configuration</h3>
    <p>Control which Digital Images are shared with the school via the portal, using the settings for each Job (TNJ). Settings on this page are saved automatically.</p>
    <div class="flex w-full mb-16 gap-4 flex-col ml-4">
        <div class="flex gap-4">
            <div class="w-[213px] ">
                <x-form.select class="w-full">Choose a Season</x-form.select>
            </div>
            <div class="w-[502px]"> 
                <x-form.select class="w-full">Choose a Job (TNJ) </x-form.select>
            </div>
        </div>
        <div>
            <p class=" text-neutral-600">Note: Multiple Jobs (TNJs) can be configured per season to control which images are shared with the school through the portal. Select and configure each Job (TNJ) individually before proceeding to the next. Each of the settings below apply only to the Job selected above.</p>
        </div>

        <div>
            <div class="mb-16">
                <h5 class="mb-4 text-black">Set Digital Image Release Dates</h5>
                <p>Select when Portrait and Group Digital Images will be available on the portal for the school to view. The default date displayed is the date set in K2 for Parent Digital Downloads, which is also the earliest possible date. You can update the dates below if you wish to push the release of photos in the portal to a later date.</p>

                <div class="flex gap-4">
                    <div class="w-[213px] flex items-center">
                        <strong>Portraits</strong>
                    </div>
                    <div class="w-[502px]"> 
                        <div class="relative" id="portrait_download_start_container">
                            <input datepicker id="portrait_download_start_picker" type="text" 
                                class="bg-gray-50 border border-neutral text-gray-900 mb-2 
                                        text-sm rounded-lg focus:ring-blue-500 focus:border-primary 
                                        block w-full pr-10 p-2.5" placeholder="Select date">
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
                        <div class="relative" id="portrait_download_start_container">
                            <input datepicker id="portrait_download_start_picker" type="text" 
                                class="bg-gray-50 border border-neutral text-gray-900 mb-2 
                                        text-sm rounded-lg focus:ring-blue-500 focus:border-primary 
                                        block w-full pr-10 p-2.5" placeholder="Select date">
                            <div class="absolute right-[16px] flex items-center top-[14px] pointer-events-none">
                                <span class="fa fa-calendar"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h5 class="mb-4 text-black">Select Folders to Display</h5>
                <p>Tick the folders below to make their images available on the portal once the release dates have passed. Unticked folders will remain hidden.</p>
                <div class="w-[502px] mb-4"> 
                    <x-form.select class="w-full">Optional: Filter folders by type </x-form.select>
                </div>
                <div>
                    <div class="w-full border rounded lg:w-full xl:w-1/2">
                        <table class=" w-full">
                            <thead>
                                <x-table.headerCell sortable="false">Folder</x-table.headerCell>
                                <x-table.headerCell sortable="false">Portraits Tab</x-table.headerCell>
                                <x-table.headerCell sortable="false">Groups Tab</x-table.headerCell>
                            </thead>
                            <tbody>
                                <tr>
                                    <x-table.cell>Portrait</x-table.cell>
                                    <x-table.cell class="flex items-center">
                                        <input type="checkbox" class=" mr-1" name="permissions[photocoordinator]" value="1" >
                                        <label class="ml-1 mb-0" for="">22 portraits</label>
                                    </x-table.cell>
                                    <x-table.cell class=" items-center">
                                        <input type="checkbox" class=" mr-1" name="permissions[schooladmin]" value="1" >
                                        <label class="ml-1 mb-0" for="">1 group photo</label>
                                    </x-table.cell>
                            
                                </tr>
                                <tr>
                                    <x-table.cell>Group Photo</x-table.cell>
                                    <x-table.cell class="flex items-center">
                                        <input type="checkbox" class=" mr-1" name="permissions[photocoordinator]" value="1" >
                                        <label class="ml-1 mb-0" for="">22 portraits</label>
                                    </x-table.cell>
                                    <x-table.cell class=" items-center">
                                        <input type="checkbox" class=" mr-1" name="permissions[schooladmin]" value="1" >
                                        <label class="ml-1 mb-0" for="">1 group photo</label>
                                    </x-table.cell>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

</div>

@section('js')
    <script type="module" src="{{ Vite::asset('public/proofing-assets/js/school/configure.js') }}"></script>
@stop
