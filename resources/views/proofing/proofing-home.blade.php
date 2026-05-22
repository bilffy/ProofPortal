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
    #searchData thead th, 
    .card-header strong {
        color: #808080;
    }
    #schools-table thead th {
        color: #808080;
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

        <!-- <div class="row">
            <div class="col-lg-12"> 
                <h1 class="page-header">
                    <span class="display-4">{{ __("Hello ") }} @if(isset($user)) {{ $user['firstname'] }}! @endif {{ __("Let's get you started.") }}</span>
                </h1>
            </div>
        </div> -->
        <div class="py-4 flex items-center justify-between">
            <h3 class="text-2xl">Proofing</h3>
        </div>


        {{-- <div class="row mt-4">
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
        </div> --}}

        <div class="row mt-4">
            <div class="col-12">
                <!-- <p class="lead">
                    <?php echo __('Your Unsynced Jobs...'); ?>
                </p> -->
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <!-- Combined into a single line wrapper -->
                                <div class="mb-4">
                                    <h5 class="text-black d-inline mr-2">Your Unsynced Jobs</h5>
                                    <span class="text-muted">
                                        - There are
                                        <strong>@if($tsJobs){{$tsJobs->count()}}@endif</strong>
                                        Jobs to be synced.
                                    </span>
                                </div>
                                <div class="row mt-3 mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <input type="search" class="form-control" id="schools-table_filter" placeholder="Start typing a Job name to filter by...">
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
                                        @if (count($tsJobs) > 0)
                                            <table id="schools-table" class="table table-bordered table-striped table-sm">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" style="width: 70px; max-width: 70px; white-space: nowrap;">
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
                                                            Actions
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                        @foreach ($tsJobs as $tsJob)
                                                            @php
                                                                $hash = Crypt::encryptString($tsJob->JobKey);
                                                            @endphp
                                                            <tr
                                                                id="{{ $tsJob->JobKey }}"
                                                                class="school"
                                                                data-job-key="{{ $tsJob->JobKey }}"
                                                                data-school-name="{{ strtolower(__($tsJob->Name . ' (' . $tsJob->code ?? '' . ')')) }}"
                                                            >
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td class="idx-job-key">{{ $tsJob->JobKey }}</td>
                                                                <td class="idx-name">{{ $tsJob->Name }}</td>
                                                                <td class="idx-description">{{ $tsJob->code }}</td>
                                                                <td class="actions">
                                                                    <form action="#" method="POST" target="syncFrame" class="syncForm">
                                                                        @csrf
                                                                        <input type="hidden" name="job_key_hash" value="{{ $hash }}">
                                                                        <button 
                                                                        type="button" 
                                                                        class="btn btn-link p-0 openJobBtn" 
                                                                        data-job-id="{{ Crypt::encryptString($tsJob->JobID) }}" data-job-key="{{ Crypt::encryptString($tsJob->JobKey) }}">
                                                                            {{ __('Sync Job') }}
                                                                        </button>
                                                                    </form>
                                                                    <iframe name="syncFrame" style="display:none;"></iframe>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="text-center py-3 text-muted fw-semibold">
                                                {{ __('No Jobs Found...') }}
                                            </div> 
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <!-- Combined into a single line wrapper -->
                                <div class="mb-4">
                                    <h5 class="text-black d-inline mr-2">Your Synced Jobs</h5>
                                    <span class="text-muted">
                                        - There are 
                                        <strong>@if($data['activeSyncJobs']){{$data['activeSyncJobs']->count()}}@endif</strong> 
                                        out of your 
                                        <strong>@if($data['totalSchoolCount']){{$data['totalSchoolCount']}}@endif</strong> 
                                        Jobs marked as active.
                                    </span>
                                    <span class="btn-link ml-2 show-hide-archived" style="cursor: pointer;" data-toggle-url="{{ route('dashboard.toggleArchived') }}">
                                        Show Archived Jobs
                                    </span>
                                </div>
                                <!-- Filter Row below -->
                                <div class="row mt-3 mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <input type="search" class="form-control" id="searchData_filter" placeholder="Start typing a Job name to filter by...">
                                        </div>
                                        <div id="school-name-filter-feedback"></div>
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
                                                        
                                                        // Get pre-calculated folder status counts from dashbaord data
                                                        $folderStatusCounts = $data['folderStatusCounts'][$activeSyncJob->ts_job_id] ?? collect([]);
                                                
                                                        // Loop through each status
                                                        foreach ($data['statuses'] as $status) {
                                                            // Find the count for this status
                                                            $statusData = $folderStatusCounts->firstWhere('status_id', $status->id);
                                                            $folderCount = $statusData ? $statusData->count : 0;
                                                            
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
                                                                <a href="#" id="open-job-link" data-job="{{ $jobKeyHash }}">Open Job</a> |
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
<script src="{{ URL::asset('proofing-assets/js/franchise/ajaxcontrol.js') }}?v={{ filemtime(public_path('proofing-assets/js/franchise/ajaxcontrol.js')) }}"></script>

@stop


