@extends('proofing.layouts.master')
@section('title', 'Configure Job')

@section('css')
    <link href="{{ URL::asset('proofing-assets/vendors/css/flatpickr.min.css')}}" rel="stylesheet" />
    <link href="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/css/bootstrap-multiselect.css')}}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('proofing-assets/custom/validate.css') }}">

    <style>

        .table-fixed-header {
            position: relative;
            overflow-y: auto;
            max-height: 1000px; /* Adjust the height as needed */
        }
        
        /* Sticky header cells */
        .table-fixed-header thead th {
            position: sticky;
            top: 0;
            background: #F0F3F5;
            z-index: 10;

            /* ✅ SINGLE border that stays visible */
            border-top: 1px solid #dee2e6 !important;
        }

        /* REMOVE extra borders and shadows */
        .table-fixed-header thead {
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: none;
            border-bottom: none;
        }

        /* If you still want separation line under header, use this instead */
        .table-fixed-header thead tr {
            box-shadow: 0 2px 0 #dee2e6;
        }

        .progress {
            height: 30px;
        }
        
        .progress-bar {
            background-color: #88c671;
        }

        /* Style the input field if you want to display it when users select a file */
        input[type="file"]:focus + .custom-file-label {
            outline: none;
            box-shadow: 0 0 5px #007bff;
        }
        .flatpickr-input {
            background-color: white !important;
        }
        /* Fix upload button overflowing column */
        td .custom-file-label.btn {
            display: inline-block;
            width: 100%;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }

        /* Prevent Bootstrap pseudo-elements from breaking layout */
        .custom-file-label::after {
            display: none !important;
        }

    </style> 
@stop

@section('content')

    @php
        use Illuminate\Support\Facades\Crypt;
        use Carbon\Carbon;
        use App\Helpers\Helper;
    @endphp

@if(Session::has('selectedJob') && Session::has('selectedSeason'))
    @php
        $notificationsMatrix = $selectedJob->notifications_matrix;
        $notificationsMatrix = $notificationsMatrix ? json_decode($notificationsMatrix, true) : [];
    @endphp

    {{-- @if(Auth::user()->hasRole(['superadmin', 'admin', 'franchise'])) --}}
        @php

            $defaultDate = Carbon::now()->format('d/m/Y g:i A');
            $jobKeyHash = Crypt::encryptString($selectedJob->ts_jobkey);
            $jobIdHash = Crypt::encryptString($selectedJob->ts_job_id);

            if ($selectedJob->proof_start) {
                $review_due_start = Carbon::parse($selectedJob->proof_start)->format('d/m/Y g:i A');
                $trigger_review_due_start = false;
            } else {
                $review_due_start = $defaultDate;
                $trigger_review_due_start = true;
            }

            if ($selectedJob->proof_warning) {
                $review_due_warning = Carbon::parse($selectedJob->proof_warning)->format('d/m/Y g:i A');
                $trigger_review_due_warning = false;
            } else {
                $review_due_warning = $defaultDate;
                $trigger_review_due_warning = true;
            }

            if ($selectedJob->proof_due) {
                $review_due = Carbon::parse($selectedJob->proof_due)->format('d/m/Y g:i A');
                $trigger_review_due = false;
            } else {
                $review_due = $defaultDate;
                $trigger_review_due = true;
            }

            if ($selectedJob->proof_catchup) {
                $review_due_catchup = Carbon::parse($selectedJob->proof_catchup)->format('d/m/Y g:i A');
                $trigger_review_due_catchup = false;
            } else {
                $review_due_catchup = $defaultDate;
                $trigger_review_due_catchup = true;
            }

            $isVisibleForProofingList = [];
            $isVisibleForProofingCounter = ['true' => 0, 'false' => 0];

            $isEditPortraitsList = [];
            $isEditPortraitsCounter = ['true' => 0, 'false' => 0];

            $isEditGroupList = [];
            $isEditGroupCounter = ['true' => 0, 'false' => 0];

            $isSubjectListAllowedList = [];
            $isSubjectListAllowedCounter = ['true' => 0, 'false' => 0];

            $isEditPrincipalList = [];
            $isEditPrincipalCounter = ['true' => 0, 'false' => 0];

            $isEditDeputyList = [];
            $isEditDeputyCounter = ['true' => 0, 'false' => 0];

            $isEditTeacherList = [];
            $isEditTeacherCounter = ['true' => 0, 'false' => 0];

            $isEditSalutationList = [];
            $isEditSalutationCounter = ['true' => 0, 'false' => 0];

            $isEditJobTitleList = [];
            $isEditJobTitleCounter = ['true' => 0, 'false' => 0];

            $isEditJobShowSalutationPortraitList = [];
            $isEditJobShowSalutationPortraitCounter = ['true' => 0, 'false' => 0];

            $isEditJobPrefixSuffixPortraitList = [];
            $isEditJobPrefixSuffixPortraitCounter = ['true' => 0, 'false' => 0];

            $isEditJobShowSalutationGroup = [];
            $isEditJobShowSalutationGroupCounter = ['true' => 0, 'false' => 0];

            $isEditJobPrefixSuffixGroupList = [];
            $isEditJobPrefixSuffixGroupCounter = ['true' => 0, 'false' => 0];  

            // Iterate over folder details and update lists and counters
            foreach ($selectedFolders as $folderDetail) {
                $isVisibleForProofingList[$folderDetail->ts_folder_id] = $folderDetail->is_visible_for_proofing;
                $isVisibleForProofingCounter[$folderDetail->is_visible_for_proofing ? 'true' : 'false']++;

                $isEditPortraitsList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_portraits;
                $isEditPortraitsCounter[$folderDetail->is_edit_portraits ? 'true' : 'false']++;

                $isEditGroupList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_groups;
                $isEditGroupCounter[$folderDetail->is_edit_groups ? 'true' : 'false']++;

                $isEditJobTitleList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_job_title;
                $isEditJobTitleCounter[$folderDetail->is_edit_job_title ? 'true' : 'false']++;

                $isEditJobShowSalutationPortraitList[$folderDetail->ts_folder_id] = $folderDetail->show_salutation_portraits;
                $isEditJobShowSalutationPortraitCounter[$folderDetail->show_salutation_portraits ? 'true' : 'false']++;

                $isEditJobPrefixSuffixPortraitList[$folderDetail->ts_folder_id] = $folderDetail->show_prefix_suffix_portraits;
                $isEditJobPrefixSuffixPortraitCounter[$folderDetail->show_prefix_suffix_portraits ? 'true' : 'false']++;

                $isEditJobShowSalutationGroupList[$folderDetail->ts_folder_id] = $folderDetail->show_salutation_groups;
                $isEditJobShowSalutationGroupCounter[$folderDetail->show_salutation_groups ? 'true' : 'false']++;

                $isEditJobPrefixSuffixGroupList[$folderDetail->ts_folder_id] = $folderDetail->show_prefix_suffix_groups;
                $isEditJobPrefixSuffixGroupCounter[$folderDetail->show_prefix_suffix_groups ? 'true' : 'false']++;

                $isEditSalutationList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_salutation;
                $isEditSalutationCounter[$folderDetail->is_edit_salutation ? 'true' : 'false']++;

                $isSubjectListAllowedList[$folderDetail->ts_folder_id] = $folderDetail->is_subject_list_allowed;
                $isSubjectListAllowedCounter[$folderDetail->is_subject_list_allowed ? 'true' : 'false']++;

                $isEditPrincipalList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_principal;
                $isEditPrincipalCounter[$folderDetail->is_edit_principal ? 'true' : 'false']++;

                $isEditDeputyList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_deputy;
                $isEditDeputyCounter[$folderDetail->is_edit_deputy ? 'true' : 'false']++;

                $isEditTeacherList[$folderDetail->ts_folder_id] = $folderDetail->is_edit_teacher;
                $isEditTeacherCounter[$folderDetail->is_edit_teacher ? 'true' : 'false']++;
            }

            // $artifactListingByTokenAndUrl = [];
            // foreach ($artifacts as $artifact) {
            //     $artifactListingByTokenAndUrl[$artifact->token] = $artifact->full_url;
            // }

            $imageOptions = [
                'class' => "mx-auto d-block",
                'style' => "max-width: 100%;",
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    {{ __(':name Configuration', ['name' => $selectedJob->ts_jobname]) }}
                </h1>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12 m-auto">
                <a href="{{ route('proofing') }}" class="btn btn-primary float-right pl-4 pr-4">{{ __('Done') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 m-auto">
                <div class="card">
                    <div class="card-header">
                        <legend>{{ __('School Configuration') }}</legend>
                    </div>
                    <div class="card-body">
                            <input type='hidden' value='{{$hash}}' name='jobHash'>
                        {{-- Proofing Timeline --}}
                            @include('proofing.franchise.configure.proofing-timeline')
                        {{-- Proofing Timeline --}}
                        <hr>
                        {{-- Email Notifiction --}}
                            @include('proofing.franchise.configure.email-notifications')
                        {{-- Email Notifiction --}}
                        <hr>
                        {{-- TNJ Data Refresh --}}
                            @include('proofing.franchise.configure.tnj-refresh',['jobKeyHash' => $jobKeyHash])
                        {{-- TNJ Data Refresh --}}
                    </div>
                </div>
            </div>
        </div>

    {{-- Edit Folder configs --}}
        @include('proofing.franchise.configure.folder-config')
    {{-- Edit Folder configs --}}

    {{-- Delete & Archive Job --}}
        @include('proofing.franchise.configure.archive-delete-job',['jobIdHash' => $jobIdHash])
    {{-- Delete & Archive Job --}}    

    {{--Modal--}}
    <!-- Modal -->
    <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <img class="img-fluid mx-auto d-block">
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
    {{--Modal--}}

    {{-- @endif --}}
@else

    @include('proofing.franchise.flash-error')

@endif

@endsection

@if(Session::has('selectedJob') && Session::has('selectedSeason'))

    @section('js')
    <script src="{{ URL::asset('proofing-assets/vendors/js/flatpickr.js') }}"></script>
        <script>
            $(document).ready(function () {
                jQuery.noConflict();

                // const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                // console.log(userTimezone);
                // console.log(window.createPopper)

                window.createPopper = Popper.createPopper;

                var modelTag;
                var fieldTag;
                var roleTag;

                var multiSelectOptionsDefault = {
                buttonWidth: '100%',
                buttonClass: 'btn btn-default border',
                onChange: function (option, checked, select) {
                    const $parent = $(option).parent();
                    modelTag = $parent.data('model');
                    fieldTag = $parent.data('field');
                    roleTag = $(option).val();

                    // Debounced call to minimize delay
                    debouncedInsertEmailNotification(modelTag, fieldTag, roleTag);
                },
            };

            // Grouped initialization
            const multiSelectSelectors = [
                '#proofing-timeline-start-date',
                '#proofing-timeline-warning-date',
                '#proofing-timeline-due-date',
                '#proofing-timeline-catchup-date',
                '#school-actions-modified',
                '#school-actions-completed',
                '#school-actions-unlocked',
                '#folder-actions-modified',
                '#folder-actions-completed',
                '#folder-actions-unlocked',
            ];

            // Initialize all multi-selects
            multiSelectSelectors.forEach((selector) => {
                $(selector).multiselect(multiSelectOptionsDefault);
            });
                
                /*
            //     Functions to send the date changes
            //     */
                // let startDateTimePickerInstance = $('#review_due_start_picker').data("DateTimePicker");
                // let warningDateTimePickerInstance = $('#review_due_warning_picker').data("DateTimePicker");
                // let dueDateTimePickerInstance = $('#review_due_picker').data("DateTimePicker");
                // let catchupDateTimePickerInstance = $('#review_due_catchup_picker').data("DateTimePicker");

                // if (startDateTimePickerInstance) {
                //     let initialValue = startDateTimePickerInstance.date();
                //     $('#review_due_start_picker').on('dp.change', function (e) {
                //         if (!e.oldDate || !e.date.isSame(initialValue)) {
                //             adjustReviewDates(e.date, 'proof_start');
                //         }
                //     });
                // }

                // if (warningDateTimePickerInstance) {
                //     let initialValue = warningDateTimePickerInstance.date();
                //     $('#review_due_warning_picker').on('dp.change', function (e) {
                //         if (!e.oldDate || !e.date.isSame(initialValue)) {
                //             adjustReviewDates(e.date, 'proof_warning');
                //         }
                //     });
                // }

                // if (dueDateTimePickerInstance) {
                //     let initialValue = dueDateTimePickerInstance.date();
                //     $('#review_due_picker').on('dp.change', function (e) {
                //         if (!e.oldDate || !e.date.isSame(initialValue)) {
                //             adjustReviewDates(e.date, 'proof_due');
                //         }
                //     });
                // }

                // if (catchupDateTimePickerInstance) {
                //     let initialValue = catchupDateTimePickerInstance.date();
                //     $('#review_due_catchup_picker').on('dp.change', function (e) {
                //         if (!e.oldDate || !e.date.isSame(initialValue)) {
                //             adjustReviewDates(e.date, 'proof_catchup');
                //         }
                //     });
                // }

               // Initialize Flatpickr for all relevant fields
                    $('.flatpickr-field').each(function () {
                        var $field = $(this);
                        $field.flatpickr({
                            enableTime: true,
                            dateFormat: "d/m/Y H:i K", // Same format as 'DD/MM/YYYY HH:mm A'
                            disableMobile: true,
                            onClose: function (selectedDates, dateStr, instance) {
                                if (dateStr) {
                                    var selectedDate = $field.val();
                                    var fieldId = $field.attr('id'); // Use saved reference to get the ID
                                    // Map field ID to specific actions
                                    var actionMap = {
                                        'review_due_start_picker': 'proof_start',
                                        'review_due_warning_picker': 'proof_warning',
                                        'review_due_picker': 'proof_due',
                                        'review_due_catchup_picker': 'proof_catchup',
                                    };
                                    var jobHash = document.querySelector('input[name="jobHash"]').value;
                                    var targetUrl = base_url + "/franchise/config-job/proofing-timeline/email-send";
                                
                                    // Prepare formData here (adjust as needed)
                                    var formData = new FormData();
                                    formData.append('date', convertTo24HourFormat(selectedDate)); // Replace with actual data
                                    formData.append('dataType', actionMap[fieldId]); // Replace with actual data
                                    formData.append('jobHash', jobHash); // Replace with actual data
                                    sendAjaxRequest(targetUrl, formData);
                                } 
                            }
                        });
                    });

                    // Attach change event listeners to all fields
                    $('.flatpickr-field').on('change', function () {
                        var selectedDate = $(this).val(); // Get the value of the input (string)
                        var fieldId = $(this).attr('id'); // Get the ID of the field

                        // Map field ID to specific actions
                        var actionMap = {
                            'review_due_start_picker': 'proof_start',
                            'review_due_warning_picker': 'proof_warning',
                            'review_due_picker': 'proof_due',
                            'review_due_catchup_picker': 'proof_catchup',
                        };

                        // Call the function with the mapped action
                        if (actionMap[fieldId]) {
                            adjustReviewDates(convertTo24HourFormat(selectedDate), actionMap[fieldId]);
                        }
                    });

                    function convertTo24HourFormat(dateStr) {
                        // Split the input string into date and time parts
                        const dateParts = dateStr.split(' '); // ["26/11/2024", "03:43:11 PM"]
                        const date = dateParts[0].split('/'); // ["26", "11", "2024"]
                        let time = dateParts[1].split(':'); // ["03", "43", "11", "PM"]
                        const hours = parseInt(time[0]); // 3 (hours part)
                        const minutes = time[1]; // 43 (minutes part)
                        const seconds = '00'; // 11 (seconds part)
                        const ampm = time[3]; // PM or AM
                        
                        // Convert the hours to 24-hour format
                        let hour24 = hours;
                        if (ampm === 'PM' && hours !== 12) {
                            hour24 += 12; // Add 12 to convert PM hours (except for 12 PM which stays the same)
                        } else if (ampm === 'AM' && hours === 12) {
                            hour24 = 0; // Convert 12 AM to 00 (midnight)
                        }
                    
                        // Return the formatted date as YYYY-MM-DD HH:MM:SS
                        return `${date[2]}-${date[1]}-${date[0]} ${hour24.toString().padStart(2, '0')}:${minutes}:${seconds}`;
                    }
            });
        </script>
        
        <script src="{{ URL::asset('proofing-assets/js/proofing/configure.js') }}"></script>
        <script src="{{ URL::asset('proofing-assets/vendors/moment/moment.js') }}"></script>
        <script src="{{ URL::asset('proofing-assets/vendors/bootstrap-multiselect-0.9.15/dist/js/bootstrap-multiselect.js') }}"></script>
        <script src="{{ URL::asset('proofing-assets/vendors/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js') }}"></script>

    @stop

@endif