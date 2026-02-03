@extends('proofing.layouts.master')
@section('title', 'Invitations')

@section('css')
    <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />
@stop

@php
    use Illuminate\Support\Facades\Crypt;
@endphp

@section('content')
    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        @php
            $labelClass = 'col-md-3 form-control-label';
            $inputClass = 'form-control';

            $foldersTmp = [];
            foreach ($selectedJob->folders as $folder) {
                $foldersTmp[$folder->ts_folderkey] = $folder->ts_foldername;
            }

            if($role === 'photocoordinator'){
                $title = 'Photo-Coordinator';
            } else if($role === 'teacher'){
                $title = 'Teacher';
            }

            $btnAttrCancel = "btn btn-primary float-right pl-4 pr-4";
            $urlDone = (strpos(request()->headers->get('referer'), $role) !== false)
                        ? route('proofing') : url()->previous();
        @endphp

        <div class="row">
            <div class="col-12 mb-3">
                <div id="emailNotFoundMsg" class="alert alert-info mt-2" style="display:none">
                    If you have not found the user, <strong><a href="#" id="createUserLink" class="text-primary">Add the user</a></strong>
                </div>
            </div>
            <div class="col-12 mb-3">
                <a href="{{ $urlDone }}" class="{{ $btnAttrCancel }}">Back</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-xl-8 m-xl-auto">
                <div class="users">
                    <div class="card">
                        <div class="card-header">
                            <legend>{{ __('Invite :title to :school', ['title' => $title, 'school' => $selectedJob->ts_jobname]) }}</legend>
                        </div>

                        <form action="{{ route('invitations.inviteSend') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <fieldset>

                                    {{-- Email --}}
                                    <div class="form-group row">
                                        <label for="email" class="{{ $labelClass }}">Email</label>
                                        <div class="col-md-9">
                                            <select id="emailSelect" name="email" class="{{ $inputClass }}">
                                                <option value="">-- Select Email --</option>
                                                @foreach($users as $singleUser)
                                                    <option value="{{ $singleUser->email }}"
                                                        data-firstname="{{ $singleUser->firstname }}"
                                                        data-lastname="{{ $singleUser->lastname }}">
                                                        {{ $singleUser->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- First Name --}}
                                    <div class="form-group row">
                                        <label class="{{ $labelClass }}">First Name</label>
                                        <div class="col-md-9">
                                            <input type="text" name="first_name" id="first_name" class="{{ $inputClass }}">
                                        </div>
                                    </div>

                                    {{-- Last Name --}}
                                    <div class="form-group row">
                                        <label class="{{ $labelClass }}">Last Name</label>
                                        <div class="col-md-9">
                                            <input type="text" name="last_name" id="last_name" class="{{ $inputClass }}">
                                        </div>
                                    </div>

                                    {{-- Mobile --}}
                                    <div class="form-group row">
                                        <label class="{{ $labelClass }}">Mobile</label>
                                        <div class="col-md-9">
                                            <input type="tel" name="mobile" id="mobile" class="{{ $inputClass }}">
                                        </div>
                                    </div>

                                    {{-- Folder --}}
                                    <div class="form-group row">
                                        <label class="{{ $labelClass }}">Class/Group</label>
                                        <div class="col-md-9">
                                            <select name="folder" id="folder" class="{{ $inputClass }}">
                                                <option value="*">All Folders</option>
                                                @foreach($foldersTmp as $folderKey => $folderName)
                                                    <option value="{{ $folderKey }}">{{ $folderName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Activation Date --}}
                                    <div class="form-group row datetime d-none">
                                        <label class="{{ $labelClass }}">Activation Date</label>

                                        <div class="col-md-auto">
                                            <select name="activation[year]">
                                                @for($i = now()->year + 5; $i >= now()->year - 5; $i--)
                                                    <option value="{{ $i }}" {{ now()->year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="col-md-auto">
                                            <select name="activation[month]">
                                                @foreach(range(1, 12) as $month)
                                                    <option value="{{ $month }}" {{ now()->month == $month ? 'selected' : '' }}>
                                                        {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-auto">
                                            <select name="activation[day]">
                                                @foreach(range(1, 31) as $day)
                                                    <option value="{{ $day }}" {{ now()->day == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <input type="hidden" name="activation[hour]" value="00">
                                        <input type="hidden" name="activation[minute]" value="00">
                                        <input type="hidden" name="activation[second]" value="00">
                                    </div>

                                    {{-- Expiration Date --}}
                                    <div class="form-group row datetime d-none">
                                        <label class="{{ $labelClass }}">Expiration Date</label>

                                        <div class="col-md-auto">
                                            <select name="expiration[year]">
                                                @for($i = $expiryDate->year; $i >= now()->year - 5; $i--)
                                                    <option value="{{ $i }}" {{ $expiryDate->year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="col-md-auto">
                                            <select name="expiration[month]">
                                                @foreach(range(1, 12) as $month)
                                                    <option value="{{ $month }}" {{ $expiryDate->month == $month ? 'selected' : '' }}>
                                                        {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-auto">
                                            <select name="expiration[day]">
                                                @foreach(range(1, 31) as $day)
                                                    <option value="{{ $day }}" {{ $expiryDate->day == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <input type="hidden" name="job_key" value="{{ $selectedJob->ts_jobkey }}">
                                        <input type="hidden" name="role" value="{{ $role }}">
                                        <input type="hidden" name="expiration[hour]" value="23">
                                        <input type="hidden" name="expiration[minute]" value="59">
                                        <input type="hidden" name="expiration[second]" value="59">
                                    </div>

                                    <input type="hidden" name="model_name" value="Folders">
                                    <input type="hidden" name="model_field_name" value="folder_key">

                                </fieldset>
                            </div>

                            <div class="card-footer">
                                <button type="submit" id="submitButton" class="btn btn-primary" disabled>
                                    {{ __('Send invitation to join :school', ['school' => $selectedJob->ts_jobname]) }}
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('proofing.franchise.flash-error')
    @endif
@endsection

@section('js')
    <script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script>
    <script>
        // Initialize Select2
        $('#emailSelect').select2({
            placeholder: "-- Select Email --",
            allowClear: true
        });

        // When email selected → autofill names
        $('#emailSelect').on('change', function() {
            const option = this.selectedOptions[0];

            if (!option || option.value === "") {
                $('#first_name').val("");
                $('#last_name').val("");
                $('#submitButton').prop('disabled', true);
                return;
            }

            $('#first_name').val(option.dataset.firstname || "");
            $('#last_name').val(option.dataset.lastname || "");
            $('#submitButton').prop('disabled', false);
        });

        // Detect manual typing & show "Add user" message
        $(document).on('keyup', '.select2-search__field', function () {
            const typedEmail = $(this).val().toLowerCase();

            const availableEmails = $('#emailSelect option').map(function () {
                return this.value.toLowerCase();
            }).get();

            if (typedEmail && !availableEmails.includes(typedEmail)) {
                $('#emailNotFoundMsg').show();
            } else {
                $('#emailNotFoundMsg').hide();
            }
        });

        // Open User Create Page in new tab
        document.getElementById('createUserLink').addEventListener('click', function (e) {
            e.preventDefault();
            window.open(
                "{{ route('users.create') }}",
                '_blank',
                'noopener,noreferrer'
            );
        });
    </script>
@stop
