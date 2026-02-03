@extends('proofing.layouts.master')
@section('title', 'Validate')

@section('css')
    <link href="{{ asset('proofing-assets/custom/validate.css') }}" rel="stylesheet">
    <style>
        @foreach($reviewStatusesColours as $reviewStatusColour)
            .colour-scheme-{{ $reviewStatusColour->id }} {
                color: {{ $reviewStatusColour->colour_code }} !important;
            }

            .colour-scheme-reverse-{{ $reviewStatusColour->id }} {
                color: #ffffff;
                background-color: {{ $reviewStatusColour->colour_code }} !important;
            }
        @endforeach
    </style>
@stop

@section('content')
    @php      
        use Illuminate\Support\Facades\URL;
        use Illuminate\Support\Facades\Crypt;
        use Carbon\Carbon;
        $activeText = '<span class="text-success"><i class="fa fa-check fa-lg"></i></span> School is marked for Active Syncing. Data is updated from Timestone every hour.';
        $inactiveText = '<span class="text-warning"><i class="fa fa-exclamation-triangle fa-lg"></i></span> School is not marked for Active Syncing.';
    @endphp

    @if(Session::has('selectedJob') && Session::has('selectedSeason'))
        <div class="row mt-2">
            <div class="col-12">
                <p class="display-4">
                    {{ __('What would you like to Proof?') }}
                </p>
            </div>
            <div class="col-12 mb-3">
                <a href="{{route('proofing')}}" class="btn btn-primary float-right pl-4 pr-4">
                    {{ __('Done') }}
                </a>
            </div>
            <div class="col-12">
                @if (isset($selectedJob))
                    <div id="{{ $selectedJob->ts_jobkey }}" class="row school"
                        data-job-key="{{ $selectedJob->ts_jobkey }}"
                        data-school-name="{{ strtolower(sprintf('%s (%s)', $selectedJob->ts_jobname, $selectedSeason->code)) }}">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-header">
                                    <legend>{{ sprintf('%s (%s)', $selectedJob->ts_jobname, $selectedSeason->code) }}</legend>
                                    @if($selectedJob->jobsync_status_id == $syncStatus)
                                        {!! $activeText !!}

                                    @elseif($selectedJob->jobsync_status_id == $unsyncStatus)
                                        {!! $inactiveText !!}
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @inject('proofingChangelogService', 'App\Services\Proofing\ProofingChangelogService')
                                        @inject('emailService', 'App\Services\Proofing\emailService')
                                        @if ($selectedFolders->count() > 0)
                                        @foreach ($selectedFolders as $folder)
                                            <div class="col-6 col-lg-3">
                                                @php
                                                    $none_modified = 0;
                                                    $completedText = '';

                                                    $folderSubjectKeys = $folder->subjects?->pluck('ts_subjectkey') ?? collect();
                                                    /* $folderAttachedSubjectIds = $folder->attachedsubjects?->pluck('ts_subject_id') ?? collect();
                                                    $attachedSubjectKeys = App\Models\Subject::whereIn('ts_subject_id', $folderAttachedSubjectIds)
                                                        ->pluck('ts_subjectkey'); */

                                                    $hasChanges =
                                                        ($getChangelog->where('keyvalue', $folder->ts_folderkey)->count() > 0) ||
                                                        ($proofingChangelogService->subjectChangedFolderCount($folder->id) > 0) ||
                                                        ($folderSubjectKeys->filter(function ($subjectKey) use ($getChangelog) {
                                                            return $getChangelog->where('keyvalue', $subjectKey)->count() > 0;
                                                        })->count() > 0); 
                                                        /* ||
                                                        ($attachedSubjectKeys->filter(function ($subjectKey) use ($getChangelog) {
                                                            return $getChangelog->where('keyvalue', $subjectKey)->count() > 0;
                                                        })->count() > 0); */

                                                    if ($folder->status_id === $noneStatus && $hasChanges) {
                                                        $none_modified = 1;
                                                        $status = $reviewStatusesColours->firstWhere('id', $modifiedStatus);
                                                        $completedText = '<p class="small mt-1">(' . $status->status_external_name . ')</p>';
                                                        if($selectedJob->job_status_id === $noneStatus) {
                                                                $selectedJob->update([
                                                                    'job_status_id' => $modifiedStatus,
                                                                ]);
                                                                $folder->update([
                                                                    'status_id' => $modifiedStatus,
                                                                ]);
                                                            $emailService->saveEmailContent($folder->job->ts_jobkey, 'job_status_modified', Carbon::now(), $modifiedStatus);
                                                            $emailService->saveEmailFolderContent($folder->ts_folder_id, 'folder_status_modified', Carbon::now(), $modifiedStatus);                                                        
                                                        }
                                                    } elseif ($folder->status_id === $noneStatus && !$hasChanges) {
                                                        $none_modified = 0;
                                                        $completedText = '';
                                                    } else {
                                                        $none_modified = 0;
                                                        $status = $reviewStatusesColours->firstWhere('id', $folder->status_id);
                                                        $completedText = '<p class="small mt-1">(' . $status->status_external_name . ')</p>';
                                                    }

                                                    $hash = Crypt::encryptString($folder->ts_folderkey);
                                                    $location = URL::signedRoute('my-folders-validate', ['hash' => $hash]);
                                                @endphp

                                                @if ($folder->status_id !== $completeStatus)
                                                    <a href="{{ $location }}">
                                                @endif

                                                <div class="card">
                                                    <div class="card-body p-3 clearfix">
                                                        <i class="fa fa-mortar-board bg-success p-3 font-2xl mr-3 float-left colour-scheme-reverse-{{ $none_modified === 1 ? $modifiedStatus : $folder->status_id }}"></i>
                                                        <div class="h5 text-success mb-0 mt-3 colour-scheme-{{ $none_modified === 1 ? $modifiedStatus : $folder->status_id }}">
                                                            {{ $folder->ts_foldername }}{!! $completedText !!}
                                                        </div>
                                                    </div>
                                                </div>

                                                @if ($folder->status_id !== $completeStatus)
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach

                                        {{--
                                        @else
                                            <div class="col-6 col-lg-3">
                                                <a href="">{{ __('Click Here') }}</a>
                                                to add this School to Active Syncing.
                                            </div>
                                        --}}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else

    @include('proofing.franchise.flash-error')

    @endif
@endsection

@section('js')

@stop
