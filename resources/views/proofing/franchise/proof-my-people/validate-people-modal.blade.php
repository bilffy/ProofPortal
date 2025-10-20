@php
    use App\Helpers\Helper;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Str;
    $image_url = asset('proofing-assets/img/subject-image.png');              
@endphp

    @if ($currentFolder->is_edit_portraits)
        <div id="modals">
            @foreach($allSubjects as $subject)
            @php
                $skHash = sha1($subject->ts_subjectkey);
                $skEncrypted = Crypt::encryptString($subject->ts_subjectkey);
                $spellingTemplateID = 0;
                $pictureTemplateID = 0; 
                $wrongclassTemplateID = 0;

                if ($subject->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                    $combined_key = $subject->ts_subjectkey . $selectedJob->ts_jobkey;
                    $encryptImageKey = sprintf("%08x", crc32($combined_key));
                    $hashed_key = hash('sha256', $combined_key);
                    $sub_dirs = [];

                    for ($i = 0; $i < strlen($hashed_key); $i += 5) {
                        $sub_dirs[] = substr($hashed_key, $i, 3);
                    }

                    // Generate the directory structure and filename using DIRECTORY_SEPARATOR
                    $full_path = implode(DIRECTORY_SEPARATOR, $sub_dirs);
                    $imageName = DIRECTORY_SEPARATOR . $full_path . DIRECTORY_SEPARATOR . $encryptImageKey . '.jpg';
                    $newimageName = str_replace('\\', '-', $imageName);
                    $newimageName = Str::replace('\\', '-', $imageName);
                    // Generate a signed URL for the image
                    // $image_url = route('serve.image', ['filename' => $newimageName]);
                    $image_url = route('serve.image', ['filename' => $skEncrypted]);
                }
            @endphp

            <div class="modal fade modal_start" id="{{ $skHash }}_Modal" tabindex="-1" role="dialog" aria-labelledby="{{ $skHash }}Label" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form id="{{ $skHash }}_form" action="">
                        @csrf
                            <input type="hidden" name="subject_key_encrypted" value="{{$skEncrypted}}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="{{ $skHash }}Label">
                                    Make a correction to {!! Helper::wrapSalutationFirstNameLastName($subject->salutation, $subject->firstname, $subject->lastname, $skHash) !!}
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-12">
                                        <img style="max-width: 100%" class="lazyload mx-auto d-block" src="public_path('proofing-assets/img/subject-image.png')" data-src="{{ $image_url }}" alt="Photo-Image">
                                    </div>
                                    <div class="col-md-9 col-sm-12">
                                    <select id="subjects_questions" name="subjects_questions" data-id="{{ $skHash }}" class="form-control is_subject_select">
                                        <option value="">--Please select an issue--</option>
                                        @foreach($subject_questions as $subject_question)
                                            @php
                                                $displayOption = true;

                                                // Check conditions
                                                if ($subject_question->issue_description === $jobTitleSalutation) {
                                                    $displayOption = $currentFolder->is_edit_job_title == 1 && $currentFolder->is_edit_salutation == 1;
                                                } elseif ($subject_question->issue_description === $jobTitle) {
                                                    $displayOption = $currentFolder->is_edit_job_title == 1 && $currentFolder->is_edit_salutation != 1;
                                                } elseif ($subject_question->issue_description === $salutation) {
                                                    $displayOption = $currentFolder->is_edit_job_title != 1 && $currentFolder->is_edit_salutation == 1;
                                                }
                                              
                                                // Assign template IDs based on description
                                                if ($subject_question->issue_description === Config::get('constants.SUBJECT_ISSUE_SPELLING')) {
                                                    $spellingTemplateID = $subject_question->id;
                                                } elseif ($subject_question->issue_description === Config::get('constants.SUBJECT_ISSUE_PICTURE')) {
                                                    $pictureTemplateID = $subject_question->id;
                                                } elseif ($subject_question->issue_description === Config::get('constants.SUBJECT_ISSUE_CLASS')) {
                                                    $wrongclassTemplateID = $subject_question->id;
                                                }

                                                // Replace placeholders with actual names
                                                $description = str_replace(['FIRSTNAME', 'LASTNAME'], [$subject->firstname, $subject->lastname], $subject_question->issue_description);

                                            @endphp
                                            @if ($displayOption)
                                                <option class="{{ $skHash }}-find-replace" value="{{ $subject_question->id }}">
                                                    {{ $description }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                            <div id="inputFieldsContainer_{{ $skHash }}"></div>
                                            <!-- Input field templates -->
                                            <template class="template_{{ $skHash }}" id="inputFieldTemplate{{$spellingTemplateID}}_{{ $skHash }}">
                                                <div id="spelling_{{ $skHash }}" class="mt-2">
                                                    <div id="first_name_{{ $skHash }}">
                                                        <label for="{{ $skHash }}-new_first_name" class="mt-3">First Name</label>
                                                        <input type="text" name="{{ $skHash }}_new_first_name" id="{{ $skHash }}-new_first_name" class="form-control {{ $skHash }}-form-spelling-first-name" value="{{ $subject->firstname }}">
                                                    </div>
                                                    <div id="last_name_{{ $skHash }}">
                                                        <label for="{{ $skHash }}-new_last_name" class="mt-3">Last Name</label>
                                                        <input type="text" name="{{ $skHash }}_new_last_name" id="{{ $skHash }}-new_last_name" class="form-control {{ $skHash }}-form-spelling-last-name" value="{{ $subject->lastname }}">
                                                    </div>
                                                </div>
                                            </template>

                                            <template class="template_{{ $skHash }}" id="inputFieldTemplate{{$pictureTemplateID}}_{{ $skHash }}">
                                                <div id="picture_{{ $skHash }}" class="mt-2">
                                                    <label for="{{ $skHash }}-picture_issue" class="mt-3">Do you know who this is? Type their name below or leave it blank.</label>
                                                    <input type="text" name="{{ $skHash }}_picture_issue" id="{{ $skHash }}-picture_issue" class="form-control" >
                                                </div>
                                            </template>

                                            <template class="template_{{ $skHash }}" id="inputFieldTemplate{{$wrongclassTemplateID}}_{{ $skHash }}">
                                                <div id="folder_{{ $skHash }}" class="mt-2">
                                                    <label for="{{ $skHash }}-folder_issue" class="mt-3">Do you know which Class/Group they belong to? If so, select from below.</label>
                                                    <select name="{{ $skHash }}_folder_issue" id="{{ $skHash }}-folder_issue" class="form-control homedfolders">
                                                        <option value="">I'm not sure</option>
                                                        @foreach ($folderSelections as $folderSelection)
                                                            <option value="{{ $folderSelection->id }}">{{ $folderSelection->ts_foldername }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </template>

                                            <template class="template_{{ $skHash }}" id="inputFieldTemplate_{{ $skHash }}">
                                                <div id="title-salutation_{{ $skHash }}">
                                                    @if ($currentFolder->is_edit_job_title)
                                                        <label for="{{ $skHash }}-new_title" class="mt-3">Job Title</label>
                                                        <input type="text" name="{{ $skHash }}_new_title" id="{{ $skHash }}-new_title" class="form-control {{ $skHash }}-form-spelling-title" value="{{ $subject->title }}">
                                                    @endif
                                                    @if ($currentFolder->is_edit_salutation)
                                                        <label for="{{ $skHash }}-new_salutation" class="mt-3">Salutation</label>
                                                        <input type="text" name="{{ $skHash }}_new_salutation" id="{{ $skHash }}-new_salutation" class="form-control {{ $skHash }}-form-spelling-salutation" value="{{ $subject->salutation }}">
                                                    @endif
                                                </div>
                                            </template>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div id="{{ $skHash }}_acknowledge" class="mr-auto"></div>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" id="{{ $skHash }}_issue_submit" class="btn btn-primary subject_submit">Submit Issue</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Other modals and content -->
        <div class="modal fade" id="HistoryEdits_Modal" tabindex="-1" role="dialog"
            aria-labelledby="HistoryEdits_ModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-90" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="HistoryEditsLabel">
                            Changes made to <span id="history-box-subject-name">Subject ABC</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="history-box-subject-history-table">

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="GridSpellingEdits_Modal" tabindex="-1" role="dialog"
             aria-labelledby="GridSpellingEdits_ModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="GridSpellingEditsLabel">
                            Make Spelling Corrections
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
        
                    <div class="modal-body">
                        <p>
                            Correct the spelling of names in the table below. Changes are automatically saved when you 'tab'
                            away from the cell or click close.
                        </p>
        
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <input class="form-control" id="subject-name-filter" type="text"
                                       name="subject-name-filter"
                                       placeholder="Start typing a Name to filter down table...">
                            </div>
                        </div>
        
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <span id="grid-spelling-acknowledge"></span>&nbsp;
                            </div>
                        </div>
        
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th class="idx-artifact" scope="col">
                                        Photo<br>
                                        <a href="#" class="small people-photos-hide d-inline">(Hide)</a>
                                        <a href="#" class="small people-photos-show d-none">(Show)</a>
                                    </th>
        
                                    @if ($currentFolder->is_edit_salutation)
                                        <th class="idx-salutation" scope="col">Salutation</th>
                                    @endif
        
                                    <th class="idx-first-name" scope="col">First Name</th>
                                    <th class="idx-last-name" scope="col">Last Name</th>
        
                                    @if ($currentFolder->is_edit_job_title)
                                        <th class="idx-job-title" scope="col">Job Title</th>
                                    @endif
        
                                    <th class="idx-last-name" scope="col">Undo</th>
                                </tr>
                            </thead>
        
                            <tbody>
                                @foreach ($allSubjectsByJob as $subject)
                                        @php
                                            $skHash = sha1($subject->ts_subjectkey);
                                            $skEncrypted = Crypt::encryptString($subject->ts_subjectkey);
                                            if ($subject->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                                                $combined_key = $subject->ts_subjectkey . $selectedJob->ts_jobkey;
                                                $encryptImageKey = sprintf("%08x", crc32($combined_key));
                                                $hashed_key = hash('sha256', $combined_key);
                                                $sub_dirs = [];
        
                                                for ($i = 0; $i < strlen($hashed_key); $i += 5) {
                                                    $sub_dirs[] = substr($hashed_key, $i, 3);
                                                }
        
                                                // Generate the directory structure and filename using DIRECTORY_SEPARATOR
                                                $full_path = implode(DIRECTORY_SEPARATOR, $sub_dirs);
                                                $imageName = DIRECTORY_SEPARATOR . $full_path . DIRECTORY_SEPARATOR . $encryptImageKey . '.jpg';
                                                $newimageName = Str::replace('\\', '-', $imageName);
                                                // Generate a signed URL for the image
                                                // $image_url = route('serve.image', ['filename' => $newimageName]);
                                                $image_url = route('serve.image', ['filename' => $skEncrypted]); 
                                                $salutation = !empty($subject['salutation']) ? $subject['salutation'] . '. ' : '';
                                            }
                                        @endphp

                                        <tr class="person-row {{ $skHash }}"
                                            data-subject-name="{{ trim(strtolower($salutation . ($subject->firstname ?? '') . ' ' . ($subject->lastname ?? ''))) }}">

                                            <td class="idx-artifact text-center pt-2 pb-1">
                                                <div class="person-pic-wrapper d-inline">
                                                    <img style="max-height: 100px;" class="lazyload mx-auto d-block" src="public_path('proofing-assets/img/subject-image.png')" data-src="{{ $image_url }}" alt="Subject Image">
                                                </div>
                                            </td>
                                            @if ($currentFolder->is_edit_salutation)
                                                <td class="idx-salutation p-0">
                                                    <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-salutation"
                                                        id="{{ $skHash }}-grid-spelling-salutation"
                                                        name="{{ $skHash }}_grid_spelling_salutation"
                                                        value="{{ $subject->salutation }}"
                                                        data-original-value="{{ $subject->salutation }}"
                                                        data-old-value="{{ $subject->salutation }}"
                                                        data-skhash="{{ $skHash }}"
                                                        data-skencrypted="{{ $skEncrypted }}">
                                                </td>
                                            @endif
                                            <td class="idx-first-name p-0">
                                                <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-first-name"
                                                    id="{{ $skHash }}-grid-spelling-first-name"
                                                    name="{{ $skHash }}_grid_spelling_first_name"
                                                    value="{{ $subject->firstname }}"
                                                    data-original-value="{{ $subject->firstname }}"
                                                    data-old-value="{{ $subject->firstname }}"
                                                    data-skhash="{{ $skHash }}"
                                                    data-skencrypted="{{ $skEncrypted }}">
                                            </td>
                                            <td class="idx-last-name p-0">
                                                <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-last-name"
                                                    id="{{ $skHash }}-grid-spelling-last-name"
                                                    name="{{ $skHash }}_grid_spelling_last_name"
                                                    value="{{ $subject->lastname }}"
                                                    data-original-value="{{ $subject->lastname }}"
                                                    data-old-value="{{ $subject->lastname }}"
                                                    data-skhash="{{ $skHash }}"
                                                    data-skencrypted="{{ $skEncrypted }}">
                                            </td>
                                            @if ($currentFolder->is_edit_job_title)
                                                <td class="idx-job-title p-0">
                                                    <input type="text" class="form-control grid-spelling {{ $skHash }}-grid-spelling-title"
                                                        id="{{ $skHash }}-grid-spelling-title"
                                                        name="{{ $skHash }}_grid_spelling_title"
                                                        value="{{ $subject->title }}"
                                                        data-original-value="{{ $subject->title }}"
                                                        data-old-value="{{ $subject->title }}"
                                                        data-skhash="{{ $skHash }}"
                                                        data-skencrypted="{{ $skEncrypted }}">
                                                </td>
                                            @endif
                                            <td class="idx-last-name p-0 align-middle text-center">
                                                <span class="d-none pl-3 pr-4 pt-2 pb-2" id="{{ $skHash }}-grid-spelling-revert-button"
                                                    data-skhash="{{ $skHash }}" data-skencrypted="{{ $skEncrypted }}">
                                                    <a href="#">
                                                        <i class="fa fa-undo fa-lg text-danger"></i>
                                                    </a>
                                                </span>
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
        
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
    @endif

