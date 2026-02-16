
<fieldset id="ValidateStep3">
    @if(isset($artifact) && isset($artifact->name))
        @php
            $scaledImageUrl = $image_url = URL::asset('proofing-assets/img/440x290.png');
            $width = $height = 0;
            $artifactNameCrypt = (isset($artifact) && isset($artifact->name)) ? Crypt::encryptString($artifact->name) : '';

            if(isset($artifact) && isset($artifact->name)) {
                $imagePath = storage_path('app/public/groupImages/'.$artifact->name);
                if (file_exists($imagePath)) {
                    $scaledImageUrl = route('image.show', $artifactNameCrypt);
                    list($width, $height) = getimagesize($imagePath);
                }
            }
            
        @endphp
        <div class="row" id="group_thumbnail">
            <div class="questions col-xs-12 col-sm-12 col-md-12 col-lg-10 col-xl-8 m-auto">
                <div class="slate-board p-4 mb-4">
                    <div class="row">
                        <div class="group-image-holder col-12 ml-auto mr-auto">
                            <div class="click-box-wrapper">
                                @if(file_exists(storage_path('app/public/groupImages/'.$artifact->name ?? '')))
                                    <img src="{{ $scaledImageUrl }}"
                                        class="mx-auto d-block group-image"
                                        style="max-width: 100%;"
                                        data-native-width="{{ $width }}"
                                        data-native-height="{{ $height }}"
                                        data-artifact-name="{{ $artifactNameCrypt }}">
                                @endif
                                <div class="click-box d-none"></div>
                            </div>
                        </div>
                  
                        <div class="group-image-zoom-holder col-6 ml-auto d-none">
                            <img src="{{ $image_url }}"
                                class="mx-auto d-block group-image-zoom-placeholder"
                                style="max-width: 100%;" id="group-image-zoom-placeholder">
                        </div>

                        <div class="group-image-zoom-pz-holder col-12 m-auto p-0 d-none"
                            style="width: 100%; height: 400px;">
                            <img id="group-image" src="{{ $scaledImageUrl }}"
                                class="mx-auto d-block group-image-zoom-pz"
                                style="max-width: 100%;">
                        </div>
                    </div>

                    <div class="group-image-zoom-instructions row d-none">
                        <div class="col-6 mr-auto text-center">
                            Click on a student to zoom.
                        </div>
                        <div class="col-6 ml-auto text-center">
                            Student displays here.
                        </div>
                    </div>

                    <div class="group-image-zoom-pz-instructions row d-none">
                        <div class="col-12 m-auto text-center">
                            <a href="#" class="group-image-zoom-pz-reset">Reset Pan & Zoom</a>
                            Scroll wheel to zoom in and out. Click and drag to move image.
                        </div>
                    </div>

                    <div class="row mt-0">
                        <div class="col-12">
                            <a href="#" class="group-image-zoom z12" data-zoom-level="12">Full Size | </a>

                            <a href="#" class="group-image-zoom-click">Click Zoom</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($currentFolder->is_subject_list_allowed && !empty($groupDetails))
            <div class="row" id="group_questions_1">
                <div class="questions col-xs-12 col-sm-12 col-md-12 col-lg-10 col-xl-8 m-auto">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="slate-board p-4 mb-4">
                                @php
                                    $groupRowArray = $groupDetails['groupDetails'];
                                    $groupCount = count($groupRowArray);
                                    unset($groupRowArray['Absent']);
                                    $groupCountExceptAbsent = count($groupRowArray);
                                    $rowNumber = 0;
                                @endphp

                                @if($groupCountExceptAbsent > 0)
                                    @foreach($groupDetails['groupDetails'] as $key => $groupDetail)
                                        @php 
                                            $isAbsentList = false;
                                            if (is_string($groupDetail) && stripos($groupDetail, "Absent") !== false && trim($groupDetail) === '') {
                                                $rowLabel = 'Absent List';
                                                $isAbsentList = true;
                                                $row = $rowNumber;
                                                $names = implode(', ', $groupDetail);
                                            }
                                            $rowNumber++;
                                        @endphp

                                        @if ($isAbsentList)
                                            <div class="form-group row-label tagsSection mb-2" data-row-number="{{ $row }}">
                                                <label for="tags_{{ $row }}">{{ $rowLabel }}</label>
                                                <input type="text" class="form-control tagsinput" style="display:none;" data-role="tagsinput" id="tags_{{ $row }}" name="tags[]" data-key="" value="{{ $names }}" placeholder="Add a Name" autocomplete="off">
                                            </div>
                                        @else
                                            @php
                                            
                                                if($groupCountExceptAbsent < 2){
                                                    if($key === 'Row_0'){
                                                        $rowLabel = 'Back Row';
                                                    } elseif($key === 'Absent'){
                                                        $rowLabel = 'Absent List';
                                                    }
                                                } elseif($groupCountExceptAbsent === 2){
                                                    if($key === 'Row_0'){
                                                        $rowLabel = 'Back Row';
                                                    } elseif($key === 'Row_1'){
                                                        $rowLabel = 'Front Row';
                                                    } elseif($key === 'Absent'){
                                                        $rowLabel = 'Absent List';
                                                    }
                                                } elseif($groupCountExceptAbsent > 2){
                                                    if($key === 'Row_0'){
                                                        $rowLabel = 'Back Row';
                                                    } elseif($key === 'Row_'.($groupCountExceptAbsent-1)){
                                                        $rowLabel = 'Front Row';
                                                    } elseif($key !== 'Absent'){
                                                        $rowLabel = 'Middle Row';
                                                    } elseif($key === 'Absent'){
                                                        $rowLabel = 'Absent List';
                                                    }
                                                }
                                                $names = implode(', ', $groupDetail);

                                            @endphp
                                            <div class="form-group row-label tagsSection" data-row-number="{{ $rowNumber }}">
                                                <label for="tags_{{ $rowNumber }}">{{ $rowLabel }}</label>
                                                <input type="text" class="form-control tagsinput" style="display:none;" data-role="tagsinput" id="tags_{{ $rowNumber }}" name="tags[]" value="{{ $names }}" placeholder="Add a Name" class="typeahead" data-provide="typeahead" autocomplete="off">
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="form-group row-label tagsSection" data-row-number="0">
                                        <label for="tags_0">Back Row</label>
                                        <input type="text" class="form-control tagsinput" style="display:none;" data-role="tagsinput" id="tags_0" name="tags[]" value="" placeholder="Add a Name" class="typeahead" data-provide="typeahead" autocomplete="off">
                                    </div>
                                    <div class="form-group row-label tagsSection" data-row-number="1">
                                        <label for="tags_1">Absent List</label>
                                        <input type="text" class="form-control tagsinput" style="display:none;" data-role="tagsinput" id="tags_1" name="tags[]" value="" placeholder="Add a Name" class="typeahead" data-provide="typeahead" autocomplete="off">
                                    </div>
                                @endif
                                <button class="add_row_button btn btn-secondary">Add Row</button>
                                    <p class="mt-3 mb-0"><strong>Spelling Mistake? Click
                                            <a href="#" data-toggle="modal" data-target="{{ $currentFolder->is_edit_portraits ? '#GridSpellingEdits_Modal' : '#NotEnablePortrait_Modal' }}">here</a>
                                            to correct spelling.</strong>
                                    </p>
                                <input type="hidden" value="{{$folderhash}}" id="folderHash" name="folderHash">
                                <input type="hidden" value="{{$hash}}" id="jobHash" name="jobHash">
                                <input type="hidden" value="{{$groupCount}}" name="groupCount">
                                <input type="hidden" value="{{json_encode($subjectNames)}}" data-key="{{$groupDetails['groupValue']}}" name="allSubjects" id="allSubjectNames">
                                <input type="hidden" value="{{$currentFolder->is_edit_portraits}}" id="isPortrait">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="row" id="group_questions_2">
        <div class="questions col-xs-12 col-sm-12 col-md-12 col-lg-10 col-xl-8 m-auto">
            <div class="slate-board p-4 mb-4">
                @if($currentFolder->is_edit_teacher)
                    <div class="form-group">
                        <label for="teacher-name">Teacher</label>
                        <div class="form-group row text">
                            <div class="col-md-12">
                                <input type="text" name="teacher_name" id="teacher_name" class="form-control" select="form-control" value="{{$currentFolder->teacher}}">
                                <input type="hidden" name="recorded_teachername" id='recorded_teachername' value="{{$currentFolder->teacher}}">
                            </div>
                        </div>
                    </div>
                @endif
                @if($currentFolder->is_edit_principal)
                    <div class="form-group">
                        <label for="principal-name">Principal</label>
                        <div class="form-group row text">
                            <div class="col-md-12">
                                <input type="text" name="principal_name" id="principal_name" class="form-control" select="form-control" value="{{$currentFolder->principal}}">
                                <input type="hidden" name="recorded_principalname" id='recorded_principalname' value="{{$currentFolder->principal}}">
                            </div>
                        </div>
                    </div>
                @endif
                @if($currentFolder->is_edit_deputy)
                    <div class="form-group">
                        <label for="deputy-name">Deputy/Assistant Principal</label>
                        <div class="form-group row text">
                            <div class="col-md-12">
                                <input type="text" name="deputy_name" id="deputy_name" class="form-control" select="form-control" value="{{$currentFolder->deputy}}">
                                <input type="hidden" name="recorded_deputyname" id='recorded_deputyname' value="{{$currentFolder->deputy}}">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row" id="group_questions">
        <div class="questions col-xs-12 col-sm-12 col-md-8 col-lg-6 col-xl-4 m-auto">
            @foreach($group_questions as $group_question)
            @php
                $groupChange = $formattedFoldersWithChanges
                            ->where('issue_id', $group_question->id)
                            ->sortByDesc('id')
                            ->first();
            @endphp
            <div class="form-group question-trad-photo-named">
                <!-- traditional photo named -->
                <label for="trad_photo_named_{{$group_question->id}}">{!! $group_question->issue_description !!}</label>
                <input type="hidden" id="groupPreviousValue_{{$group_question->id}}" name="groupPreviousValue_{{$group_question->id}}" @if($groupChange) value="{{$groupChange->change_to}}" @else value="" @endif>
                @if ($group_question->issue_name === 'GROUP_COMMENTS')
                    <textarea class="form-control" name="group_comments" id="group_comments" data-id="{{$group_question->id}}" rows="5">{!! $groupChange->change_to ?? '' !!}</textarea>
                @else
                    <select name="trad_photo_named_{{$group_question->id}}" id="trad_photo_named_{{$group_question->id}}" data-id="{{$group_question->id}}" data-name="{{$group_question->issue_name}}" data-description="{{$group_question->issue_description}}" class="form-control is_group_select" select="form-control">
                        <option value="" @if($groupChange){{ !$groupChange->change_to ? 'selected' : '' }}@endif>--Please Select--</option>
                        <option value="1"  @if($groupChange){{ $groupChange->change_to === "1" ? 'selected' : '' }}@endif>Yes</option>
                        <option value="0"  @if($groupChange){{ $groupChange->change_to === "0" ? 'selected' : '' }}@endif>No</option>
                    </select>
                    <div id="trad_photo_named_yes_{{$group_question->id}}">
                        <!-- Proceed -->
                    </div>
                    <div id="trad_photo_named_no_{{$group_question->id}}">
                        <!-- Halt -->
                        {!! $group_question->issue_error_message !!}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="f1-buttons">
        <button type="button" id="groupPrevious" class="btn btn-previous btn-secondary">Previous</button>
        <button id="groupNext" type="button" class="btn btn-next btn-primary">Next</button>
        <button id="groupNextDisabled" type="button" class="btn btn-next-disabled btn-secondary" onclick="alert('Please answer all questions before proceeding.')">Next</button>
        <div class="end_of_step_3"></div>
    </div>
</fieldset>
