<div class="row">
    <div class="col-12 m-auto">
        <div class="card">
            <div class="card-header">
                <legend><?= __('Folder Configuration') ?></legend>
            </div>
            <div class="card-body">
                <p class="mb-0">
                    Visible for Proofing
                </p>
                <ul>
                    <li>
                        Selecting this option will show/hide the Folder from Photo Coordinators and Teachers.
                    </li>
                    <li class="" data-count="is-visible-for-proofing">
                       <span data-count="is-visible-for-proofing-active">{{$isVisibleForProofingCounter['true']}}</span> Folders marked as visible for proofing, <span data-count="is-visible-for-proofing-inactive">{{$isVisibleForProofingCounter['false']}}</span> Folders marked as invisible.
                    </li>
                </ul>

                <p class="mb-0">
                    Show Portraits Step
                </p>
                <ul>
                    <li>
                        Selecting this option will show/hide the Portrait Photos wizard step.
                    </li>
                    <li class="" data-count="is-edit-portraits">
                       <span data-count="is-edit-portraits-active">{{$isEditPortraitsCounter['true']}}</span> Folders marked as having Group Names, <span data-count="is-edit-portraits-inactive">{{$isEditPortraitsCounter['false']}}</span> Folders marked as not having Group Names.
                    </li>
                </ul>

                <p class="mb-0">
                    Show Group Step
                </p>
                <ul>
                    <li>
                        Selecting this option will show/hide the Group Photo wizard step.
                    </li>
                    <li class="" data-count="is-edit-group">
                       <span data-count="is-edit-group-active">{{$isEditGroupCounter['true']}}</span> Folders marked as having Group Names, <span data-count="is-edit-group-inactive">{{$isEditGroupCounter['false']}}</span> Folders marked as not having Group Names.
                    </li>
                </ul>

                <p class="mb-0">
                    Has Group Names
                </p>
                <ul>
                    <li>
                        If a Traditional Group Photo is uploaded, selecting this option will allow Photo
                        Coordinators and Teachers to view/edit the Group Names.
                    </li>
                    <li class="" data-count="is-subject-list-allowed">
                       <span data-count="is-subject-list-allowed-active">{{$isSubjectListAllowedCounter['true']}}</span> Folders marked as having Group Names, <span data-count="is-subject-list-allowed-inactive">{{$isSubjectListAllowedCounter['false']}}</span> Folders marked as not having Group Names.
                    </li>
                </ul>

                <?php
                    $uploadMaxFilesize = ini_get('upload_max_filesize');
                ?>
                <p class="mb-0">
                    Show Group Image
                </p>
                <ul>
                    <li>
                        Upload a Traditional Group Photo for each Folder as required.
                    </li>
                    <li>
                        There is a {{$uploadMaxFilesize}} size limit per photo.
                    </li>
                    <li>
                        Click thumbnails to view a larger image.
                    </li>
                </ul>

                <p class="mb-0">
                    Has Principal/Deputy/Teacher
                </p>
                <ul>
                    <li>
                        Selecting this option will show/hide the Principal/Deputy/Teacher fields.
                    </li>
                    <li>
                       <span data-count="is-edit-principal-active">{{$isEditPrincipalCounter['true']}}</span> Folders marked as having Principal field editable, <span data-count="is-edit-principal-inactive">{{$isEditPrincipalCounter['false']}}</span> Folders marked as Principal field not editable.
                    </li>
                    <li>
                       <span data-count="is-edit-deputy-active">{{$isEditDeputyCounter['true']}}</span> Folders marked as having Deputy field editable, <span data-count="is-edit-deputy-inactive">{{$isEditDeputyCounter['false']}}</span> Folders marked as Deputy field not editable.
                    </li>
                    <li>
                       <span data-count="is-edit-teacher-active">{{$isEditTeacherCounter['true']}}</span> Folders marked as having Teacher field editable, <span data-count="is-edit-teacher-inactive">{{$isEditTeacherCounter['false']}}</span> Folders marked Teacher field not editable.
                    </li>
                </ul>

                <div class="row ">
                    <div class="col-md-12">
                        <div id="ajax-response-readable" class="alert" role="alert" style="display: none;"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 table-fixed-header">
                        <table class="table table-responsive-sm table-sm table-bordered">
                            <thead>
                                <tr class="bg-light">
                                    <th>Folder</th>
                                    <th class="text-center">
                                        Visible for Proofing
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                            <span class="font-weight-light"
                                                id="set-is-visible-for-proofing-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-visible-for-proofing-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Portraits Step
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-portraits-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-portraits-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Group Step
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-group-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-group-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Has Group Names
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="is-subject-list-allowed-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="is-subject-list-allowed-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Group Image
                                    </th>
                                    <th class="text-center">
                                        Has Principal
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-principal-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-principal-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Has Deputy
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-deputy-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-deputy-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Has Teacher
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-teacher-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-teacher-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Has Salutation
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-salutation-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-salutation-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Has Job Title
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-job-title-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-job-title-none">None</span>
                                        </p>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $folderKeys = [];
                                ?>
                                @foreach ($selectedFolders as $folder) 
                                    @php
                                        $folderKeys[] = $folder->ts_folderkey;
                                        $folderKey = $folder->ts_folderkey;
                                        $folderName = $folder->ts_foldername;
                                        $fkHash = Crypt::encryptString($folder->ts_folderkey);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="mt-1 ml-1">
                                                <?= $folder->ts_foldername ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-visible-for-proofing is-visible-for-proofing--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        $allowProofingVisible = false;
                                                        $allowProofingVisibleDisabled = false;
                                        
                                                        if (isset($isVisibleForProofingList[$folder->ts_folder_id])) {
                                                            if ($isVisibleForProofingList[$folder->ts_folder_id] == false) {
                                                                $allowProofingVisibleDisabled = true;
                                                            } else {
                                                                $allowProofingVisible = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <input type="checkbox"
                                                            class="form-check-input folder-details-is-visible-for-proofing text-center mt-2 ml-0 mr-0"
                                                            id="is-visible-for-proofing-{{ $folderKey }}"
                                                            name="is-visible-for-proofing-{{ $folderName }}"
                                                            data-folder-key="{{ $folderKey }}"
                                                            data-folder-name="{{ $folderName }}"
                                                            data-folder-id="{{ $folder->ts_folder_id }}"
                                                            {{ $allowProofingVisible ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-portraits is-edit-portraits--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        $isEditPortraits = false;
                                                        $isEditPortraitsDisabled = false;
                                        
                                                        if (isset($isEditPortraitsList[$folder->ts_folder_id])) {
                                                            if ($isEditPortraitsList[$folder->ts_folder_id] == false) {
                                                                $isEditPortraitsDisabled = true;
                                                            } else {
                                                                $isEditPortraits = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <input type="checkbox"
                                                            class="form-check-input folder-details-is-edit-portraits text-center mt-2 ml-0 mr-0"
                                                            id="is-edit-portraits-{{ $folderKey }}"
                                                            name="is-edit-portraits-{{ $folderName }}"
                                                            data-folder-key="{{ $folderKey }}"
                                                            data-folder-name="{{ $folderName }}"
                                                            data-folder-id="{{ $folder->ts_folder_id }}"
                                                            {{ $isEditPortraits ? 'checked' : '' }}
                                                            {{ $isEditPortraitsDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-4 text-center">
                                                    @if (isset($sortingTypesList[$folderKey]))
                                                        @if ($sortingTypesList[$folderKey] == 'SpecialSort')
                                                            TNJ Special Sort
                                                        @elseif ($sortingTypesList[$folderKey] == 'SortOrder')
                                                            TNJ Sort Order
                                                        @elseif ($sortingTypesList[$folderKey] == 'Default')
                                                            Alphabetical
                                                        @else
                                                            {{ $sortingTypesList[$folderKey] }}
                                                        @endif
                                                    @else
                                                        Alphabetical
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-group is-edit-group--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        $isEditGroup = false;
                                                        $isEditGroupDisabled = false;
                                        
                                                        if (isset($isEditGroupList[$folder->ts_folder_id])) {
                                                            if ($isEditGroupList[$folder->ts_folder_id] == false) {
                                                                $isEditGroupDisabled = true;
                                                            } else {
                                                                $isEditGroup = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <input type="checkbox"
                                                            class="form-check-input folder-details-is-edit-group text-center mt-2 ml-0 mr-0"
                                                            id="is-edit-group-{{ $folderKey }}"
                                                            name="is-edit-group-{{ $folderName }}"
                                                            data-folder-key="{{ $folderKey }}"
                                                            data-folder-name="{{ $folderName }}"
                                                            data-folder-id="{{ $folder->ts_folder_id }}"
                                                            {{ $isEditGroup ? 'checked' : '' }}
                                                            {{ $isEditGroupDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="row is-subject-list-allowed is-subject-list-allowed--{{ $folderKey }}">
                                                        <div class="col-12">
                                                            @php
                                                                $isSubjectListAllowed = false;
                                                                $isSubjectListAllowedDisabled = false;
                                        
                                                                if (isset($isSubjectListAllowedList[$folder->ts_folder_id])) {
                                                                    if ($isSubjectListAllowedList[$folder->ts_folder_id] == false) {
                                                                        $isSubjectListAllowedDisabled = true;
                                                                    } else {
                                                                        $isSubjectListAllowed = true;
                                                                    }
                                                                }
                                                            @endphp
                                        
                                                            <div class="form-group text-center">
                                                                <input type="checkbox"
                                                                    class="form-check-input folder-details-is-subject-list-allowed text-center mt-2 ml-0 mr-0"
                                                                    id="is-subject-list-allowed-{{ $folderKey }}"
                                                                    name="is-subject-list-allowed-{{ $folderName }}"
                                                                    data-folder-key="{{ $folderKey }}"
                                                                    data-folder-name="{{ $folderName }}"
                                                                    data-folder-id="{{ $folder->ts_folder_id }}"
                                                                    {{ $isSubjectListAllowed ? 'checked' : '' }}
                                                                    {{ $isSubjectListAllowedDisabled ? 'disabled' : '' }}>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="row traditional-photo-upload traditional-photo-upload--{{ $folderKey }}">
                                                        <div class="col-12 text-center">
                                                            <div class="row">
                                                                <div class="col-4 text-center">
                                                                    @php
                                                                        $imageData = $folder->images;
                                                                        if(isset($imageData)) {
                                                                            $imageUrl = asset('storage/groupImages/'.$imageData->name);
                                                                            $deleteLinkVisible = isset($imageData->name);
                                                                        } else {
                                                                            $imageUrl = asset('proofing-assets/img/traditionalGroupPlaceholderImage.png');
                                                                            $deleteLinkVisible = '';
                                                                        }
                                                                    @endphp
                                                                    <img loading="lazy" src="{{ $imageUrl }}" 
                                                                        class="mx-auto d-block modal-thumb" 
                                                                        style="max-width: 100%; max-height: 200px; height: auto;" 
                                                                        id="{{ $folderKey }}-image"
                                                                        data-modal-title="{{ $folder->ts_foldername }}" 
                                                                        data-modal-src="{{ $imageUrl }}">
                                                                
                                                                    @php
                                                                        $deleteClass = $deleteLinkVisible ? 'delete-artifact mx-auto d-block' : 'delete-artifact mx-auto d-none';
                                                                    @endphp
                                                                
                                                                    <a href="#" 
                                                                        id="{{ $folderKey }}-delete" 
                                                                        class="{{ $deleteClass }}" 
                                                                        data-folder-key="{{ $folderKey }}" 
                                                                        data-folder-name="{{ $folderName }}" onclick="event.preventDefault();">
                                                                        Delete
                                                                    </a>
                                                                </div>
                                                                
                                        
                                                                <div class="col-8">
                                                                    <div class="form-group text-center">
                                                                        <label for="{{ $folderKey }}" class="custom-file-label btn btn-secondary">
                                                                            Upload File
                                                                        </label>
                                                                        <input type="file"
                                                                            class="form-control-file traditional-photo-upload d-none"
                                                                            id="{{ $folderKey }}"
                                                                            name="{{ $folderName }}">
                                                                    </div> 
                                        
                                                                    <div id="{{ $folderKey }}-bar" class="d-none">
                                                                        <div id="progress-wrp-{{ $folderKey }}" class="progress mt-2 mb-2">
                                                                            <div class="progress-bar" role="progressbar"
                                                                                 style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                        
                                                                    <div id="{{ $folderKey }}-error" class="alert alert-danger d-none p-1 mt-2 mb-2">
                                                                        Error MSG
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-principal is-edit-principal--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditPrincipal = false;
                                                        $isEditPrincipalDisabled = false;
                                        
                                                        // Logic for handling the principal flag
                                                        if (isset($isEditPrincipalList[$folder->ts_folder_id])) {
                                                            if ($isEditPrincipalList[$folder->ts_folder_id] == false) {
                                                                $isEditPrincipalDisabled = true;
                                                            } else {
                                                                $isEditPrincipal = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-principal text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-principal-{{ $folderKey }}"
                                                               name="is-edit-principal-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditPrincipal ? 'checked' : '' }}
                                                               {{ $isEditPrincipalDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-deputy is-edit-deputy--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditDeputy = false;
                                                        $isEditDeputyDisabled = false;
                                        
                                                        // Logic for handling the deputy flag
                                                        if (isset($isEditDeputyList[$folder->ts_folder_id])) {
                                                            if ($isEditDeputyList[$folder->ts_folder_id] == false) {
                                                                $isEditDeputyDisabled = true;
                                                            } else {
                                                                $isEditDeputy = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-deputy text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-deputy-{{ $folderKey }}"
                                                               name="is-edit-deputy-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditDeputy ? 'checked' : '' }}
                                                               {{ $isEditDeputyDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-teacher is-edit-teacher--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditTeacher = false;
                                                        $isEditTeacherDisabled = false;
                                        
                                                        // Logic for handling the teacher flag
                                                        if (isset($isEditTeacherList[$folder->ts_folder_id])) {
                                                            if ($isEditTeacherList[$folder->ts_folder_id] == false) {
                                                                $isEditTeacherDisabled = true;
                                                            } else {
                                                                $isEditTeacher = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-teacher text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-teacher-{{ $folderKey }}"
                                                               name="is-edit-teacher-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditTeacher ? 'checked' : '' }}
                                                               {{ $isEditTeacherDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-salutation is-edit-salutation--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditSalutation = false;
                                                        $isEditSalutationDisabled = false;
                                        
                                                        // Logic for handling the salutation flag
                                                        if (isset($isEditSalutationList[$folder->ts_folder_id])) {
                                                            if ($isEditSalutationList[$folder->ts_folder_id] == false) {
                                                                $isEditSalutationDisabled = true;
                                                            } else {
                                                                $isEditSalutation = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-salutation text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-salutation-{{ $folderKey }}"
                                                               name="is-edit-salutation-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditSalutation ? 'checked' : '' }}
                                                               {{ $isEditSalutationDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-job-title is-edit-job-title--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditJobTitle = false;
                                                        $isEditJobTitleDisabled = false;
                                        
                                                        // Logic for handling the job-title flag
                                                        if (isset($isEditJobTitleList[$folder->ts_folder_id])) {
                                                            if ($isEditJobTitleList[$folder->ts_folder_id] == false) {
                                                                $isEditJobTitleDisabled = true;
                                                            } else {
                                                                $isEditJobTitle = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-job-title text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-job-title-{{ $folderKey }}"
                                                               name="is-edit-job-title-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditJobTitle ? 'checked' : '' }}
                                                               {{ $isEditJobTitleDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td> 
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <input type="hidden" name="allFolderKeys" value={{ json_encode($folderKeys) }}>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>