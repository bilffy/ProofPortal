
<div class="row">
    <div class="col-lg-12">
        <p>Changes are listed in chronological order - most recent changes at the bottom.</p>
        <table class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th class="idx-time-changed" scope="col">Changed</th>
                    <th class="idx-note" scope="col">Note</th>
                    <th class="idx-user" scope="col">By User</th>
                    <th class="idx-status" scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjectChanges as $subjectChange)
                    <tr>
                        <td class="idx-time-changed">{{ $subjectChange->change_datetime ? $subjectChange->change_datetime : '' }}</td>
                        <td class="idx-note">{{ $subjectChange->notes }}</td>
                        <td class="idx-user">{{ $subjectChange->user->firstname }} {{ $subjectChange->user->lastname }}</td>
                        <td class="idx-status">
                            @php
                                $textColour = '';
                                if ($subjectChange->approvalStatus === $approved) {
                                    $textColour = 'text-success';
                                } elseif ($subjectChange->approvalStatus === $rejected) {
                                    $textColour = 'text-danger';
                                } elseif ($subjectChange->approvalStatus === $awaitingApproval) {
                                    $textColour = 'text-warning';
                                } elseif ($subjectChange->approvalStatus === null) {
                                    $textColour = 'text-danger';
                                }
                            @endphp 
                            <span class="text-center {{$textColour}}">{{$subjectChange->statuses->status_external_name}}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
