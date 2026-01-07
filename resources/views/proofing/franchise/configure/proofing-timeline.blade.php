<div class="row">
    <div class="col-12 m-auto">
        <p class="h5 lead mb-1"><strong>{{ __('Proofing Timeline') }}</strong></p>
        <p>{{ __('Defines the key dates and times for the proofing process.') }}</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12 m-auto">
        <div class="form-group row text mb-0">
            <label for="review_due_start_picker" class="col-md-3 form-control-label">{{ __('Start Date') }}</label>
            <div class="col-md-9 text">
                <div class="form-group">
                    <div class="input-group date" id="datetime-picker-wrapper">
                        <input type="text" id="review_due_start_picker" class="form-control flatpickr-field" name="review_due_start" value="{{$review_due_start}}"/>
                        <span class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </span>
                    </div>
                </div>
            </div>
            @error('review_due_start')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 m-auto">
        <div class="form-group row text mb-0">
            <label for="review_due_warning_picker" class="col-md-3 form-control-label">{{ __('Warning Date') }}</label>
            <div class="col-md-9 text">
                <div class="form-group">
                    <div class="input-group date" id="review_due_warning_container">
                        <input type="text" id="review_due_warning_picker" class="form-control flatpickr-field" name="review_due_warning" value="{{$review_due_warning}}"/>
                        <span class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </span>
                    </div>
                </div>
            </div>
            @error('review_due_warning')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 m-auto">
        <div class="form-group row text mb-0">
            <label for="review_due_picker" class="col-md-3 form-control-label">{{ __('Due Date') }}</label>
            <div class="col-md-9 text">
                <div class="form-group">
                    <div class="input-group date" id="review_due_container">
                        <input type="text" id="review_due_picker" class="form-control flatpickr-field" name="review_due" value="{{$review_due}}"/>
                        <span class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </span>
                    </div>
                </div>
            </div>
            @error('review_due')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 m-auto">
        <div class="form-group row text mb-0">
            <label for="review_due_catchup_picker" class="col-md-3 form-control-label">{{ __('Catchup Date') }}</label>
            <div class="col-md-9 text">
                <div class="form-group">
                    <div class="input-group date" id="review_due_catchup_container">
                        <input type="text" id="review_due_catchup_picker" class="form-control flatpickr-field" name="review_due_catchup" value="{{$review_due_catchup}}"/>
                        <span class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </span>
                    </div>
                    <span class="text-warning">
                        Catchup notification emails will be sent on this date, please ensure this date is after the schools actual photo catchup day.
                    </span>
                </div>
            </div>
            @error('review_due_catchup')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>