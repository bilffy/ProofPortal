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
    $defaultOptions = [
        'class' => $inputClass,
    ];

    $dtActivationSelected = [
        'year' => 'Year',
        'month' => 'Month',
        'day' => 'Day',
    ];

    $dtExpirationSelected = [
        'year' => 'Year',
        'month' => 'Month',
        'day' => 'Day',
    ];

    $foldersTmp = [];
    foreach ($selectedJob->folders as $folder) {
        $foldersTmp[$folder->ts_folderkey] = $folder->ts_foldername;
    }
@endphp

<div class="row">
    <div class="col-12 mb-3">
        @php
            $btnAttrCancel = "btn btn-primary float-right pl-4 pr-4";
            $urlDone = (strpos(request()->headers->get('referer'), $role) !== false) ? route('proofing') : url()->previous();
            if($role === 'photocoordinator'){
                $title = 'Photo-Coordinator';
            }else if($role === 'teacher'){
                $title = 'Teacher';
            }
        @endphp

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
                            <div class="form-group row">
                                <label for="email" class="{{ $labelClass }}">Email</label>
                                <div class="col-md-9">
                                    <input type="email" name="email" id="email" class="{{ $inputClass }}">
                                    <div id="feedback"></div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="first_name" class="{{ $labelClass }}">First Name</label>
                                <div class="col-md-9">
                                    <input type="text" name="first_name" id="first_name" class="{{ $inputClass }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="last_name" class="{{ $labelClass }}">Last Name</label>
                                <div class="col-md-9">
                                    <input type="text" name="last_name" id="last_name" class="{{ $inputClass }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="mobile" class="{{ $labelClass }}">Mobile</label>
                                <div class="col-md-9">
                                    <input type="tel" name="mobile" id="mobile" class="{{ $inputClass }}">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="folder" class="{{ $labelClass }}">Class/Group</label>
                                <div class="col-md-9">
                                    <select name="folder" id="folder" class="{{ $inputClass }}">
                                        <option value="*">All Folders</option>
                                        @foreach($foldersTmp as $folderKey => $folderName)
                                            <option value="{{ $folderKey }}">{{ $folderName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row datetime">
                                <label for="activation" class="{{ $labelClass }}">Activation Date</label>
                                <div class="col-md-auto">
                                    <select name="activation[year]" title="Year">
                                        @for($i = now()->year + 5; $i >= now()->year - 5; $i--)
                                            <option value="{{ $i }}" {{ now()->year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="col-md-auto">
                                    <select name="activation[month]" title="Month">
                                        @foreach(range(1, 12) as $month)
                                            <option value="{{ $month }}" {{ now()->month == $month ? 'selected' : '' }}>
                                                {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-auto">
                                    <select name="activation[day]" title="Day">
                                        @foreach(range(1, 31) as $day)
                                            <option value="{{ $day }}" {{ now()->day == $day ? 'selected' : '' }}>{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                    <input type="hidden" name="activation[hour]" value="00">
                                    <input type="hidden" name="activation[minute]" value="00">
                                    <input type="hidden" name="activation[second]" value="00">
                            </div>

                            <div class="form-group row datetime">
                                <label for="expiration" class="{{ $labelClass }}">Expiration Date</label>
                                <div class="col-md-auto">
                                    <select name="expiration[year]" title="Year">
                                        @for($i = $expiryDate->year; $i >= now()->year - 5; $i--)
                                            <option value="{{ $i }}" {{ $expiryDate->year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="col-md-auto">
                                    <select name="expiration[month]" title="Month">
                                        @foreach(range(1, 12) as $month)
                                            <option value="{{ $month }}" {{ $expiryDate->month == $month ? 'selected' : '' }}>
                                                {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-auto">
                                    <select name="expiration[day]" title="Day">
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
                        <button type="submit" id="submitButton" disabled="true" class="btn btn-primary">{{ __('Send invitation to join :school', ['school' => $selectedJob->ts_jobname]) }}</button>
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
        $('#folder').select2();
        const emailArrayRaw = @json($emails ?? []);
        const emailArray = Array.isArray(emailArrayRaw) ? emailArrayRaw : [];
        
        document.addEventListener("DOMContentLoaded", function () {
            const emailInput = document.getElementById("email");
            const feedback = document.getElementById("feedback");
            const submitButton = document.getElementById("submitButton");

            emailInput.addEventListener("keyup", function () {
                const typedEmail = emailInput.value.trim();

                if (typedEmail === "") {
                    feedback.textContent = "";
                    submitButton.disabled = true;
                    return;
                }

                if (!emailArray.includes(typedEmail)) {
                    feedback.textContent = "Email does not exist.";
                    feedback.style.color = "red";
                    submitButton.disabled = true;
                } else {
                    feedback.textContent = "";
                    submitButton.disabled = false;
                }
            });
        });
    </script>
@stop
