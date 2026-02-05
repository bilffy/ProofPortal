@extends('proofing.layouts.master')
@section('title', 'Proofing')

@section('css')
<!-- Latest compiled and minified CSS -->
{{-- <link href="{{ URL::asset('proofing-assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" /> --}}


<style>
    .d-none-archive {
        display: none !important
    }
    /* Hide the DataTables search input field */
        .dataTables_filter {
        display: none;
    }
    div.dataTables_wrapper div.dataTables_length select{
        width:75px;
    }
</style>
@stop

@section('content')

        @php
            use Illuminate\Support\Carbon;
            use Illuminate\Support\Facades\Crypt;
            $selectedSeasonId = session('selectedSeasonDashboard.ts_season_id');

            $encryptedSeasonId = $selectedSeasonId 
                ? Crypt::encryptString($selectedSeasonId) 
                : null;
        @endphp

        <div class="row">
            <div class="col-lg-12"> 
                <h1 class="page-header">
                    <span class="display-4">{{ __("Hello ") }} @if(isset($user)) {{ $user['firstname'] }}! @endif {{ __("Let's get you started.") }}</span>
                </h1>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <p class="lead">
                    You can perform the following Tasks...
                </p>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <a
                            @if(session('openSeason') === true && $encryptedSeasonId)
                                href="{{ route('dashboard.openSeason', $encryptedSeasonId) }}"
                            @else
                                href="{{ route('dashboard.viewSeason') }}"
                            @endif
                        >
                            <div class="card">
                                <div class="card-body p-3 clearfix">
                                    <i class="fa fa-mortar-board bg-secondary bg-inverse p-3 font-2xl mr-3 float-left"></i>
                                    <div class="h7 text-secondary mb-0 mt-3">{{ __('Open a Job') }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <p class="lead">
                    <?php echo __('Your Active Jobs...'); ?>
                </p>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                There are
                                <strong>@if($data['activeSyncJobs']){{$data['activeSyncJobs']->count()}}@endif</strong>
                                out of your
                                <strong>@if($data['totalSchoolCount']){{$data['totalSchoolCount']}}@endif</strong>
                                Jobs marked as active.
                                <span class="btn-link ml-2 show-hide-archived" style="cursor: pointer;" data-toggle-url="{{ route('dashboard.toggleArchived') }}">Show Archived Jobs</span>
                                <div class="row mt-3 mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <input type="search" class="form-control" id="searchData_filter" placeholder="Start typing a Job name to filter by...">
                                        </div>
                                        <div id="school-name-filter-feedback">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <p>Click headings to sort by that column.</p>
                                        <table id="searchData" class="table table-bordered table-sm">
                                            <thead>
                                                <!-- Table Headings-->
                                                <tr>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> ID
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> Job Key
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> Job
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> Season
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> Job Proofing Status
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> Folder Proofing Statuses
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> <span class="text-success fa fa-play"></span>
                                                        Proofing Start
                                                    </th>
                                                    <th scope="col">
                                                        <i class="fa fa-sort"></i> <span class="text-warning fa fa-circle"></span>
                                                        Proofing Warning
                                                    </th>
                                                    <th class="idx-review-due-start" scope="col">
                                                        <i class="fa fa-sort"></i> <span class="text-danger fa fa-stop"></span>
                                                        Proofing Due
                                                    </th>
                                                    <th scope="col">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($data['activeSyncJobs'] as $activeSyncJob)
                                                    @php
                                                        $jobFolderStatusCounts[$activeSyncJob->id] = [];
                                                        $jobKeyHash = Crypt::encryptString($activeSyncJob->ts_jobkey);
                                                        $jobIdHash = Crypt::encryptString($activeSyncJob->ts_job_id);
                                                
                                                        // Loop through each status
                                                        foreach ($data['statuses'] as $status) {
                                                            // Get the count of folders with this status for the current job
                                                            $folderCount = $activeSyncJob->folders->where('status_id', $status->id)->count();
                                                            // Store the folder count for this status
                                                            if ($folderCount !== 0) {
                                                                $jobFolderStatusCounts[$activeSyncJob->id][$status->status_external_name] = $folderCount;
                                                            }
                                                        }
                                                
                                                        $proofStart = $activeSyncJob->proof_start ? Carbon::parse($activeSyncJob->proof_start) : null;
                                                        $proofWarning = $activeSyncJob->proof_warning ? Carbon::parse($activeSyncJob->proof_warning) : null;
                                                        $proofDue = $activeSyncJob->proof_due ? Carbon::parse($activeSyncJob->proof_due) : null;
                                                        $statuses = collect($data['statuses']);
                                                        $completedId = $statuses->firstWhere('status_internal_name', 'COMPLETED')['id'] ?? null;
                                                    @endphp
                                                
                                                    @if(optional($activeSyncJob->reviewStatuses)->status_external_name != 'Archived')
                                                        <tr id="row-number-{{ $jobKeyHash }}" class="@if($activeSyncJob->job_status_id === $completedId) bg-success-light @endif">
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $activeSyncJob->ts_jobkey }}</td>
                                                            <td>{{ $activeSyncJob->ts_jobname }}</td>
                                                            <td>{{ $activeSyncJob->season_code }}</td>
                                                            <td>{{ optional($activeSyncJob->reviewStatuses)->status_external_name ?? '' }}</td>
                                                            <td>
                                                                @foreach($jobFolderStatusCounts[$activeSyncJob->id] as $statusName => $count)
                                                                    {{ $statusName }}: {{ $count }}<br>
                                                                @endforeach
                                                            </td>
                                                            <td class="@if($proofStart && Carbon::today()->gte($proofStart)) text-success alert-link @endif">
                                                                @if($proofStart){{ $proofStart->format('Y-m-d') }}@endif
                                                            </td>
                                                            <td class="@if($proofWarning && Carbon::today()->gte($proofWarning)) text-warning alert-link @endif">
                                                                @if($proofWarning){{ $proofWarning->format('Y-m-d') }}@endif
                                                            </td>
                                                            <td class="@if($proofDue && Carbon::today()->gte($proofDue)) text-danger alert-link @endif">
                                                                @if($proofDue){{ $proofDue->format('Y-m-d') }}@endif
                                                            </td>
                                                            <td>
                                                                <a href="#" id="open-job-link" data-job="{{ $jobIdHash }}">Open Job</a> |
                                                                <a href="{{ URL::signedRoute('config-job', ['hash' => $jobKeyHash]) }}">Configure</a> |
                                                                <a href="#" class="archive-job" data-job="{{ $jobIdHash }}" data-name="{{ $activeSyncJob->ts_jobname }}">Archive</a>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('js')
{{-- <script src="{{ URL::asset('proofing-assets/plugins/select2/js/select2.min.js')}}"></script> --}}
<!-- DataTables -->
<script src="{{ URL::asset('proofing-assets/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<!-- DataTables -->
<script src="{{ URL::asset('proofing-assets/js/moment.min.js')}}"></script>
<script src="{{ URL::asset('proofing-assets/js/franchise/ajaxcontrol.js') }}"></script>

@stop


