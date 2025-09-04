<div class="row">
    <div class="col-12 m-auto">
        <p class="h5 lead mb-1"><strong>Email Notifications</strong></p>
        <p>Send email notifications at the specified events. You can select multiple people to receive the email notifications.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12 m-auto">
        <div class="form-group row text mb-0">
            <!-- Label for the checkbox -->
            <label for="notifications_enabled_checkbox" class="col-md-3 form-control-label">
                Enable Email Notifications
            </label>

            <?php
                $isReviewEnabledChecked = $selectedJob->notifications_enabled == true ? 'checked' : '';
            ?>

            <div class="col-md-9">
                <div class="form-group">
                    <div class="input-group" id="notifications_enabled_container">
                        <!-- Checkbox input -->
                        <input type="checkbox"
                               id="notifications_enabled_checkbox"
                               name="notifications_enabled"
                               class="form-check-input notifications_enabled ml-0" {{ $isReviewEnabledChecked }}>
                    </div>
                </div>
            </div>

            <!-- Error handling for the checkbox -->
            @if ($errors->has('notifications_enabled'))
                <div class="invalid-feedback">
                    {{ $errors->first('notifications_enabled') }}
                </div>
            @endif
        </div>
    </div>
</div>

<?php
    // // Handling display for the review matrix
    $reviewMatrixDisplay = $isReviewEnabledChecked ? '' : 'd-none';

    // Define the options for the select fields
    $selectOptionsEmailTo = [
        'franchise' => 'Franchise',
        'photocoordinator' => 'Photo Coordinator',
        'teacher' => 'Teacher',
    ];
?>

<div id="review-matrix" class="row {{ $reviewMatrixDisplay }}">
    <div class="col-md-12 m-auto">
        
        <!-- Proofing Timeline Section -->
        <form id='notification_email_form'>
            <div class="row text mt-3">
                <label class="col-md-3 form-control-label mt-1" for="proofing-timeline">Proofing Timeline</label>

                <div class="col-md-2">
                    <p class="mt-1 mb-1">Start Date:</p>
                    <select name="schools[proof_start][]" id="proofing-timeline-start-date" class="proofing-timeline-start-date" multiple data-model="jobs" data-field="proof_start">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['proof_start'][$key]) && $notificationsMatrix['schools']['proof_start'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">Warning Date:</p>
                    <select name="schools[proof_warning][]" id="proofing-timeline-warning-date" class="proofing-timeline-warning-date" multiple data-model="jobs" data-field="proof_warning">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['proof_warning'][$key]) && $notificationsMatrix['schools']['proof_warning'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">Due Date:</p>
                    <select name="schools[proof_due][]" id="proofing-timeline-due-date" class="proofing-timeline-due-date" multiple data-model="jobs" data-field="proof_due">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['proof_due'][$key]) && $notificationsMatrix['schools']['proof_due'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">Catchup  Date:</p>
                    <select name="schools[proof_catchup][]" id="proofing-timeline-catchup-date" class="proofing-timeline-catchup-date" multiple data-model="jobs" data-field="proof_catchup">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['proof_catchup'][$key]) && $notificationsMatrix['schools']['proof_catchup'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="style3">
            <!-- Job Status Actions Section -->

            <div class="row text mt-4">
                <label class="col-md-3 form-control-label mt-1" for="school-actions">School Status Change</label>

                <div class="col-md-2">
                    <p class="mt-1 mb-1">School Modified:</p>
                    <select name="schools[job_status_modified][]" id="school-actions-modified" class="school-actions-modified" multiple data-model="jobs" data-field="status-modified">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['job_status_modified'][$key]) && $notificationsMatrix['schools']['job_status_modified'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">School Completed:</p>
                    <select name="schools[job_status_completed][]" id="school-actions-completed" class="school-actions-completed" multiple data-model="jobs" data-field="status-completed">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['job_status_completed'][$key]) && $notificationsMatrix['schools']['job_status_completed'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">School Unlocked:</p>
                    <select name="schools[job_status_unlocked][]" id="school-actions-unlocked" class="school-actions-unlocked" multiple data-model="jobs" data-field="status-unlocked">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['schools']['job_status_unlocked'][$key]) && $notificationsMatrix['schools']['job_status_unlocked'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="style3">
            <!-- Folder Status Actions Section -->

            <div class="row text mt-4">
                <label class="col-md-3 form-control-label mt-1" for="folder-action">Folder Status Change</label>

                <div class="col-md-2">
                    <p class="mt-1 mb-1">Folder Modified:</p>
                    <select name="folders[folder_status_modified][]" id="folder-actions-modified" class="folder-actions-modified" multiple data-model="folders" data-field="status-modified">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['folders']['folder_status_modified'][$key]) && $notificationsMatrix['folders']['folder_status_modified'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">Folder Completed:</p>
                    <select name="folders[folder_status_completed][]" id="folder-actions-completed" class="folder-actions-completed" multiple data-model="folders" data-field="status-completed">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['folders']['folder_status_completed'][$key]) && $notificationsMatrix['folders']['folder_status_completed'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <p class="mt-1 mb-1">Folder Unlocked:</p>
                    <select name="folders[folder_status_unlocked][]" id="folder-actions-unlocked" class="folder-actions-unlocked" multiple data-model="folders" data-field="status-unlocked">
                        @foreach($selectOptionsEmailTo as $key => $value)
                            <option value="{{ $key }}" @if(isset($notificationsMatrix['folders']['folder_status_unlocked'][$key]) && $notificationsMatrix['folders']['folder_status_unlocked'][$key] == true) selected @endif>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>