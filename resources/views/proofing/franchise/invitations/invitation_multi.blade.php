@extends('proofing.layouts.master')
@section('title', 'Access')

@section('css')
    <link rel="stylesheet" href="{{ asset('proofing-assets/vendors/jexcel-1.3.4/dist/css/jquery.jexcel.css') }}">
    <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />
@stop

@php
    use Illuminate\Support\Facades\Crypt;
@endphp

@section('content')

@if(Session::has('selectedJob') && Session::has('selectedSeason'))
    <div class="row">
        <div class="col-12 mb-3">
            @php
                $btnAttrCancel = "btn btn-primary float-right pl-4 pr-4";
                $urlDone = route('invitation.index', ['role' => $role]);
                $title = $role === 'photocoordinator' ? 'Photo-Coordinator' : 'Teacher';
            @endphp
            <a href="{{ $urlDone }}" class="{{ $btnAttrCancel }}">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
            <form method="POST" action="{{ route('invitations.inviteSend') }}" id="invite-multi-form">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <legend>Assign {{ $title }} to {{ $selectedJob->ts_jobname }}</legend>
                    </div>

                    <div class="mt-4 mr-4 ml-4">
                        <p class="mb-2">
                            Type directly into the Spreadsheet below or copy-and-paste from an Excel document.<br>
                            In Class / Group, paste <strong>All Folders</strong> to assign every folder, or paste a folder name to assign that folder.<br>
                            Invitations are only sent to users who already belong to this school and have the {{ $title }} role.
                        </p>
                        <div id="invite-validation" class="alert alert-warning mt-3 d-none" role="alert"></div>
                    </div>
                    
                    <div class="mt-4 mb-4 ml-4" id="invite-spreadsheet"></div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submitButton" disabled>
                            Assign to {{ $selectedJob->ts_jobname }}
                        </button>

                        <input type="hidden" name="people">
                        <input type="hidden" name="job_key" value="{{ $selectedJob->ts_jobkey }}">
                        <input type="hidden" name="role" value="{{ $role }}">
                        <input type="hidden" name="model_name" value="Folders">
                        <input type="hidden" name="model_field_name" value="ts_folderkey">
                    </div>
                </div>
            </form>
        </div>
    </div>
@else
    @include('proofing.franchise.flash-error')
@endif

@php
    // Email → Firstname / Lastname lookup (lowercase keys for paste matching)
    $userLookup = [];
    foreach ($users as $u) {
        $userLookup[strtolower(trim((string) $u->email))] = [
            'firstname' => $u->firstname,
            'lastname' => $u->lastname,
            'email' => $u->email,
        ];
    }

    $emailList = array_values(array_map(
        fn ($email) => strtolower(trim((string) $email)),
        $users->pluck('email')->toArray()
    ));

    // Folder dropdown
    $sourceList = [['id' => '*', 'name' => 'All Folders']];
    foreach ($selectedJob->folders as $folder) {
        $sourceList[] = [
            'id' => $folder->ts_folderkey,
            'name' => $folder->ts_foldername
        ];
    }

    // Initial spreadsheet rows
    $spreadsheetData = [];
    for ($i = 0; $i < 4; $i++) {
        $spreadsheetData[] = ['', '', '', '*'];
    }
@endphp

@endsection

@section('js')
<script src="{{ asset('proofing-assets/vendors/jexcel-1.3.4/dist/js/jquery.jexcel.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>

    <script>
        $(document).ready(function () {
            var userLookup = @json($userLookup);
            var emailList = @json($emailList);
            var spreadsheetData = @json($spreadsheetData);
            var sourceList = @json($sourceList);
            var syncingNames = false;
            // row index → pasted name that did not match a folder (dropdown clears unknown names)
            var unrecognizedByRow = {};

            // Dropdown setValue only matches by id; map pasted folder names → ids
            var folderById = {};
            var folderByName = {};
            sourceList.forEach(function (item) {
                var id = String(item.id);
                var name = String(item.name || '').trim();
                folderById[id] = name;
                if (name) {
                    folderByName[name.toLowerCase()] = id;
                }
            });
            folderByName['all folders'] = '*';
            folderByName['allfolders'] = '*';

            function normalizeEmail(value) {
                return String(value || '').trim().toLowerCase();
            }

            function resolveFolderId(value) {
                var raw = String(value == null ? '' : value).trim();
                if (!raw) {
                    return '*';
                }
                if (folderById[raw] !== undefined) {
                    return raw;
                }
                var byName = folderByName[raw.toLowerCase()];
                return byName !== undefined ? byName : null;
            }

            function unrecognizedFolderNames() {
                return Object.keys(unrecognizedByRow).map(function (rowIdx) {
                    return unrecognizedByRow[rowIdx];
                });
            }

            function refreshPeoplePayloadAndValidation() {
                var data = $('#invite-spreadsheet').jexcel('getData');
                var invalidEmails = [];
                var validCount = 0;

                data.forEach(function (row, index) {
                    var email = normalizeEmail(row[2]);
                    if (!email) {
                        return;
                    }

                    if (userLookup[email]) {
                        validCount++;
                        row[2] = userLookup[email].email;
                    } else {
                        invalidEmails.push(email);
                    }

                    if (unrecognizedByRow[index]) {
                        // Do not fall back to All Folders for a failed folder-name paste
                        row[3] = '';
                    } else {
                        var folderId = resolveFolderId(row[3]);
                        row[3] = folderId !== null ? folderId : '*';
                    }
                });

                $("input[name='people']").val(JSON.stringify(data));
                $('#submitButton').prop('disabled', validCount === 0);

                var $validation = $('#invite-validation');
                var messages = [];
                var badFolders = unrecognizedFolderNames();
                if (invalidEmails.length) {
                    messages.push(
                        '<strong>These emails will be skipped</strong> (User role is not set for this school): '
                        + invalidEmails.map(function (email) {
                            return '<code>' + email + '</code>';
                        }).join(', ')
                    );
                }
                if (badFolders.length) {
                    messages.push(
                        '<strong>Unrecognized folder names</strong> (use All Folders or an exact folder name): '
                        + badFolders.map(function (name) {
                            return '<code>' + name + '</code>';
                        }).join(', ')
                    );
                }

                if (messages.length) {
                    $validation.removeClass('d-none').html(messages.join('<br>'));
                } else {
                    $validation.addClass('d-none').empty();
                }
            }

            var change = function () {
                if (syncingNames) {
                    return;
                }

                var data = $('#invite-spreadsheet').jexcel('getData');
                syncingNames = true;

                try {
                    data.forEach(function (row, index) {
                        var rowNumber = index + 1;
                        var email = normalizeEmail(row[2]);

                        if (email) {
                            var user = userLookup[email];
                            if (user) {
                                if (normalizeEmail(row[2]) !== normalizeEmail(user.email)) {
                                    $('#invite-spreadsheet').jexcel('setValue', 'C' + rowNumber, user.email);
                                }
                                if ((row[0] || '') !== (user.firstname || '')) {
                                    $('#invite-spreadsheet').jexcel('setValue', 'A' + rowNumber, user.firstname || '');
                                }
                                if ((row[1] || '') !== (user.lastname || '')) {
                                    $('#invite-spreadsheet').jexcel('setValue', 'B' + rowNumber, user.lastname || '');
                                }
                            }
                        }

                        if (unrecognizedByRow[index]) {
                            return;
                        }

                        var folderId = resolveFolderId(row[3]);
                        if (folderId !== null && String(row[3] || '') !== String(folderId)) {
                            $('#invite-spreadsheet').jexcel('setValue', 'D' + rowNumber, folderId);
                        }
                    });
                } finally {
                    syncingNames = false;
                }

                refreshPeoplePayloadAndValidation();
            };

            jQuery.noConflict();
            $('#invite-spreadsheet').jexcel({
                data: spreadsheetData,
                colHeaders: ['First Name', 'Last Name', 'Email', 'Class / Group'],
                colWidths: [120, 120, 280, 200],
                minDimensions: [4, 4],
                maxDimensions: [4, 40],
                allowInsertColumn: false,
                allowInsertRow: true,
                columns: [
                    { type: 'text' },
                    { type: 'text' },
                    // plain text so Excel-pasted emails are kept even when not in the allow-list
                    { type: 'text' },
                    { type: 'dropdown', source: sourceList }
                ],
                // jexcel dropdown only matches source ids; pasted names would be discarded.
                // Resolve name → id here while newValue is still available.
                onchange: function (instance, cell, newValue) {
                    if (syncingNames) {
                        return;
                    }

                    var position = String($(cell).prop('id') || '').split('-');
                    if (position[0] !== '3') {
                        return;
                    }

                    var rowIdx = position[1];
                    var resolved = resolveFolderId(newValue);
                    var pasted = String(newValue == null ? '' : newValue).trim();

                    if (resolved === null) {
                        if (pasted) {
                            unrecognizedByRow[rowIdx] = pasted;
                        }
                        return;
                    }

                    delete unrecognizedByRow[rowIdx];

                    if (String(resolved) === String(newValue)) {
                        return;
                    }

                    syncingNames = true;
                    try {
                        $(instance).jexcel('setValue', cell, resolved);
                    } finally {
                        syncingNames = false;
                    }
                },
                onafterchange: change,
                onpaste: function () {
                    setTimeout(change, 0);
                }
            });

            $('#invite-multi-form').on('submit', function () {
                refreshPeoplePayloadAndValidation();
            });

            refreshPeoplePayloadAndValidation();
        });
    </script>
@stop
