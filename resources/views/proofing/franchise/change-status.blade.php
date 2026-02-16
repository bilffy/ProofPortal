
@extends('proofing.layouts.master')
@section('title', 'Change Proofing Statuses')

@section('css')

@stop

@section('content')

@if(Session::has('selectedJob') && Session::has('selectedSeason'))
    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
            <div class="reports">
                <div class="card">
                    <div class="card-header">
                        <legend>{{ __('Change Proofing Statuses') }}</legend>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            Alter the Review Status of the Job.
                        </p>
                        <table class="table table-bordered table-striped table-sm mb-5">
                            <thead>
                            <tr>
                                <th scope="col">{{ __('Job Name') }}</th>
                                <th scope="col" class="text-center">{{ __('Current Status') }}</th>
                                <th scope="col" class="text-center">{{ __('Change Job Status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="align-middle">{{ $selectedJob->ts_jobname }}</td>
                                <td class="align-middle text-center">{{ $selectedJob->reviewStatuses->status_external_name }}</td>
                                <td class="align-middle text-center">
                                    @php 
                                        $counter = 0; 
                                        $jobIdHash = Crypt::encryptString($selectedJob->ts_job_id);
                                    @endphp
                                    @foreach ($reviewStatusesNewList as $statusKey => $reviewStatusesNewItem)
                                        @php
                                            $confirmMsg = __('Are you sure you want to change the status from :currentStatus to :newStatus?', [
                                                'currentStatus' => $selectedJob->reviewStatuses->status_external_name,
                                                'newStatus' => $reviewStatusesNewItem
                                            ]);
                                        @endphp
                                        
                                        @if(
                                            $statusKey != $selectedJob->reviewStatuses->id &&
                                            ($statusKey != $completeStatus || ($selectedJob->reviewStatuses->id != $locked && $selectedJob->reviewStatuses->id != $archived))
                                        )
                                            @if ($counter > 0)
                                                | 
                                            @endif
                                            <a href="#" class="change-job-status" data-job = "{{ $jobIdHash }}" data-value="{{ $statusKey }}" data-confirm-msg="{{ $confirmMsg }}">{{ __($reviewStatusesNewItem) }}</a>
                                            @php $counter++; @endphp
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        
                        @if($selectedJob->reviewStatuses->id === $locked || $selectedJob->reviewStatuses->id === $archived)
                            <p class="mb-3"><span class="alert alert-warning p-1">To Unlock the Folder, you need to unlock the Job.</span></p>
                        @else
                            <p class="mb-1">Alter the Review Status of the Folder.</p>
                        
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-3">
                                            <select id="folder_status" name="folder_status" class="form-control">
                                                <option value="">--Please Select Folder Status--</option>
                                                @foreach ($reviewStatusesNewList as $key => $reviewStatusesNewItem)
                                                    <option value="{{ $key }}">{{ $reviewStatusesNewItem }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <a href="#" class="btn btn-primary" data-job = "{{ $jobIdHash }}" id="change-folder-status">Apply</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                            
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                            <tr>
                                @if($selectedJob->reviewStatuses->id !== $locked && $selectedJob->reviewStatuses->id !== $archived)
                                    <th scope="col" style="width: 50px;" class="text-center"><input type="checkbox" id="check_all" class="mt-2 ml-0 mr-0"></th>
                                @endif
                                <th scope="col">{{ __('Folder Name') }}</th>
                                <th scope="col" class="text-center">{{ __('Current Status') }}</th>
                                {{-- <th scope="col" class="text-center">{{ __('Change Folder Status') }}</th> --}}
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($selectedFolders as $folder)
                                <tr>
                                    @if($selectedJob->reviewStatuses->id !== $locked && $selectedJob->reviewStatuses->id !== $archived)
                                        <td class="align-middle text-center"><input type="checkbox" value="{{ Crypt::encryptString($folder->ts_folder_id) }}" class="mt-2 ml-0 mr-0 folder-checkbox"></td>
                                    @endif
                                    <td class="align-middle">{{ $folder->ts_foldername }}</td>
                                    <td class="align-middle text-center">{{ $folder->reviewStatuses->status_external_name }}</td>
                                    {{-- <td class="align-middle text-center">
                                        @php $reviewStatusCounter = 0; @endphp
                                        @foreach ($reviewStatusesNewList as $statusKey => $reviewStatusesNewItem)
                                            @php
                                                $confirmFolderMsg = __('Are you sure you want to change the status from :currentStatus to :newStatus?', [
                                                    'currentStatus' => $folder->reviewStatuses->status_external_name,
                                                    'newStatus' => $reviewStatusesNewItem
                                                ]);
                                            @endphp

                                            @if(
                                                $folder->reviewStatuses->status_external_name !== $reviewStatusesNewItem &&
                                                ($statusKey !== $completeStatus || ($folder->reviewStatuses->id !== $locked && $folder->reviewStatuses->id !== $archived))
                                            )
                                                @if ($reviewStatusCounter > 0)
                                                    | 
                                                @endif
                                                <a href="#" class="change-single-folder-status" data-job = "{{ $jobKey }}" data-folder = "{{ Crypt::encryptString($folder->ts_folder_id) }}" data-value="{{ $statusKey }}"  data-confirm-msg="{{ $confirmFolderMsg }}">{{ __($reviewStatusesNewItem) }}</a>
                                                @php $reviewStatusCounter++; @endphp
                                            @endif
                                        @endforeach
                                    </td> --}}
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <a @if($selectedJob) href="{{ URL::signedRoute('proofing.dashboard', ['hash' => Crypt::encryptString($selectedJob->ts_jobkey)]) }}" @else href="{{ route('proofing') }}" @endif class="btn btn-primary float-right">{{ __('Done') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    @include('proofing.franchise.flash-error')
@endif
@endsection

@section('js')
<script src="{{ URL::asset('proofing-assets/js/proofing/checkbox_proofing_status.js') }}"></script>
@stop
