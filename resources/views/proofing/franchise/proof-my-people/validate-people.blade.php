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

            $groupCommentsIssue = $currentFolder->is_edit_groups && isset($group_questions) ? $group_questions->where('issue_name', 'GROUP_COMMENTS')->first() : null;
            $groupComments = $groupCommentsIssue ? $groupCommentsIssue->issue_description : Config::get('constants.GROUP_COMMENTS');
            $groupCommentsNote = Config::get('constants.GROUP_COMMENTS_NOTE');

            $tradPhotoTagged = Config::get('constants.TRADITIONAL_PHOTO_TAGGED');
            $tradPhotoTaggedNote = Config::get('constants.TRADITIONAL_PHOTO_TAGGED_NOTE');
            
            $folderBelong = Config::get('constants.FOLDER_BELONG_SUBJECTS');
            $folderBelongNote = Config::get('constants.FOLDER_BELONG_SUBJECTS_NOTE');

            $folderSpell = Config::get('constants.FOLDER_SPELL_SUBJECT');
            $folderSpellNote = Config::get('constants.FOLDER_SPELL_SUBJECT_NOTE');

            $photoCorrect = Config::get('constants.SUBJECTS_CORRECT_PHOTO');
            $photoCorrectNote = Config::get('constants.SUBJECTS_CORRECT_PHOTO_NOTE');

            $subjectAccounted = Config::get('constants.SUBJECTS_ACCOUNTED');
            $subjectAccountedNote = Config::get('constants.SUBJECTS_ACCOUNTED_NOTE');

            $folderCorrected = Config::get('constants.FOLDER_CORRECTED');
            $folderCorrectedNote = Config::get('constants.FOLDER_CORRECTED_NOTE');

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

            $('#folder_name').on('keyup change', function (event) {
                var newValue = $(this).val();
                var issue = @json($folderName);
                var note = @json($folderNameNote);
                
                $('.group-name').html(newValue);

                if (event.type === 'change') {
                    console.log('Folder name change triggered', {newValue: newValue});
                    sendFolderChanges(location, issue, newValue, note);
                }
            });

            $('#subject_missing_names').on('keyup change', function (event) {
                var newValue = $(this).val();
                var issue = @json($subjectMissing);
                var note = @json($subjectMissingNote);
                
                if (event.type === 'change') {
                    console.log('Subject missing names change triggered', {newValue: newValue});
                    var previousValue = $('#recorded_subjectmissing').val();
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#recorded_subjectmissing').val(newValue);
                    }
                }
            });
            
            $('#group_comments').on('keyup change', function (event) {
                var newValue = $(this).val();
                var issue_id = $(this).data('id');
                var issue = @json($groupComments);
                var note = @json($groupCommentsNote);
                
                if (event.type === 'change') {
                    console.log('Group comment change triggered', {issue: issue, newValue: newValue});
                    var previousValue = $('#groupPreviousValue_' + issue_id).val();
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#groupPreviousValue_' + issue_id).val(newValue);
                    }
                }
            });

            $('#subject_general_issue_text').on('keyup change', function (event) {
                var newValue = $(this).val();
                var issue = @json($generalIssue);
                var note = @json($generalIssueNote);
                
                if (event.type === 'change') {
                    console.log('General issue change triggered', {newValue: newValue});
                    var previousValue = $('#recorded_pageissue').val();
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#recorded_pageissue').val(newValue);
                    }
                }
            });

            $('#teacher_name').on('keyup change', function (event) {
                var newValue = $(this).val();
                var previousValue = $('#recorded_teachername').val();
                var issue = @json($teacher);
                var note = previousValue ? @json($teacherExistNote) : @json($teacherNote);

                if (event.type === 'change') {
                    console.log('Teacher name change triggered', {newValue: newValue});
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#recorded_teachername').val(newValue);
                    }
                }
            });
            
            $('#principal_name').on('keyup change', function (event) {
                var newValue = $(this).val();
                var previousValue = $('#recorded_principalname').val();
                var issue = @json($principal);
                var note = previousValue ? @json($principalExistNote) : @json($principalNote);

                if (event.type === 'change') {
                    console.log('Principal name change triggered', {newValue: newValue});
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#recorded_principalname').val(newValue);
                    }
                }
            });
            
            $('#deputy_name').on('keyup change', function (event) {
                var newValue = $(this).val();
                var previousValue = $('#recorded_deputyname').val();
                var issue = @json($deputy);
                var note = previousValue ? @json($deputyExistNote) : @json($deputyNote);

                if (event.type === 'change') {
                    console.log('Deputy name change triggered', {newValue: newValue});
                    if (newValue !== previousValue) {
                        console.log('Sending change to server...');
                        sendFolderChanges(location, issue, newValue, note);
                        $('#recorded_deputyname').val(newValue);
                    }
                }
            });

            $('.is_group_select').change(function () {
                var issue_id = $(this).data('id');
                var issueName = $(this).data('name');
                var issueDescription = $(this).data('description');
                var newValue = $(this).val();

                console.log('Group select changed:', {id: issue_id, name: issueName, desc: issueDescription, val: newValue});

                // UI Toggle Logic
                if (newValue === '0') {
                    $('#groupNext').addClass("d-none");
                    $('#groupNextDisabled').removeClass("d-none");
                    $('#trad_photo_named_no_'+issue_id).show();
                } else {
                    $('#groupNext').removeClass("d-none");
                    $('#groupNextDisabled').addClass("d-none");
                    $('#trad_photo_named_no_'+issue_id).hide();
                }

                // Value Change check
                var previousValue = $('#groupPreviousValue_' + issue_id).val();
                if (newValue !== previousValue) {
                    var issue = issueDescription || issueName;
                    var note = '';

                    if (issueName === 'TRADITIONAL_PHOTO_TAGGED' || issueName === 'GROUP_NAMED' || issueDescription === @json($tradPhotoTagged)) {
                        issue = @json($tradPhotoTagged);
                        note = @json($tradPhotoTaggedNote);
                    }

                    if (issue) {
                        console.log('Sending group select change...', {issue: issue, newValue: newValue, note: note});
                        sendFolderChanges(location, issue, newValue, note);
                        $('#groupPreviousValue_' + issue_id).val(newValue);
                    }
                }
            });

            // Event listener for change on select elements with class 'is_proceed_select'
            $('.is_proceed_select').change(function () {
                var issue_id = $(this).data('id');
                var issueName = $(this).data('name');
                var issueDescription = $(this).data('description');
                var newValue = $(this).val();

                console.log('Proceed select changed:', {id: issue_id, name: issueName, desc: issueDescription, val: newValue});

                // Value Change check
                var previousValue = $(this).data('previousValue');
                if (newValue !== previousValue) {
                    var issue = issueDescription || issueName;
                    var note = '';

                    switch(issueName) {
                        case 'FOLDER_BELONG_SUBJECTS':
                            issue = @json($folderBelong);
                            note = @json($folderBelongNote);
                            break;
                        case 'FOLDER_SPELL_SUBJECT':
                            issue = @json($folderSpell);
                            note = @json($folderSpellNote);
                            break;
                        case 'SUBJECTS_CORRECT_PHOTO':
                            issue = @json($photoCorrect);
                            note = @json($photoCorrectNote);
                            break;
                        case 'SUBJECTS_ACCOUNTED':
                            issue = @json($subjectAccounted);
                            note = @json($subjectAccountedNote);
                            break;
                        case 'FOLDER_CORRECTED':
                            issue = @json($folderCorrected);
                            note = @json($folderCorrectedNote);
                            break;
                    }
                    
                    // Fallback by description match
                    if (note === '') {
                        if (issueDescription === @json($folderBelong)) { issue = @json($folderBelong); note = @json($folderBelongNote); }
                        else if (issueDescription === @json($folderSpell)) { issue = @json($folderSpell); note = @json($folderSpellNote); }
                        else if (issueDescription === @json($photoCorrect)) { issue = @json($photoCorrect); note = @json($photoCorrectNote); }
                        else if (issueDescription === @json($subjectAccounted)) { issue = @json($subjectAccounted); note = @json($subjectAccountedNote); }
                        else if (issueDescription === @json($folderCorrected)) { issue = @json($folderCorrected); note = @json($folderCorrectedNote); }
                    }

                    if (issue) {
                        console.log('Sending folder select change...', {issue: issue, newValue: newValue, note: note});
                        sendFolderChanges(location, issue, newValue, note);
                        $(this).data('previousValue', newValue);
                    }
                }
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
