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
                <a @if($selectedJob) href="{{ URL::signedRoute('proofing.dashboard', ['hash' => Crypt::encryptString($selectedJob->ts_jobkey)]) }}" @else href="{{ route('proofing') }}" @endif class="btn btn-primary float-right pl-4 pr-4">
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
                                        @if ($preparedFolders->count() > 0)
                                        @foreach ($preparedFolders as $item)
                                            @php
                                                $folder = $item['folder'];
                                                $displayStatusId = $item['displayStatusId'];
                                                $isManuallyModified = $item['isManuallyModified'];
                                                
                                                $status = $reviewStatusesColours->firstWhere('id', $displayStatusId);
                                                $completedText = $displayStatusId != $noneStatus ? '<p class="small mt-1">(' . $status->status_external_name . ')</p>' : '';
                                                
                                                $hash = Crypt::encryptString($folder->ts_folderkey);
                                                $location = URL::signedRoute('my-folders-validate', ['hash' => $hash]);
                                            @endphp
                                            <div class="col-6 col-lg-3">
                                                @if ($folder->status_id != $completeStatus)
                                                    <a href="{{ $location }}">
                                                @endif

                                                <div class="card">
                                                    <div class="card-body p-3 clearfix">
                                                        <i class="fa fa-mortar-board bg-success p-3 font-2xl mr-3 float-left colour-scheme-reverse-{{ $displayStatusId }}"></i>
                                                        <div class="h5 text-success mb-0 mt-3 colour-scheme-{{ $displayStatusId }}">
                                                            {{ $folder->ts_foldername }}{!! $completedText !!}
                                                        </div>
                                                    </div>
                                                </div>

                                                @if ($folder->status_id != $completeStatus)
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
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
