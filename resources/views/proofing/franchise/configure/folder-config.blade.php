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
                                    <th class="text-center">
                                        Show Salutation in Portraits
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-job-show-salutation-portrait-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-job-show-salutation-portrait-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Prefix & Suffix in Portraits
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-job-prefix-suffix-portrait-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-job-prefix-suffix-portrait-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Salutation in Groups
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-job-show-salutation-group-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-job-show-salutation-group-none">None</span>
                                        </p>
                                    </th>
                                    <th class="text-center">
                                        Show Prefix & Suffix in Groups
                                        <br>
                                        <p class="mb-0 d-repeating-header-none">
                                                <span class="font-weight-light"
                                                    id="set-is-edit-job-prefix-suffix-group-all">Select All</span>
                                            <span class="font-weight-normal"> | </span>
                                            <span class="font-weight-light"
                                                id="set-is-edit-job-prefix-suffix-group-none">None</span>
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
                                                        
                                                        $allowProofingVisible = true;
                                                        $allowProofingVisibleDisabled = false;
                                                        if (isset($isVisibleForProofingList[$folder->ts_folder_id])) {
                                                            if ($isVisibleForProofingList[$folder->ts_folder_id] == false) {
                                                                $allowProofingVisible = false;
                                                            } elseif ($isVisibleForProofingList[$folder->ts_folder_id] == null) {
                                                                $allowProofingVisible = false;
                                                                $allowProofingVisibleDisabled = false;
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
                                                            {{ $allowProofingVisible ? 'checked' : '' }}
                                                            {{ $allowProofingVisibleDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-portraits is-edit-portraits--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        $isEditPortraits = true;
                                                        $isEditPortraitsDisabled = false;
                                                        if (isset($isEditPortraitsList[$folder->ts_folder_id])) {
                                                            if ($isEditPortraitsList[$folder->ts_folder_id] == false) {
                                                                $isEditPortraits = false;
                                                            } elseif ($isEditPortraitsList[$folder->ts_folder_id] == null) {
                                                                $isEditPortraits = false;
                                                                $isEditPortraitsDisabled = true;
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
                                                {{-- <div class="col-12 mt-4 text-center">
                                                    Alphabetical
                                                </div> --}}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row is-edit-group is-edit-group--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        $isEditGroup = true;
                                                        $isEditGroupDisabled = false;
                                                        if (isset($isEditGroupList[$folder->ts_folder_id])) {
                                                            if ($isEditGroupList[$folder->ts_folder_id] == false) {
                                                                $isEditGroup = false;
                                                            } elseif ($isEditGroupList[$folder->ts_folder_id] == null) {
                                                                $isEditGroup = false;
                                                                $isEditGroupDisabled = true;
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
                                                                $isSubjectListAllowed = true;
                                                                $isSubjectListAllowedDisabled = false;
                                                                if (isset($isSubjectListAllowedList[$folder->ts_folder_id])) {
                                                                    if ($isSubjectListAllowedList[$folder->ts_folder_id] == false) {
                                                                        $isSubjectListAllowed = false;
                                                                    } elseif ($isSubjectListAllowedList[$folder->ts_folder_id] == null) {
                                                                        $isSubjectListAllowed = false;
                                                                        $isSubjectListAllowedDisabled = true;
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
                                                                        
                                                                        if (!empty($imageData) && !empty($imageData->name)) {
                                                                            $imageUrl = route('image.show', Crypt::encryptString($imageData->name));
                                                                            $deleteLinkVisible = true;
                                                                        } else {
                                                                            $imageUrl = asset('proofing-assets/img/traditionalGroupPlaceholderImage.png');
                                                                            $deleteLinkVisible = false;
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
                                                        $isEditPrincipal = true;
                                                        $isEditPrincipalDisabled = false;
                                                        if (isset($isEditPrincipalList[$folder->ts_folder_id])) {
                                                            if ($isEditPrincipalList[$folder->ts_folder_id] == false) {
                                                                $isEditPrincipal = false;
                                                            } elseif ($isEditPrincipalList[$folder->ts_folder_id] == null) {
                                                                $isEditPrincipal = false;
                                                                $isEditPrincipalDisabled = true;
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
                                                        $isEditDeputy = true;
                                                        $isEditDeputyDisabled = false;
                                                        if (isset($isEditDeputyList[$folder->ts_folder_id])) {
                                                            if ($isEditDeputyList[$folder->ts_folder_id] == false) {
                                                                $isEditDeputy = false;
                                                            } elseif ($isEditDeputyList[$folder->ts_folder_id] == null) {
                                                                $isEditDeputy = false;
                                                                $isEditDeputyDisabled = true;
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
                                                        $isEditTeacher = true;
                                                        $isEditTeacherDisabled = false;
                                                        if (isset($isEditTeacherList[$folder->ts_folder_id])) {
                                                            if ($isEditTeacherList[$folder->ts_folder_id] == false) {
                                                                $isEditTeacher = false;
                                                            } elseif ($isEditTeacherList[$folder->ts_folder_id] == null) {
                                                                $isEditTeacher = false;
                                                                $isEditTeacherDisabled = true;
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
                                                        $isEditSalutation = true;
                                                        $isEditSalutationDisabled = false;
                                                        if (isset($isEditSalutationList[$folder->ts_folder_id])) {
                                                            if ($isEditSalutationList[$folder->ts_folder_id] == false) {
                                                                $isEditSalutation = false;
                                                            } elseif ($isEditSalutationList[$folder->ts_folder_id] == null) {
                                                                $isEditSalutation = false;
                                                                $isEditSalutationDisabled = true;
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
                                                        $isEditJobTitle = true;
                                                        $isEditJobTitleDisabled = false;
                                                        if (isset($isEditJobTitleList[$folder->ts_folder_id])) {
                                                            if ($isEditJobTitleList[$folder->ts_folder_id] == false) {
                                                                $isEditJobTitle = false;
                                                            } elseif ($isEditJobTitleList[$folder->ts_folder_id] == null) {
                                                                $isEditJobTitle = false;
                                                                $isEditJobTitleDisabled = true;
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
                                        <td>
                                            <div class="row is-edit-job-show-salutation-portrait is-edit-job-show-salutation-portrait--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditJobShowSalutationPortrait = true;
                                                        $isEditJobShowSalutationPortraitDisabled = false;
                                        
                                                        // Logic for handling the job-title flag
                                                        if (isset($isEditJobShowSalutationPortraitList[$folder->ts_folder_id])) {
                                                            if ($isEditJobShowSalutationPortraitList[$folder->ts_folder_id] == false) {
                                                                $isEditJobShowSalutationPortrait = false;
                                                            } elseif ($isEditJobShowSalutationPortraitList[$folder->ts_folder_id] == null) {
                                                                $isEditJobShowSalutationPortrait = false;
                                                                $isEditJobShowSalutationPortraitDisabled = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-job-show-salutation-portrait text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-job-show-salutation-portrait-{{ $folderKey }}"
                                                               name="is-edit-job-show-salutation-portrait-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditJobShowSalutationPortrait ? 'checked' : '' }}
                                                               {{ $isEditJobShowSalutationPortraitDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td> 
                                        <td>
                                            <div class="row is-edit-job-prefix-suffix-portrait is-edit-job-prefix-suffix-portrait--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditJobPrefixSuffixPortrait = true;
                                                        $isEditJobPrefixSuffixPortraitDisabled = false;
                                        
                                                        // Logic for handling the job-title flag
                                                        if (isset($isEditJobPrefixSuffixPortraitList[$folder->ts_folder_id])) {
                                                            if ($isEditJobPrefixSuffixPortraitList[$folder->ts_folder_id] == false) {
                                                                $isEditJobPrefixSuffixPortrait = false;
                                                            } elseif ($isEditJobPrefixSuffixPortraitList[$folder->ts_folder_id] == null) {
                                                                $isEditJobPrefixSuffixPortrait = false;
                                                                $isEditJobPrefixSuffixPortraitDisabled = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-job-prefix-suffix-portrait text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-job-prefix-suffix-portrait-{{ $folderKey }}"
                                                               name="is-edit-job-prefix-suffix-portrait-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditJobPrefixSuffixPortrait ? 'checked' : '' }}
                                                               {{ $isEditJobPrefixSuffixPortraitDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td> 
                                        <td>
                                            <div class="row is-edit-job-show-salutation-group is-edit-job-show-salutation-group--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditJobShowSalutationGroup = true;
                                                        $isEditJobShowSalutationGroupDisabled = false;
                                        
                                                        // Logic for handling the job-title flag
                                                        if (isset($isEditJobShowSalutationGroupList[$folder->ts_folder_id])) {
                                                            if ($isEditJobShowSalutationGroupList[$folder->ts_folder_id] == false) {
                                                                $isEditJobShowSalutationGroup = false;
                                                            } elseif ($isEditJobShowSalutationGroupList[$folder->ts_folder_id] == null) {
                                                                $isEditJobShowSalutationGroup = false;
                                                                $isEditJobShowSalutationGroupDisabled = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-job-show-salutation-group text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-job-show-salutation-group-{{ $folderKey }}"
                                                               name="is-edit-job-show-salutation-group-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditJobShowSalutationGroup ? 'checked' : '' }}
                                                               {{ $isEditJobShowSalutationGroupDisabled ? 'disabled' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </td> 
                                        <td>
                                            <div class="row is-edit-job-prefix-suffix-group is-edit-job-prefix-suffix-group--{{ $folderKey }}">
                                                <div class="col-12">
                                                    @php
                                                        // Default settings
                                                        $isEditJobPrefixSuffixGroup = true;
                                                        $isEditJobPrefixSuffixGroupDisabled = false;
                                        
                                                        // Logic for handling the job-title flag
                                                        if (isset($isEditJobPrefixSuffixGroupList[$folder->ts_folder_id])) {
                                                            if ($isEditJobPrefixSuffixGroupList[$folder->ts_folder_id] == false) {
                                                                $isEditJobPrefixSuffixGroup = false;
                                                            } elseif ($isEditJobPrefixSuffixGroupList[$folder->ts_folder_id] == null) {
                                                                $isEditJobPrefixSuffixGroup = false;
                                                                $isEditJobPrefixSuffixGroupDisabled = true;
                                                            }
                                                        }
                                                    @endphp
                                        
                                                    <div class="form-group text-center">
                                                        <!-- Checkbox -->
                                                        <input type="checkbox"
                                                               class="form-check-input folder-details-is-edit-job-prefix-suffix-group text-center mt-2 ml-0 mr-0"
                                                               id="is-edit-job-prefix-suffix-group-{{ $folderKey }}"
                                                               name="is-edit-job-prefix-suffix-group-{{ $folderName }}"
                                                               data-folder-key="{{ $folderKey }}"
                                                               data-folder-name="{{ $folderName }}"
                                                               data-folder-id="{{ $folder->ts_folder_id }}"
                                                               {{ $isEditJobPrefixSuffixGroup ? 'checked' : '' }}
                                                               {{ $isEditJobPrefixSuffixGroupDisabled ? 'disabled' : '' }}>
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