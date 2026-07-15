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
                            Paste from Excel into the grid (email column is free text).
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

            function normalizeEmail(value) {
                return String(value || '').trim().toLowerCase();
            }

            function refreshPeoplePayloadAndValidation() {
                var data = $('#invite-spreadsheet').jexcel('getData');
                var invalidEmails = [];
                var validCount = 0;

                data.forEach(function (row) {
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

                    if (!row[3]) {
                        row[3] = '*';
                    }
                });

                $("input[name='people']").val(JSON.stringify(data));
                $('#submitButton').prop('disabled', validCount === 0);

                var $validation = $('#invite-validation');
                if (invalidEmails.length) {
                    $validation
                        .removeClass('d-none')
                        .html(
                            '<strong>These emails will be skipped</strong> (not in this school for this role): '
                            + invalidEmails.map(function (email) {
                                return '<code>' + email + '</code>';
                            }).join(', ')
                        );
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
                        var email = normalizeEmail(row[2]);
                        if (!email) {
                            return;
                        }

                        var user = userLookup[email];
                        if (!user) {
                            return;
                        }

                        var rowNumber = index + 1;
                        if (normalizeEmail(row[2]) !== normalizeEmail(user.email)) {
                            $('#invite-spreadsheet').jexcel('setValue', 'C' + rowNumber, user.email);
                        }
                        if ((row[0] || '') !== (user.firstname || '')) {
                            $('#invite-spreadsheet').jexcel('setValue', 'A' + rowNumber, user.firstname || '');
                        }
                        if ((row[1] || '') !== (user.lastname || '')) {
                            $('#invite-spreadsheet').jexcel('setValue', 'B' + rowNumber, user.lastname || '');
                        }
                        if (!row[3]) {
                            $('#invite-spreadsheet').jexcel('setValue', 'D' + rowNumber, '*');
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
