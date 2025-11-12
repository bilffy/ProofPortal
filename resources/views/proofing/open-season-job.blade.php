@extends('proofing.layouts.master')
@section('title', 'Proofing | Open Season')

@section('content')
@php
    use Illuminate\Support\Facades\Crypt;
@endphp


<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">{{ __('Jobs') }}</h1>
    </div>
</div>

{{-- Filter Section --}}
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="school-name-filter">{{ __('Filter Jobs') }}</label>
                    <input
                        class="form-control"
                        id="school-name-filter"
                        type="text"
                        name="school-name-filter"
                        placeholder="{{ __('Start typing a Job name to filter by...') }}"
                    >
                </div>
                <div id="school-name-filter-feedback"></div>
            </div>
        </div>
    </div>
</div>

{{-- Jobs Table --}}
<div class="row">
    <div class="col-lg-12">
        <div class="dashboard openSchool">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-align-justify"></i> {{ __('Please pick a Job to open.') }}
                </div>
                <div class="card-body">
                    <div id="jobs-container">
                        @if (count($tsJobs) > 0)
                            <table id="schools-table" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Job Key') }}</th>
                                        <th scope="col">{{ __('Name') }}</th>
                                        <th scope="col">{{ __('Season') }}</th>
                                        <th scope="col">{{ __('Actions') }}</th>
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
                                                data-school-name="{{ strtolower(__($tsJob->Name . ' (' . $selectedSeason->code ?? '' . ')')) }}"
                                            >
                                                <td class="idx-job-key">{{ $tsJob->JobKey }}</td>
                                                <td class="idx-name">{{ $tsJob->Name }}</td>
                                                <td class="idx-description">{{ $selectedSeason->code }}</td>
                                                <td class="actions">
                                                    <form action="#" method="POST" target="syncFrame" class="syncForm">
                                                        @csrf
                                                        <input type="hidden" name="job_key_hash" value="{{ $hash }}">
                                                        <button 
                                                        type="button" 
                                                        class="btn btn-link p-0 openJobBtn" 
                                                        data-job-id="{{ Crypt::encryptString($tsJob->JobID) }}" data-job-key="{{ Crypt::encryptString($tsJob->JobKey) }}">
                                                            {{ __('Open Job') }}
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
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // === CSRF Setup ===
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    
        // === Auto Refresh Configuration ===
        const targetUrl = "{{ url()->current() }}";
        const refreshDelay = 4000; // 4 seconds
        let refreshCounter = 1;
        const refreshLimit = 100;
        let schoolsContentTimer = setInterval(refreshJobs, refreshDelay);
    
        function refreshJobs() {
            $.ajax({
                dataType: 'html',
                type: 'GET',
                url: targetUrl,
                success: function (htmlResult) {
                    const newContent = $('<div>').html(htmlResult).find("#jobs-container").html();
    
                    // Smooth transition for content update
                    $("#jobs-container").fadeOut(150, function () {
                        $(this).html(newContent).fadeIn(150);
    
                        // Reapply filter if text is present
                        if (typeof filterSchoolsTable === "function") {
                            filterSchoolsTable();
                        }
                    });
                },
                error: function () {
                   // console.error("Failed to refresh jobs section.");
                }
            });
    
            if (refreshCounter++ >= refreshLimit) {
                clearInterval(schoolsContentTimer);
            }
        }
    
        // === Filter Jobs ===
        $('#school-name-filter').on('keyup', filterSchoolsTable);
    
        function filterSchoolsTable() {
            const filterByTextOriginal = $('#school-name-filter').val();
            const filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");
    
            if (filterByText.length >= 1) {
                $(".school").addClass("d-none");
                const foundSchools = $("[data-school-name*='" + filterByText + "']").removeClass("d-none");
                const foundCount = foundSchools.length;
                $("#school-name-filter-feedback").text("Found " + foundCount + " Jobs containing '" + filterByTextOriginal + "'.");
            } else {
                $(".school").removeClass("d-none");
                $("#school-name-filter-feedback").text("");
            }
        }
    
        // === Open Job Button (Delegated Click) ===
        $(document).on('click', '.openJobBtn', function (e) {
            e.preventDefault();
    
            const jobId = $(this).data('job-id');
            const jobKey = $(this).data('job-key');
    
            // console.log("Syncing job:", jobKey);
    
            // Step 1: Proxy sync with private server
            $.ajax({
                url: "{{ route('proxy.syncJob') }}",
                type: "POST",
                data: { jobKey: jobKey },
                success: function (proxyResponse) {
                    // console.log("Proxy sync response:", proxyResponse);
    
                    if (proxyResponse.success) {
                        // console.log("Proxy sync successful. Opening job:", jobId);
    
                        // Step 2: Open job session
                        $.ajax({
                            url: "{{ route('dashboard.openJob') }}",
                            type: "GET",
                            data: { jobId: jobId },
                            success: function (response) {
                                // console.log("OpenJob response:", response);
                                if (response.success) {
                                    // Stop refreshing before redirect
                                    clearInterval(schoolsContentTimer);
                                    window.location.href = "{{ url('/proofing') }}";
                                } else {
                                    // console.error("Failed to open job:", response);
                                }
                            },
                            error: function (xhr, status, error) {
                                // console.error("OpenJob AJAX error:", { status, error, responseText: xhr.responseText });
                            }
                        });
                    } else {
                        // console.error("Proxy sync failed:", proxyResponse);
                    }
                },
                error: function (xhr, status, error) {
                    // console.error("Proxy sync AJAX error:", { status, error, responseText: xhr.responseText });
                }
            });
        });
    });
    </script>
    
@endpush
