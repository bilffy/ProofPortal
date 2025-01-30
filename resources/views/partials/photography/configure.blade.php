@section('css')
    {{-- Flatpickr --}}
    <link href="{{ URL::asset('proofing-assets/vendors/css/flatpickr.min.css')}}" rel="stylesheet" />
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
@endphp
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                {{ __(':name Configuration', ['name' => $selectedSchool->name]) }}
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-12 m-auto">
            <div class="card">
                <div class="card-header">
                    <legend>{{ __('School Settings') }}</legend>
                </div>
                <div class="card-body">
                    <input type='hidden' value='{{$hash}}' name='schoolHash' id="schoolHash">

                    <div class="row">
                        <div class="col-12 m-auto">
                            <p class="h5 lead mb-1"><strong>{{ __('School Details') }}</strong></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 m-auto">
                            <div class="form-group row text mb-0">
                                <label for="schoolLogo" class="col-md-6 form-control-label">{{ __('School Logo') }}</label>
                                <div class="col-md-7 text">
                                    <div class="form-group">
                                        <div class="input-group" id="schoolLogo_container">
                                            {{-- <input type="file" class="form-control-file school-logo-upload" id="schoolLogo" name="schoolLogo" accept=".jpg, .jpeg, .png"> --}}
                                            <label for="schoolLogo" class="custom-file-label btn btn-primary">
                                                Upload File
                                            </label>
                                            <input type="file"
                                                class="form-control-file schoolLogo d-none"
                                                id="schoolLogo"
                                                name="schoolLogo" accept=".jpg, .jpeg, .png">
                                        </div>
                                    </div>
                                    <!-- Preview container for the uploaded image -->
                                    <img 
                                        id="schoolLogoPreview" 
                                        src="{{ $imageUrl }}" 
                                        alt="School Logo Preview" 
                                        style="{{ !isset($selectedSchool->school_logo) ? 'display: none;' : '' }} width: 100px; margin-top: 10px; height: 64px;" 
                                    />

                                    <a href="#" id="deleteSchoolLogo" style="color: #00B3DF; margin-top: 5px; display: block; padding-left: 25px;" 
                                    @if(!isset($selectedSchool->school_logo)) class="d-none" @endif>
                                        Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 m-auto">
                            <div class="form-group row text mb-0">
                                <label for="address" class="col-md-6 form-control-label">{{ __('Address') }}</label>
                                <!-- Date picker with equal spacing -->
                                <div class="col-md-7 text">
                                    <div class="form-group">
                                        <div class="input-group" id="address_container">
                                            <input type="text" id="address_picker" class="form-control" name="address" value="{{$selectedSchool->address}}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 m-auto">
                            <div class="form-group row text mb-0">
                                <label for="schoolName" class="col-md-6 form-control-label">{{ __('School Name') }}</label>
                                <!-- Date picker with equal spacing -->
                                <div class="col-md-7 text">
                                    <div class="form-group">
                                        <div class="input-group" id="schoolName_container">
                                            <input type="text" id="schoolName_picker" class="form-control" name="schoolName" value="{{$selectedSchool->name}}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 m-auto">
                            <div class="form-group row text mb-0">
                                <label for="postcode" class="col-md-6 form-control-label">{{ __('Postcode') }}</label>
                                <!-- Date picker with equal spacing -->
                                <div class="col-md-7 text">
                                    <div class="form-group">
                                        <div class="input-group" id="postcode_container">
                                            <input type="text" id="postcode_picker" class="form-control" name="postcode" value="{{$selectedSchool->postcode}}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6 m-auto">
                        </div>
                        <div class="col-md-6 m-auto">
                            <div class="form-group row text mb-0">
                                <label for="suburb" class="col-md-6 form-control-label">{{ __('Suburb') }}</label>
                                <!-- Date picker with equal spacing -->
                                <div class="col-md-7 text">
                                    <div class="form-group">
                                        <div class="input-group" id="suburb_container">
                                            <input type="text" id="suburb_picker" class="form-control" name="suburb" value="{{$selectedSchool->suburb}}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Email Notification for Download --}}
                    <form id='digital_download_form'>
                        <div class="row mt-4">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>Digital Download Permission</strong></p>
                                <p>Manage school download permission on bases of roles.</p>
                            </div>
                        </div>

                        <div class="row text mt-3">
                            {{-- <label class="col-md-12 form-control-label mt-1" for="proofing-timeline">Download Permission</label> --}}
            
                            <div class="col-md-4">
                                <p class="mt-1 mb-1">Portrait</p>
                                <select name="digital_download_permission[download_portrait][]" id="download_portrait" class="download_portrait" multiple data-model="digital_download_permission" data-field="download_portrait">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_permission']['download_portrait'][$key]) && $notificationsMatrix['digital_download_permission']['download_portrait'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <p class="mt-1 mb-1">Group Photo</p>
                                <select name="digital_download_permission[download_group][]" id="download_group" class="download_group" multiple data-model="digital_download_permission" data-field="download_group">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_permission']['download_group'][$key]) && $notificationsMatrix['digital_download_permission']['download_group'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <p class="mt-1 mb-1">Other</p>
                                <select name="digital_download_permission[download_schoolPhoto][]" id="download_schoolPhoto" class="download_schoolPhoto" multiple data-model="digital_download_permission" data-field="download_schoolPhoto">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_permission']['download_schoolPhoto'][$key]) && $notificationsMatrix['digital_download_permission']['download_schoolPhoto'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row d-none">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>Email Notifications</strong></p>
                                <p>Send email notifications at the specified events. You can select multiple people to receive the email notifications.</p>
                            </div>
                        </div>

                        <div class="row d-none">
                            <div class="col-md-12 m-auto">
                                <div class="form-group row text mb-0">
                                    <!-- Label for the checkbox -->
                                    <label for="notifications_enabled_checkbox" class="col-md-6 form-control-label">
                                        Enable Email Notifications
                                    </label>
                        
                                    <?php
                                        $isReviewEnabledChecked = $selectedSchool->is_email_notification == true ? 'checked' : '';
                                    ?>
                        
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <div class="input-group" id="notifications_enabled_container">
                                                <!-- Checkbox input -->
                                                <input type="checkbox"
                                                    id="notifications_enabled_checkbox"
                                                    name="notifications_enabled"
                                                    class="form-check-input notifications_enabled ml-0" {{ $isReviewEnabledChecked }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row text mt-3 d-none" id="digital_download_start">
                            <label class="col-md-6 form-control-label mt-1" for="proofing-timeline">Digital Download</label>
            
                            <div class="col-md-2">
                                <p class="mt-1 mb-1">Portrait</p>
                                <select name="digital_download_notification[notification_portrait][]" id="notification_portrait" class="notification_portrait" multiple data-model="digital_download_notification" data-field="notification_portrait">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_notification']['notification_portrait'][$key]) && $notificationsMatrix['digital_download_notification']['notification_portrait'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <p class="mt-1 mb-1">Group Photo</p>
                                <select name="digital_download_notification[notification_group][]" id="notification_group" class="notification_group" multiple data-model="digital_download_notification" data-field="notification_group">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_notification']['notification_group'][$key]) && $notificationsMatrix['digital_download_notification']['notification_group'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <p class="mt-1 mb-1">Other</p>
                                <select name="digital_download_notification[notification_schoolPhoto][]" id="notification_schoolPhoto" class="notification_schoolPhoto" multiple data-model="digital_download_notification" data-field="notification_schoolPhoto">
                                    @foreach($selectOptionsEmailTo as $key => $value)
                                        <option value="{{ $key }}" @if(isset($notificationsMatrix['digital_download_notification']['notification_schoolPhoto'][$key]) && $notificationsMatrix['digital_download_notification']['notification_schoolPhoto'][$key] == true) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>

    {{-- TNJ selection --}}
    <div class="row">
        <div class="col-12 m-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <legend class="mb-0">{{ __('Schools Portal Configuration') }}
                        <span class="d-none" id="SeasoncodeDisplay"></span>
                    </legend>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="select_season">Choose a season</label>
                            <select id="select_season" name="select_season" class="form-control">
                                <option value="">--Please Select Season--</option>
                                @foreach ($seasons as $season)
                                    <option value="{{ Crypt::encryptString($season->ts_season_id) }}">{{ $season->code }}</option>
                                @endforeach
                            </select>
                        </div> 
                    </div>                        
                </div>                
                <div class="card-body">
                    <input type='hidden' value='{{$hash}}' name='jobHash'>
                    <div class="d-none" id="jobSelect">
                        <div class="row mt-3">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>Please select a job to configure for digital images in portal</strong></p>
                            </div>
                        </div>
                    
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <select id="job_select" name="job_select" class="form-control">
                                        <option value="">--Choose a Job--</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>                     

                    <div id="digital_download" class="d-none">
                        <div class="row mt-4">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>{{ __('Digital Download Timeline') }}</strong></p>
                                <p>{{ __('Define the key dates for the digital download available.') }}</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 m-auto">
                                <!-- New Portrait Download row with equal spacing -->
                                <div class="form-group row text mb-0">
                                    <label for="portrait_download_start" class="col-md-4 form-control-label">{{ __('Portrait Download') }}</label>
                                    
                                    <!-- Checkbox with equal spacing -->
                                    <div class="col-md-4 d-flex align-items-center">
                                        <div class="form-group">
                                            <input type="checkbox" disabled="disabled" id="portrait_download_allowed" class="form-check-input ml-0" name="portrait_download_allowed" />
                                            <label for="portrait_download_allowed" class="form-check-label ml-2">Allowed</label>
                                        </div>
                                    </div>
                        
                                    <!-- Date picker with equal spacing -->
                                    <div class="col-md-4 text" id="portrait_download_start">
                                        <div class="form-group">
                                            <div class="input-group" id="portrait_download_start_container">
                                                <input type="text" id="portrait_download_start_picker" autocomplete="off" class="form-control" name="portrait_download_start" />
                                                <span class="input-group-addon">
                                                    <span class="fa fa-calendar"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 m-auto">
                                <!-- New Group Photo row with equal spacing -->
                                <div class="form-group row text mb-0">
                                    <label for="group_download_start" class="col-md-4 form-control-label">{{ __('Group Photo Download') }}</label>
                                    
                                    <!-- Checkbox with equal spacing -->
                                    <div class="col-md-4 d-flex align-items-center">
                                        <div class="form-group">
                                            <input type="checkbox" disabled="disabled" id="group_download_allowed" class="form-check-input ml-0" name="group_download_allowed" />
                                            <label for="group_download_allowed" class="form-check-label ml-2">Allowed</label>
                                        </div>
                                    </div>
                        
                                    <!-- Date picker with equal spacing -->
                                    <div class="col-md-4 text" id="group_download_start">
                                        <div class="form-group">
                                            <div class="input-group" id="group_download_start_container">
                                                <input type="text" id="group_download_start_picker" autocomplete="off" class="form-control" name="group_download_start" />
                                                <span class="input-group-addon">
                                                    <span class="fa fa-calendar"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none" id="jobTypeMsg"></div>
                    <div class="d-none" id="jobType">
                        <div class="row mt-3">
                            <div class="col-12 m-auto">
                                <p class="h5 lead mb-1"><strong>Please choose a job type to access images in portal</strong></p>
                            </div>
                        </div>
                    
                        <div class="row">
                            <div class="col-lg-6">
                                <select id="job_access_image" name="job_access_image" class="form-control">
                                    <option value="0">--Choose a Job Type to Access Images--</option>
                                    <option value="all">None</option>
                                    <option value="portrait">Portrait / Group</option>
                                    <option value="special_group">Speciality</option>
                                </select>
                            </div>
                        </div>
                    </div>   

                    <div class="row mt-2 pl-3">
                        <div id="ajax-response-readable" class="alert d-none" role="alert"></div>
                    </div>

                    {{-- Folder Configuration --}}
                    <div id="folder_config" class="d-none">
                        <hr>                        
                        @include('proofing.franchise.school.configure-school-folderconfig')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')

    <script>
        jQuery.noConflict();
    </script>

    <script src="{{ URL::asset('proofing-assets/vendors/moment/moment.js') }}"></script>
    <script src="{{ URL::asset('proofing-assets/vendors/js/flatpickr.js') }}"></script>
    <script src="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/js/bootstrap-multiselect.js') }}"></script>
    <script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
    <script src="{{ URL::asset('proofing-assets/js/school/configure.js') }}"></script>
@stop
