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
                    @else
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
                            <div class="row d-flex flex-wrap pb-4" style="gap: 15px; justify-content: flex-start; margin-left: 0; margin-right: 0;" id="subject_thumbnails">
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
                                    <div class="dynamic-subjects" style="width: 186px; flex-shrink: 0;">
                                        <div class="subjects validate">
                                            <div class="portrait-img d-flex flex-column position-relative justify-content-center" style="margin: 0 auto; user-select: none;">
                                                <!-- HIDDEN ELEMENTS FOR JS TO TARGET -->
                                                <div style="display:none;">
                                                    <div id="{{ $skHash }}_history_edits_name_populate" class="d-none">
                                                        {!! $fullNameWrapped !!}
                                                    </div>
                                                    <span id="{{ $skHash }}_picture"></span>
                                                    @if ($subject->is_locked)
                                                        @if ($showLockUnlockIcons)
                                                            <button id="{{ $skHash }}_lock_button_msg" type="button" class="btn-setting">
                                                                <i id="{{ $skHash }}_lock_icon" class="fa fa-lock"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        @if ($showLockUnlockIcons)
                                                            <button id="{{ $skHash }}_lock_button" type="button" class="btn-setting">
                                                                <i id="{{ $skHash }}_lock_icon" class="fa fa-unlock"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>

                                                <!-- IMAGE BLOCK -->
                                                <div class="position-relative overflow-hidden rounded transition-all" style="height: 229px; background-color: #E6E7E8; display: flex; align-items: center; justify-content: center;">
                                                    <img style="object-fit: cover;" class="lazyload mx-auto d-block w-100 h-100 pointer-events-none" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" data-src="{{ $image_url }}" alt="Photo-Image">
                                                    
                                                    <!-- EDIT BUTTON AND HISTORY ICON -->
                                                    <div class="position-absolute d-flex justify-content-between align-items-center w-100" style="top: 0; padding: 10px; z-index: 10;">
                                                        @if (!$subject->is_locked)
                                                            <a href="javascript:void(0)" 
                                                            id="{{ $skHash }}_issue_button" 
                                                            class="text-truncate badge badge-light" 
                                                            data-toggle="modal" 
                                                            data-target="#{{ $skHash }}_Modal" 
                                                            style="text-decoration: none; cursor: pointer; color: #374151; font-size: 11px; opacity: 0.9; padding: 4px 8px;">
                                                                Edit
                                                            </a>
                                                        @else
                                                            <span class="text-truncate badge badge-secondary" style="font-size: 11px; opacity: 0.9; padding: 4px 8px;"><i class="fa fa-lock"></i> Locked</span>
                                                        @endif
                                                        
                                                        <div id="{{ $skHash }}_history_edits_button" class="{{ $historyEditsCss }}" data-route="{{$location}}">
                                                            <a href="#" data-toggle="modal" data-target="#HistoryEdits_Modal" data-skhash="{{ $skHash }}" style="text-decoration: none;">
                                                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 18px; height: 18px; opacity: 0.95;">
                                                                    <i class="fa fa-history text-{{ $iconColour }}" data-toggle="tooltip" data-placement="top" title="Click here to view changes"></i>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- INFO BLOCK -->
                                                <div class="d-flex flex-column" style="font-size: 14px; text-align: left; padding-top: 6px; line-height: 1.35;">
                                                    <div id="{{ $skHash }}_info" class="w-100">
                                                        <div id="{{ $skHash }}_full_name" class="text-truncate" style="width: 100%; font-weight: 600; font-size: 14px; color: #374151;" data-toggle="tooltip" title="{{ strip_tags($fullNameWrappedText) }}">
                                                            {!! $fullNameWrapped !!}
                                                        </div>
                                                        
                                                        @if($currentFolder->is_edit_job_title)
                                                        <div id="{{ $skHash }}_title" class="text-truncate" style="width: 100%; font-size: 13px; color: #6B7280; min-height: 20px;">
                                                            {!! $jobTitleWrapped !!}
                                                        </div>
                                                        @else
                                                        <div id="{{ $skHash }}_title" class="d-none"></div>
                                                        @endif

                                                        <div id="{{ $skHash }}_school" class="d-none"></div>
                                                        <div id="{{ $skHash }}_folder" class="d-none"></div>

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
                                        <select id="folder_question_{{$folder_question->id}}" name="folder_question_{{$folder_question->id}}" class="form-control is_proceed_select" data-id="{{$folder_question->id}}" data-is-proceed="{{$folder_question->is_proceed_confirm}}" data-name="{{$folder_question->issue_name}}" data-description="{{$folder_question->issue_description}}" onchange="toggleValidationMessage({{$folder_question->id}})">
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
                    @endif

                    <div class="f1-buttons">
                        <button type="button" id="subjectPrevious" class="btn btn-previous btn-secondary">Previous</button>
                        <button id="subjectNext" type="button" class="btn btn-next btn-primary">Next</button>
                        @if($allSubjects->count() != 0)
                            <button id="subjectNextDisabled" type="button" class="btn btn-next-disabled btn-secondary" onclick="alert('Please answer all questions before proceeding.')">Next</button>
                        @endif
                        <div class="end_of_step_2"></div>
                    </div>
                </fieldset>