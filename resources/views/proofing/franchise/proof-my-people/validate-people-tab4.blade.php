<fieldset id="ValidateStep4">
    <div class="row" id="log">
        <div class="questions col-xs-12 col-sm-12 col-md-8 col-lg-8 col-xl-6 m-auto">
            <div class="form-group row slate-board">
                <div class="col-md-12">
                    <p class="lead">Once you are happy to finalise this proof,
                        please select the 'Mark As Complete' option.
                        Please note that proofing changes will not be made
                        until this option has been selected.</p>
                </div>
                <div class="col-md-12">
                    @php
                        $options = [
                            'save-for-later' => 'Save Progress',
                            'mark-as-complete' => 'Mark As Complete'
                        ];
                    @endphp

                    <form method="POST" class="f1" action="{{ route('submit-proof') }}">
                        @csrf
                        @foreach($options as $value => $label)
                            <div class="row">
                                <input type="hidden" name="folderHash" id="folderHash" value="{{$folderhash}}">
                                <div class="col-md-1 m-0 pt-2 pl-5">
                                    <input type="radio" name="submit-proof" id="submit-proof-{{ $value }}" value="{{ $value }}">
                                </div>
                                <div class="col-md-10 m-0 pt-1 pl-0">
                                    <label class="lead col-md-9 mb-0" for="submit-proof-{{ $value }}">{{ $label }}</label>
                                </div>

                                <div class="col-md-10 offset-md-1">
                                    @if($value == 'save-for-later')
                                        <ul class="ml-4 mb-0">
                                            <li class="m-0">You can make more changes at a later stage.</li>
                                            <li class="m-0">You will be sent reminders to complete the Proofing of this Class.</li>
                                        </ul>
                                    @elseif($value == 'mark-as-complete')
                                        <ul class="ml-4 mb-0">
                                            <li class="m-0">You will not be able to make any further changes.</li>
                                            <li class="m-0">The School Photo Coordinator will be sent a notification that you have completed
                                                Proofing this Class.
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="f1-buttons">
        <button type="button" id="submitPrevious" class="btn btn-previous btn-secondary">Previous</button>
        <button id="saveProofing" type="button" class="btn btn-submit btn-primary d-none">Save Progress</button>
        <button id="completeProofing" type="button" class="btn btn-submit btn-primary d-none">Mark As Complete</button>
        <button id="submitProofingDisabled" type="button" class="btn btn-next-disabled btn-secondary">Submit</button>
    </div>
</fieldset>
