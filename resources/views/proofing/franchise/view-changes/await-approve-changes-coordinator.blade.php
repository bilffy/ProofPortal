@extends('proofing.layouts.master')
@section('title', 'View Unapproved Changes')

@section('css')

@stop

@section('content')

    @php
        use Illuminate\Support\Facades\Crypt;
        use Illuminate\Support\Str;
        use App\Services\FolderService;
    @endphp

    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
    
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                {{ __('Unapproved Changes To People In :name', ['name' => $selectedJob->ts_jobname]) }}
            </h1>
        </div>
    
        <div class="col-12 mb-3">
            @php
                $btnAttrCancel = [
                    "class" => "btn btn-primary float-right pl-4 pr-4",
                ];
                $urlDone = route('proofing');
                $image_url = asset('proofing-assets/img/subject-image.png');   
            @endphp
    
            <a href="{{ $urlDone }}" class="{{ $btnAttrCancel['class'] }}">{{ __('Done') }}</a>
        </div>
    </div>
    
    @if (count($subjectChanges) !== 0)
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-align-justify"></i> {{ __('Changes to People') }}
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Most changes are automatically approved, however, the following types of changes
                            need the approval of a Photo Coordinator:</p>
                        <ul>
                            <li>Moving a Person between Classes or Groups</li>
                            <li>Removal of a photo due to a Person leaving the School</li>
                            <li>Removal of a photo for reasons such as infringement or child protection</li>
                            <li>Change of photo</li>
                            <li>People absent on photo day</li>
                        </ul>
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <span id="approval-acknowledge" class="p-2"></span>&nbsp;
                            </div>
                        </div>

                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        Name
                                        <a href="#" class="small people-photos-hide d-inline">(Hide Photos)</a>
                                        <a href="#" class="small people-photos-show d-none">(Show Photos)</a>
                                    </th>
                                    <th scope="col">Issue Type</th>
                                    <th scope="col">Note</th>
                                    <th scope="col">Change Requested</th>
                                    <th scope="col">By User</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @inject('folderService', 'App\Services\Proofing\FolderService')
                                @foreach($subjectChanges as $subjectChange)
                                @php
                                    // $folderKey = implode("-", $attachedFolderNames[$subjectChange->ts_subjectkey]['keys']);
                                    $skHash = sha1($subjectChange->ts_subjectkey);
                                    $hash = Crypt::encryptString($subjectChange->ts_subjectkey);
                                    $rowIdSelector = sha1(json_encode($subjectChange));
                                    if ($subjectChange->ts_subjectkey != '' && $selectedJob->ts_jobkey != '') {
                                        // $combined_key = $subjectChange->ts_subjectkey . $selectedJob->ts_jobkey;
                                        // $encryptImageKey = sprintf("%08x", crc32($combined_key));
                                        // $hashed_key = hash('sha256', $combined_key);
                                        // $sub_dirs = [];

                                        // for ($i = 0; $i < strlen($hashed_key); $i += 5) {
                                        //     $sub_dirs[] = substr($hashed_key, $i, 3);
                                        // }

                                        // // Generate the directory structure and filename using DIRECTORY_SEPARATOR
                                        // $full_path = implode(DIRECTORY_SEPARATOR, $sub_dirs);
                                        // $imageName = DIRECTORY_SEPARATOR . $full_path . DIRECTORY_SEPARATOR . $encryptImageKey . '.jpg';
                                        // $newimageName = Str::replace('\\', '-', $imageName);
                                        // // Generate a signed URL for the image
                                        // $image_url = route('serve.image', ['filename' => $newimageName]);
                                        $image_url = route('serve.image', ['filename' => $hash]);
                                    }
                                    if($subjectChange->external_issue_name === 'Class'){
                                        $id = str_replace("Folder From: ", "", $subjectChange->change_from);
                                        $folderFrom = $folderService->findFolderId($id);
                                    }
                                @endphp
                                
                                <tr id="{{ $rowIdSelector }}">
                                    <td class="text-center pt-2 pb-1">
                                        <div class="person-pic-wrapper d-inline">
                                            {{-- <img src="{{ $image_url }}" class="mx-auto d-block" style="max-width: 100%; max-height: 90px;" alt="Subject Image"> --}}
                                            <img style="max-width: 100%; max-height: 90px;" class="lazyload mx-auto d-block" src="{{ $image_url }}" data-src="{{ $image_url }}" alt="Subject Image">
                                        </div>
                                        {{ $subjectChange->firstname }} {{ $subjectChange->lastname }}
                                    </td>
                                    <td>{{ $subjectChange->external_issue_name }}</td>
                                    <td>{{ $subjectChange->notes }}</td>
                                    <td>{{ \Carbon\Carbon::parse($subjectChange->change_datetime)->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $subjectChange->user->firstname }} {{ $subjectChange->user->lastname }}</td>
                                    <td>
                                        @if($subjectChange->external_issue_name == 'Picture' || $subjectChange->external_issue_name == 'Class')
                                            <a id="modify" href="#" @if($subjectChange->external_issue_name == 'Class') data-change-from = "{{ Crypt::encryptString($folderFrom->ts_folderkey) }}" @endif data-issue-type = "{{ $subjectChange->external_issue_name }}" @if($subjectChange->external_issue_name == 'Picture') data-issue-id = "{{$pictureissueID}}" @elseif($subjectChange->external_issue_name == 'Class') data-issue-id = "{{$folderissueID}}" @endif data-full-name = "{{ $subjectChange->firstname }} {{ $subjectChange->lastname }}" data-toggle="modal" data-target="#ModifyApproval_Modal" data-row-selector="{{ $rowIdSelector }}" data-skhash="{{ $skHash }}" data-skencrypted="{{ $hash }}" data-correction-id="{{$subjectChange->id}}" data-action="modify">Modify</a> | 
                                        @endif
                                            <a href="#" data-issue-type = "{{ $subjectChange->external_issue_name }}" data-full-name = "{{ $subjectChange->firstname }} {{ $subjectChange->lastname }}" data-toggle="modal" data-target="#ModifyApproval_Modal" data-row-selector="{{ $rowIdSelector }}" data-skhash="{{ $skHash }}" data-skencrypted="{{ $hash }}" data-correction-id="{{$subjectChange->id}}" data-action="approve">Approve</a> | 
                                            <a href="#" data-issue-type = "{{ $subjectChange->external_issue_name }}" data-full-name = "{{ $subjectChange->firstname }} {{ $subjectChange->lastname }}" data-toggle="modal" data-target="#ModifyApproval_Modal" data-row-selector="{{ $rowIdSelector }}" data-skhash="{{ $skHash }}" data-skencrypted="{{ $hash }}" data-correction-id="{{$subjectChange->id}}" data-action="reject">Reject</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ModifyApproval_Modal" tabindex="-1" role="dialog" aria-labelledby="ModifyApprovalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ModifyApproval_Modal_Title">Modify Approval</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div id="folder-issue-wrapper" class="d-none mt-4 mb-4">
                                    <label for="folder_issue" class="m-0">Do you know which Class/Group they belong to? If so, select from below.</label>
                                    <select name="folder_issue" id="folder_issue" class="form-control">
                                        @foreach($allFolders as $key => $allFolder)
                                            <option value="{{ $allFolder->id }}">{{ $allFolder->ts_foldername }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="picture-issue-wrapper" class="d-none mt-4 mb-4">
                                    <label for="picture_issue" class="m-0">Do you know who this is? Type their name below.</label>
                                    <input type="text" name="picture_issue" id="picture_issue" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="approve-issue-wrapper" class="d-none mt-4 mb-4">
                                    Are you sure you would like to Approve this change?
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="reject-issue-wrapper" class="d-none mt-4 mb-4">
                                    Are you sure you would like to Reject this change?
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button id="send-correction" type="button" class="btn btn-primary" data-dismiss="modal">Save & Approve</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @else

        @include('proofing.franchise.flash-error')

    @endif

@endsection

@section('js')

    <script>
        $(document).ready(function () {        
            jQuery.noConflict();
            var linkHide = $(".people-photos-hide");
            var linkShow = $(".people-photos-show");
            var picWrapper = $(".person-pic-wrapper");

            linkHide.on('click', function () {
                linkHide.toggleClass("d-none d-inline");
                linkShow.toggleClass("d-inline d-none");
                picWrapper.toggleClass("d-none d-inline");
            });

            linkShow.on('click', function () {
                linkHide.toggleClass("d-none d-inline");
                linkShow.toggleClass("d-inline d-none");
                picWrapper.toggleClass("d-none d-inline");
            });

            $('#ModifyApproval_Modal').on('hidden.bs.modal', function () {
                $(this).find('#picture_issue').val('');
                $(this).find('#folder_issue').prop('selectedIndex', 0);
            });

            $('#ModifyApproval_Modal').on('show.bs.modal', function (event) {
                var modal = $(this);
                var button = $(event.relatedTarget);
                var skHash = button.data('skhash'); // Extract info from data-* attributes
                var skEncrypted = button.data('skencrypted'); // Extract info from data-* attributes
                var subjectFullName = button.data('full-name'); // Extract info from data-* attributes
                var issueType = button.data('issue-type'); // Extract info from data-* attributes
                var issueID = button.data('issue-id'); // Extract info from data-* attributes
                var subjectCorrectionID = button.data('correction-id'); // Extract info from data-* attributes
                var rowSelector = button.data('row-selector'); // Extract info from data-* attributes
                var approvalAction = button.data('action'); // Extract info from data-* attributes

                modal.find("#picture-issue-wrapper").addClass('d-none');
                modal.find("#folder-issue-wrapper").addClass('d-none');
                modal.find("#approve-issue-wrapper").addClass('d-none');
                modal.find("#reject-issue-wrapper").addClass('d-none');

                if (approvalAction === 'modify' && issueType === 'Class') {
                    modal.find("#ModifyApproval_Modal_Title").html("Modify Approval for " + subjectFullName);
                    modal.find("#send-correction").html("Modify & Approve");
                    modal.find("#folder-issue-wrapper").removeClass('d-none');
                } else if (approvalAction === 'modify' && issueType === 'Picture') {
                    modal.find("#ModifyApproval_Modal_Title").html("Modify Approval for " + subjectFullName);
                    modal.find("#send-correction").html("Modify & Approve");
                    modal.find("#picture-issue-wrapper").removeClass('d-none');
                } else if (approvalAction === 'approve') {
                    modal.find("#ModifyApproval_Modal_Title").html("Approve Changes to " + subjectFullName);
                    modal.find("#send-correction").html("Approve");
                    modal.find("#approve-issue-wrapper").removeClass('d-none');
                } else if (approvalAction === 'reject') {
                    modal.find("#ModifyApproval_Modal_Title").html("Reject Changes to " + subjectFullName);
                    modal.find("#send-correction").html("Reject");
                    modal.find("#reject-issue-wrapper").removeClass('d-none');
                }

                $('#send-correction').off('click').on('click', function () {
                    send();
                })

                function send() {
                    var targetUrl = base_url + "/changes-action/" + skEncrypted;

                    var picture_issue = '';
                    var folder_issue = '';
                    var formData = new FormData();

                    if (issueType === 'Class') {
                        folder_issue = $('#folder_issue').val()
                        const modifyEl = document.getElementById('modify');

                        if (modifyEl.hasAttribute('data-change-from')) {
                            const fkEncrypted = modifyEl.getAttribute('data-change-from');
                            formData.append("folder_key_encrypted", fkEncrypted);
                        }

                    } else if (issueType === 'Picture') {
                        picture_issue = $('#picture_issue').val()
                    }

                    formData.append("subject_key_hash", skHash);
                    formData.append("subject_key_encrypted", skEncrypted);
                    formData.append("subject_correction_id", subjectCorrectionID);
                    if(issueID){
                        formData.append("subjects_questions", issueID);
                    }
                    formData.append("action", approvalAction);
                    formData.append("picture_issue", picture_issue);
                    formData.append("folder_issue", folder_issue);

                    formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

                    $.ajax({
                        type: "POST",
                        url: targetUrl,
                        async: true,
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        timeout: 60000,

                        success: function (response) {  
                            let parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
                            if (parsedResponse.success === true) {
                                let acknowledgeMessage = '';
                                let backgroundColor = '';
                                let alertClass = '';

                                switch(parsedResponse.action) {
                                    case 'approve':
                                        backgroundColor = "#83f28f";
                                        acknowledgeMessage = "The Approval has been noted.";
                                        alertClass = "bg-success";
                                        break;
                                    case 'reject':
                                        backgroundColor = "#FF6865";
                                        acknowledgeMessage = "The rejection has been noted.";
                                        alertClass = "bg-danger";
                                        break;
                                    case 'modify':
                                        backgroundColor = "#83f28f";
                                        acknowledgeMessage = "The Modification has been noted.";
                                        alertClass = "bg-success";
                                        break;
                                    default:
                                        backgroundColor = "#ffffff"; // default or neutral color
                                        acknowledgeMessage = "Unknown action.";
                                        alertClass = "bg-warning";
                                        break;
                                }

                                $('#' + rowSelector).find("td").css("background-color", backgroundColor).fadeOut(1000);
                                $('#approval-acknowledge')
                                    .removeClass("bg-success bg-info bg-warning bg-danger")
                                    .addClass(alertClass)
                                    .html(acknowledgeMessage).fadeIn(500).delay(3000).fadeOut(500);
                            } else {
                                $('#approval-acknowledge')
                                    .removeClass("bg-success bg-info bg-warning bg-danger")
                                    .addClass("bg-danger")
                                    .html("Sorry, something went wrong, please try again.").fadeIn(500).delay(3000).fadeOut(500);
                            }
                        },
                        error: function (e) {
                                //alert("An error occurred: " + e.responseText.message);
                                // console.log(e);
                        }
                    })
                }
            });
        });
    </script>

@stop

