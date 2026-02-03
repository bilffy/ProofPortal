@php
    use App\Helpers\Helper;
    use App\Models\ProofingChangelog;
    use Illuminate\Support\Facades\Crypt;
    use App\Models\Status;
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\Str;
    $awaitingApproval = Status::where('status_external_name','Awaiting Approval')->value('id');
@endphp

<fieldset id="ValidateStep2">
                    @if($allSubjects->count() == 0)
                        <div id="subject_continue">
                            <p class="text-center">
                                There are no Subjects in this Class/Group, please continue.
                            </p>
                        </div>
                    @endif
                    <div id="subject_photos">
                        @if ($showLockUnlockIcons)
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mt-0 mb-4 mr-auto ml-auto">
                                        <button id="lock_all_students_button" type="button" class="btn btn-primary">
                                            <i class="fa fa-unlock"></i>&nbsp;Lock All Subjects
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="row" id="subject_thumbnails">
                            @php
                                $formattedSubjectsWithChanges = ProofingChangelog::where([['ts_jobkey', $selectedJob->ts_jobkey]])->get()->groupBy('keyvalue'); // Eager load and key by keyvalue for efficient lookup
                            @endphp
                            @foreach($allSubjects as $subject)
                                @php
                                    $image_url = asset('proofing-assets/img/subject-image.png');
                                    $skHash = sha1($subject->ts_subjectkey);
                                    $skEncrypted = Crypt::encryptString($subject->ts_subjectkey);
                                    // $fullName = Helper::compileFullName($subject->salutation, $subject->firstname, $subject->lastname);
                                    $salutation = '';
                                    $prefix     = '';
                                    $suffix     = '';

                                    if ($currentFolder->show_salutation_portraits) {
                                        $salutation = $subject->salutation;
                                    }

                                    if ($currentFolder->show_prefix_suffix_portraits) {
                                        $prefix = $subject->prefix;
                                        $suffix = $subject->suffix;
                                    }

                                    $firstName = $subject->first_name ?? $subject->firstname;
                                    $lastName  = $subject->last_name  ?? $subject->lastname;

                                    $fullNameWrapped = Helper::wrapSalutationPrefixFirstNameLastNameSuffix(
                                        $salutation,
                                        $prefix,
                                        $firstName,
                                        $lastName,
                                        $suffix,
                                        $skHash
                                    );

                                    $fullNameWrappedText = Helper::wrapSalutationPrefixFirstNameLastNameSuffixAsText(
                                        $salutation,
                                        $prefix,
                                        $firstName,
                                        $lastName,
                                        $suffix
                                    );

                                    $jobTitleWrapped = $currentFolder->is_edit_job_title ? Helper::wrapTitle($subject->title, $skHash) : '';
                                    $subjectKey = $subject->ts_subjectkey;
                                    $subjectChanges = $formattedSubjectsWithChanges->get($subjectKey); 
                                    $hasChanges = !empty($subjectChanges);
                                    if (!$hasChanges) {
                                        $iconColour = 'success';
                                    } else {
                                        $hasawaitingApproval = $subjectChanges->contains('approvalStatus', $awaitingApproval);
                                        $iconColour = $hasawaitingApproval ? 'danger' : 'success';
                                    }

                                    $historyEditsCss = $hasChanges ? 'd-inline-block' : 'd-none';
                                    $location = URL::signedRoute('my-subject-change', ['hash' => $skEncrypted]);

                                    if ($subject->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                                        $image_url = route('serve.image', ['filename' => $skEncrypted, 'jobKey' => Crypt::encryptString($selectedJob->ts_jobkey)]); 
                                    }
                                @endphp
                                <div class="dynamic-subjects col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                                    <div class="subjects validate">
                                        <div class="card">
                                            <div class="card-header lead">
                                                <i class="fa d-inline-flex"></i>
                                                <div id="{{ $skHash }}_history_edits_button" class="{{ $historyEditsCss }}" data-route="{{$location}}">
                                                    <a href="#" data-toggle="modal" data-target="#HistoryEdits_Modal" data-skhash="{{ $skHash }}">
                                                        <i class="fa fa-history fa-lg text-{{ $iconColour }}" data-toggle="tooltip" data-placement="top" title="Click here to view changes"></i>
                                                    </a>
                                                </div>
                                                <div id="{{ $skHash }}_history_edits_name_populate" class="d-none">
                                                    {!! $fullNameWrapped !!}
                                                </div>
                                                <div style="display:none;">
                                                    <span id="{{ $skHash }}_picture"></span>
                                                    <span id="{{ $skHash }}_folder"></span>
                                                </div>
                                                <div class="card-actions">
                                                    @if ($subject->is_locked)
                                                        @if ($showLockUnlockIcons)
                                                            <button id="{{ $skHash }}_lock_button_msg" type="button"
                                                                    class="btn-setting">
                                                                <i id="{{ $skHash }}_lock_icon" class="fa fa-lock"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        @if ($showLockUnlockIcons)
                                                            <button id="{{ $skHash }}_lock_button" type="button"
                                                                    class="btn-setting">
                                                                <i id="{{ $skHash }}_lock_icon" class="fa fa-unlock"></i>
                                                            </button>
                                                        @endif
                                                        <button id="{{ $skHash }}_issue_button" type="button"
                                                                class="btn-setting"
                                                                data-toggle="modal"
                                                                data-target="#{{ $skHash }}_Modal">
                                                            Edit
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="col-12 p-2" style="background-color: #EFEFEF">
                                                    <img style="width:128px;height:170px;" class="lazyload mx-auto d-block" data-src="{{ $image_url }}" alt="Photo-Image">
                                                </div>

                                                {{--                                               
                                                <div class="col-12 p-2" style="background-color: #EFEFEF">
                                                    @if (isset($subjectArtifactListingByTokenAndUrl[sha1($subject->subject_key)]))
                                                        <img src="{{ $subjectArtifactListingByTokenAndUrl[sha1($subject->subject_key)] }}" {{ $imageOptions }}>
                                                    @else
                                                        <img src="{{ $subjectImagePlaceholder->full_url }}" {{ $imageOptions }}>
                                                    @endif
                                                </div>
                                                --}}
                                                <div id="{{ $skHash }}_info" class="mt-3 lead">
                                                    <div id="{{ $skHash }}_full_name" class="text-center">
                                                        <strong>{!! $fullNameWrapped !!}</strong>
                                                    </div>
                                                    <div id="{{ $skHash }}_title" class="text-center">
                                                        <strong>{!! $jobTitleWrapped !!}</strong>
                                                    </div>
                                                    <div id="{{ $skHash }}_school"></div>
                                                    <div id="{{ $skHash }}_folder"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="row" id="subject_questions">
                        <div class="questions col-xs-12 col-sm-12 col-md-8 col-lg-6 col-xl-4 m-auto">
                            @foreach($folder_questions as $folder_question)
                                @php
                                    $latestChange = $formattedFoldersWithChanges
                                        ->where('issue_id', $folder_question->id)
                                        ->sortByDesc('id')
                                        ->first();
                                    
                                    $folderCorrected = Config::get('constants.FOLDER_CORRECTED');
                                    $subjectAccounted = Config::get('constants.SUBJECTS_ACCOUNTED');
                                    $descriptionMatchesFolderCorrected = $latestChange && $latestChange->issue->issue_description === $folderCorrected;
                                    $descriptionMatchessubjectAccounted = $latestChange && $latestChange->issue->issue_description === $subjectAccounted;
                                @endphp
                                <div class="form-group">
                                    <label for="folder_question_{{$folder_question->id}}" class="form-control-label">
                                        {!! str_replace('FOLDER', '<span class="group-name">"' . $className . '"</span>', $folder_question->issue_description) !!}
                                    </label>
                                    <select id="folder_question_{{$folder_question->id}}" name="folder_question_{{$folder_question->id}}" class="form-control is_proceed_select" data-id="{{$folder_question->id}}" data-is-proceed="{{$folder_question->is_proceed_confirm}}" onchange="toggleValidationMessage({{$folder_question->id}})">
                                        <option value="" @if($latestChange){{ !$latestChange->change_to ? 'selected' : '' }}@endif>--Please Select--</option>
                                        <option value="1"  @if($latestChange){{ $latestChange->change_to === "1" ? 'selected' : '' }}@endif>Yes</option>
                                        <option value="0"  @if($latestChange){{ $latestChange->change_to === "0" ? 'selected' : '' }}@endif>No</option>
                                    </select>
                                    @if($descriptionMatchessubjectAccounted)
                                        @if($latestChange->change_to == 0)
                                            @php
                                                $subjectmissing = $formattedFoldersWithChanges->where('issue.issue_description',$subjectMissing)->select('change_to','id')->sortByDesc('id')->first();
                                            @endphp
                                            @if($subjectmissing)
                                                <input type="hidden" name="recorded_subjectmissing" id='recorded_subjectmissing' value="{{$subjectmissing['change_to'] ?? ''}}"> 
                                            @endif
                                        @else
                                            <input type="hidden" name="recorded_subjectmissing" id='recorded_subjectmissing' value="">
                                        @endif
                                    @endif
                                    @if($descriptionMatchesFolderCorrected)
                                        @if($latestChange->change_to == 0)
                                            @php
                                                $generalissue = $formattedFoldersWithChanges->where('issue.issue_description',$generalIssue)->select('change_to','id')->sortByDesc('id')->first();
                                            @endphp
                                            @if($generalissue)
                                                <input type="hidden" name="recorded_pageissue" id='recorded_pageissue' value="{{$generalissue['change_to'] ?? ''}}">
                                            @endif
                                        @else
                                            <input type="hidden" name="recorded_pageissue" id='recorded_pageissue' value="">                 
                                        @endif
                                    @endif
                                    <div id="folder_question_{{$folder_question->id}}_yes"></div>
                                    <div id="folder_question_{{$folder_question->id}}_no" style="display: none;">
                                        {!! $folder_question->issue_error_message !!}
                                    </div>
                                    <input type="hidden" name="is_proceed_{{$folder_question->id}}" value="{{$folder_question->is_proceed_confirm}}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="f1-buttons">
                        <button type="button" id="subjectPrevious" class="btn btn-previous btn-secondary">Previous</button>
                        <button id="subjectNext" type="button" class="btn btn-next btn-primary">Next</button>
                        <button id="subjectNextDisabled" type="button" class="btn btn-next-disabled btn-secondary" onclick="alert('Please answer all questions before proceeding.')">Next</button>
                        <div class="end_of_step_2"></div>
                    </div>
                </fieldset>