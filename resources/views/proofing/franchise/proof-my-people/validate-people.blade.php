@extends('proofing.layouts.master')
@section('title', 'Proof People')

@section('css')
    <link href="{{ asset('proofing-assets/css/wizard-style.css') }}" rel="stylesheet">
    <link href="{{ asset('proofing-assets/vendors/jquery-ui-1.12.1/jquery-ui.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />
    <link href="{{ URL::asset('proofing-assets/plugins/tagsinput/bootstrap-tagsinput.css')}}" rel="stylesheet" />

    <style>
        .click-box-wrapper {
            position: relative;
            width: 100%;
        }

        .slate-board {
            padding: 20px;
            border: 2px solid #b2ebf2;
        }

        .click-box {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            border: 2px solid #ff37f3;
            background-color: transparent;
            pointer-events: none;
        }

        .group-image-zoom-pz-holder {
            overflow: hidden;
            border: 1px solid lightgray;
        }

        .tt-menu {
            background-color: white; /* Set the background color to white */
            border: 1px solid #ccc;
            width: 100%;
            flex: 1; /* This makes the input take up all available space within the container */
        }

        .tt-suggestion {
            padding: 10px;
            cursor: pointer;
            width: auto;
        }

        .tt-suggestion:hover {
            background-color: #0074e8; /* Set the hover background color */
            color: white;
        }
        /* Sorting tag names */
        .sortable-placeholder {
            display: inline-block;
            height: auto; /* Matches tag height */
            min-width: 50px; /* Ensures visible placeholder */
            background-color: #f0f0f0;
            border: 1px dashed #ccc;
            margin: 2px;
        }

        .bootstrap-tagsinput span.tag {
            cursor: move; /* Add cursor indicator for drag */
        }
        
        .twitter-typeahead {
            width: 100% !important;
            flex: 1 !important;
        }
    </style>
@stop

@section('content')

        @php
            use App\Helpers\Helper;
            use Illuminate\Support\Facades\Crypt;
            use App\Models\ProofingChangelog;
            use Illuminate\Support\Facades\URL;

            $showLockUnlockIcons = false;
            $hash = Crypt::encryptString($selectedJob->ts_jobkey);
            $folderhash = Crypt::encryptString($currentFolder->ts_folderkey);
            $location = URL::signedRoute('proofing-change-log', ['hash' => $folderhash]);
            $backlocation = URL::signedRoute('my-folders-list', ['hash' => $hash]);

            $labelClass = 'col-md-3 form-control-label';
            $className = $currentFolder->ts_foldername;
            $artifactNameCrypt = '';
            
            // Constants from config
            $jobTitleSalutation = Config::get('constants.SUBJECT_ISSUE_JOBTITLE_SALUTATION');
            $jobTitle = Config::get('constants.SUBJECT_ISSUE_JOBTITLE');
            $jobSalutation = Config::get('constants.SUBJECT_ISSUE_SALUTATION');
            $folderName = Config::get('constants.FOLDER_NAME_CHANGE');
            $folderNameNote = Config::get('constants.FOLDER_NAME_CHANGE_NOTE');
            $subjectMissing = Config::get('constants.SUBJECT_MISSING_NAMES');
            $subjectMissingNote = Config::get('constants.SUBJECT_MISSING_NAMES_NOTE');
            $generalIssue = Config::get('constants.GENERAL_ISSUES');
            $generalIssueNote = Config::get('constants.GENERAL_ISSUES_NOTE');

            $teacher = Config::get('constants.TEACHER');
            $teacherExistNote = Config::get('constants.TEACHER_EXIST_NOTE');
            $teacherNote = Config::get('constants.TEACHER_NOTE');

            $deputy = Config::get('constants.DEPUTY');
            $deputyExistNote = Config::get('constants.DEPUTY_EXIST_NOTE');
            $deputyNote = Config::get('constants.DEPUTY_NOTE');

            $principal = Config::get('constants.PRINCIPAL');
            $principalExistNote = Config::get('constants.PRINCIPAL_EXIST_NOTE');
            $principalNote = Config::get('constants.PRINCIPAL_NOTE');

            $subjectNames = [];
            $useSalutation = $currentFolder->show_salutation_groups;
            $usePrefixSuffix = $currentFolder->show_prefix_suffix_groups;

            foreach ($allSubjectsByJob as $subject) {
                $parts = [];

                if ($useSalutation) $parts[] = trim($subject->salutation ?? '');
                if ($usePrefixSuffix && !empty($subject->prefix)) $parts[] = trim($subject->prefix);

                $parts[] = trim($subject->firstname ?? '');
                $parts[] = trim($subject->lastname ?? '');

                if ($usePrefixSuffix && !empty($subject->suffix)) $parts[] = trim($subject->suffix);

                $subjectNames[] = implode(' ', array_filter($parts));
            }

            if(isset($currentFolder->images)){
                $artifactNameCrypt = Crypt::encryptString($currentFolder->images->name);
            }
        @endphp

    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        <div class="row">
            <div class="col-lg-12 form-box">

                <form class="f1" id="validateFolder" method="POST" action="">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <h1 class="page-header">
                                @if (isset($currentFolder))
                                    Proof Class/Group <span class="group-name"> "{!! $className !!}" </span>
                                @else
                                    Proof
                                @endif
                            </h1>
                        </div>
                    </div>

                    <div class="f1-steps">
                        <div class="f1-progress">
                            <div class="f1-progress-line" data-now-value="12" data-number-of-steps="4" style="width: 12%;"></div>
                        </div>
                        <div id="step-1-progress" class="f1-step active">
                            <div class="f1-step-icon"><i class="fa fa-pencil"></i></div>
                            <p>Class Details</p>
                        </div>
                        @if ($currentFolder->is_edit_portraits)
                            <div id="step-2-progress" class="f1-step">
                                <div class="f1-step-icon"><i class="fa fa-user"></i></div>
                                <p>People Details</p>
                            </div>
                        @endif
                        @if ($currentFolder->is_edit_groups)
                            <div id="step-3-progress" class="f1-step">
                                <div class="f1-step-icon"><i class="fa fa-image"></i></div>
                                <p>Group Details</p>
                            </div>
                        @endif
                        <div id="step-4-progress" class="f1-step">
                            <div class="f1-step-icon"><i class="fa fa-check"></i></div>
                            <p>Confirm Details</p>
                        </div>
                    </div>

                    @include('proofing.franchise.proof-my-people.validate-people-tab1', ['className' => $className, 'backlocation' => $backlocation])

                    @if ($currentFolder->is_edit_portraits)
                        @include('proofing.franchise.proof-my-people.validate-people-tab2', ['subjectMissing' => $subjectMissing,'generalIssue' => $generalIssue,'allSubjects' => $allSubjects, 'showLockUnlockIcons' => $showLockUnlockIcons, 'currentFolder' => $currentFolder, 'folder_questions' => $folder_questions, 'className' => $className])
                    @endif

                    @if ($currentFolder->is_edit_groups)
                        @include('proofing.franchise.proof-my-people.validate-people-tab3', ['className' => $className, 'group_questions' => $group_questions, 'currentFolder' => $currentFolder, 'groupDetails' => $groupDetails, 'subjectNames' => $subjectNames, 'folderhash' => $folderhash, 'hash' => $hash, 'artifact' => $currentFolder->images, 'artifactNameCrypt' => $artifactNameCrypt])
                    @endif
                    
                    @include('proofing.franchise.proof-my-people.validate-people-tab4', ['className' => $className, 'folderhash' => $folderhash])

                </form>
            </div>
        </div>

        @include('proofing.franchise.proof-my-people.validate-people-modal', ['allSubjects' => $allSubjects, 'currentFolder' => $currentFolder])
    
    @else
                
        @include('proofing.franchise.flash-error')
        
    @endif
@endsection

@section('js')
    <script>
        window.GridConfig = {
            subjectsGridUrl: "{{ route('subjects.grid') }}",
            encryptedJob: "{{ Crypt::encryptString($currentFolder->job->ts_job_id) }}",
            encryptedFolder: "{{ Crypt::encryptString($currentFolder->ts_folderkey) }}"
        };
        document.addEventListener("DOMContentLoaded", function() {
            // Creating a JavaScript array from the Blade data
            var folderQuestions = @json($folder_questions->pluck('id'));            
            var groupQuestions = @json($group_questions->pluck('id'));
            
            // Initialize the toggle function for each question
            folderQuestions.forEach(function(issue_id) {
                $('#folder_question_' + issue_id).data('previousValue', $('#folder_question_' + issue_id).val());
                toggleValidationMessage(issue_id);
            });

            groupQuestions.forEach(function(id) {
                var selectgroupElement = document.getElementById('trad_photo_named_'+id);
                if(selectgroupElement){
                    if (selectgroupElement.value == '1') {
                        $('#groupNext').removeClass("d-none");
                        $('#groupNextDisabled').addClass("d-none");
                        $('#trad_photo_named_no_'+id).hide();
                    } else if (selectgroupElement.value == '0') {
                        $('#groupNext').addClass("d-none");
                        $('#groupNextDisabled').removeClass("d-none");
                        $('#trad_photo_named_no_'+id).show();
                    } else{
                        $('#groupNext').addClass("d-none");
                        $('#groupNextDisabled').removeClass("d-none");
                        $('#trad_photo_named_no_'+id).hide();
                    }
                }
            });
        });

        //Update Class name on change
        $(document).ready(function () {
            jQuery.noConflict();
            var location = @json($location);

            $('#folder_name').on('keyup change', function () {
                //var classNameCurrent = @json($className);
                var issue = @json($folderName);
                var note = @json($folderNameNote);
                var classNameNew = $(this).val();

                $('.group-name').html(classNameNew);

                if (event.type === 'change') {
                    sendFolderChanges(location, issue, classNameNew, note);
                    classNameCurrent = classNameNew; // Update current class name after changes are sent
                }
            });

            $('#subject_missing_names').on('keyup change', function () {
                var subjectMissingName = $(this).val();
                //var subjectMissingNameCurrent = $('#recorded_subjectmissing').val();
                var issue = @json($subjectMissing);
                var note = @json($subjectMissingNote);
                if (event.type === 'change') {
                    sendFolderChanges(location, issue, subjectMissingName, note);
                }
            });
            
            const groupCommentTextArea = document.getElementById("group_comments");

            groupCommentTextArea.addEventListener('blur', function() {
                var issue_id = $(this).data('id');
                var newValue = $(this).val();
                var currentValue = $('#groupPreviousValue_'+issue_id).val();
                $.ajax({
                    dataType: 'json',
                    type: "GET",
                    url: base_url + "/franchise/proofing-description/" + issue_id, 
                    async: true,
                    cache: false,
                    success: function (response) {
                        // Retrieve constants from the server
                        $.get(base_url + '/constants', function(constants) {
                            // Function to find constant name by value
                            function findConstantName(value) {
                                for (const [key, val] of Object.entries(constants)) {
                                    if (val === value) {
                                        return key;
                                    }
                                }
                                return null;
                            }
                            // Function to get constant value by name
                            function getConstantValue(name) {
                                return constants[name];
                            }
                            // Extract description and corresponding constant names

                            const description = response.issue_description;
                            const constantNameDescription = findConstantName(description);
                            const constantNameDescriptionNote = constantNameDescription + '_NOTE';
                            const constantNameDescriptionNoteData = getConstantValue(constantNameDescriptionNote);

                            // Initialize issue and note variables
                            var issue = description || '';
                            var note = constantNameDescriptionNoteData || '';

                            // Call function to handle folder changes with the updated issue and note
                            sendFolderChanges(location, issue, newValue, note);
                        });
                    },
                    error: function (e) {
                        console.log("An error occurred: " + e.responseText.message);
                        return false;
                    },
                    complete: function (xhr) {
                        // Do something on complete if necessary
                    }
                });
            });

            $('#subject_general_issue_text').on('keyup change', function () {
                var generalIssue = $(this).val();
                //var generalIssueCurrent = $('#recorded_pageissue').val();
                var issue = @json($generalIssue);
                var note = @json($generalIssueNote);
                if (event.type === 'change') {
                    sendFolderChanges(location, issue, generalIssue, note);
                }
            });

            $('#teacher_name').on('keyup change', function () {
                var newValue = $(this).val();
                var previousValue = $('#recorded_teachername').val();
                var issue = @json($teacher);
                if(previousValue){
                    var note = @json($teacherExistNote);
                }else{
                    var note = @json($teacherNote);
                }
                if (event.type === 'change') {
                    sendFolderChanges(location, issue, newValue, note);
                }
            });

            
            $('#principal_name').on('keyup change', function () {
                var newValue = $(this).val();
                var previousValue = $('#recorded_principalname').val();
                var issue = @json($principal);
                if(previousValue){
                    var note = @json($principalExistNote);
                }else{
                    var note = @json($principalNote);
                }
                if (event.type === 'change') {
                    sendFolderChanges(location, issue, newValue, note);
                }
            });

            
            $('#deputy_name').on('keyup change', function () {
                var newValue = $(this).val();
                var previousValue = $('#recorded_deputyname').val();
                var issue = @json($deputy);
                if(previousValue){
                    var note = @json($deputyExistNote);
                }else{
                    var note = @json($deputyNote);
                }
                if (event.type === 'change') {
                    sendFolderChanges(location, issue, newValue, note);
                }
            });

            $('.is_group_select').change(function () {
                var issue_id = $(this).data('id');
                if ($(this).val() == '0') {
                    $('#groupNext').addClass("d-none");
                    $('#groupNextDisabled').removeClass("d-none");
                    $('#trad_photo_named_no_'+issue_id).show();
                } else {
                    $('#groupNext').removeClass("d-none");
                    $('#groupNextDisabled').addClass("d-none");
                    $('#trad_photo_named_no_'+issue_id).hide();
                }
                var newValue = $(this).val();
                var currentValue = $('#groupPreviousValue_'+issue_id).val();
                // Perform AJAX request to get the proofing description
                $.ajax({
                    dataType: 'json',
                    type: "GET",
                    url: base_url + "/franchise/proofing-description/" + issue_id, 
                    async: true,
                    cache: false,
                    success: function (response) {
                        // Retrieve constants from the server
                        $.get(base_url + '/constants', function(constants) {
                            // Function to find constant name by value
                            function findConstantName(value) {
                                for (const [key, val] of Object.entries(constants)) {
                                    if (val === value) {
                                        return key;
                                    }
                                }
                                return null;
                            }
                            // Function to get constant value by name
                            function getConstantValue(name) {
                                return constants[name];
                            }
                            // Extract description and corresponding constant names

                            const description = response.issue_description;
                            const constantNameDescription = findConstantName(description);
                            const constantNameDescriptionNote = constantNameDescription + '_NOTE';
                            const constantNameDescriptionNoteData = getConstantValue(constantNameDescriptionNote);

                            // Initialize issue and note variables
                            var issue = description || '';
                            var note = constantNameDescriptionNoteData || '';

                            // Call function to handle folder changes with the updated issue and note
                            sendFolderChanges(location, issue, newValue, note);
                        });
                    },
                    error: function (e) {
                        console.log("An error occurred: " + e.responseText.message);
                        return false;
                    },
                    complete: function (xhr) {
                        // Do something on complete if necessary
                    }
                });
            });

            // Event listener for change on select elements with class 'is_proceed_select'
            $('.is_proceed_select').change(function () {
                // Get the previous value and the proofing description id from data attributes
                var currentValue = $(this).data('previousValue'); 
                var proofing_description_id = $(this).data('id');

                // Perform AJAX request to get the proofing description
                $.ajax({
                    dataType: 'json',
                    type: "GET",
                    url: base_url + "/franchise/proofing-description/" + proofing_description_id, 
                    async: true,
                    cache: false,
                    success: function (response) {
                        // Retrieve constants from the server
                        $.get(base_url + '/constants', function(constants) {
                            // Function to find constant name by value
                            function findConstantName(value) {
                                for (const [key, val] of Object.entries(constants)) {
                                    if (val === value) {
                                        return key;
                                    }
                                }
                                return null;
                            }
                            // Function to get constant value by name
                            function getConstantValue(name) {
                                return constants[name];
                            }
                            // Extract description and corresponding constant names

                            const description = response.issue_description;
                            const constantNameDescription = findConstantName(description);
                            const constantNameDescriptionNote = constantNameDescription + '_NOTE';
                            const constantNameDescriptionNoteData = getConstantValue(constantNameDescriptionNote);

                            // Initialize issue and note variables
                            var issue = description || '';
                            var note = constantNameDescriptionNoteData || '';

                            // Call function to handle folder changes with the updated issue and note
                            sendFolderChanges(location, issue, newValue, note);
                        });
                    },
                    error: function (e) {
                        console.log("An error occurred: " + e.responseText.message);
                        return false;
                    },
                    complete: function (xhr) {
                        // Do something on complete if necessary
                    }
                });

                // Get the new value of the select element
                var newValue = $(this).find('option:selected').val();
                // Update the 'previousValue' data attribute
                $(this).data('previousValue', newValue);
            });
        });
    </script>

<script src="{{ URL::asset('proofing-assets/js/proofing/ajaxcontrol.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/js/wizard-scripts.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/js/views/typeahead.bundle.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/js/views/bootstrap-tagsinput.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/js/drag_drop_jquery-ui.min.js') }}"></script>

@stop
