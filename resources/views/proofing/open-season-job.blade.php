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
        // === Auto Refresh ===
        const targetUrl = "{{ url()->current() }}";
        const refreshDelay = 4000; 
        let refreshCounter = 1;
        const refreshLimit = 100;

        const schoolsContentTimer = setInterval(refreshJobs, refreshDelay);

        function refreshJobs() {
            $.ajax({
                dataType: 'html',
                type: 'GET',
                url: targetUrl,
                success: function(htmlResult) {
                    htmlResult = $('<div>').html(htmlResult);
                    const newContent = htmlResult.find("#jobs-container").html();
                    $("#jobs-container").html(newContent);
                    if (typeof filterSchoolsTable === "function") {
                        filterSchoolsTable();
                    }
                },
                error: function() {
                    console.error("Failed to refresh jobs section.");
                }
            });

            if (refreshCounter++ >= refreshLimit) {
                clearInterval(schoolsContentTimer);
            }
        }

        // === Filter Jobs ===
        $('#school-name-filter').on('keyup', filterSchoolsTable);

        function filterSchoolsTable() {
            var filterByTextOriginal = $('#school-name-filter').val();
            var filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");
            if (filterByText.length >= 1) {
                $(".school").addClass("d-none");
                var foundSchools = $("[data-school-name*='" + filterByText + "']").removeClass("d-none");
                var foundCount = foundSchools.length;
                $("#school-name-filter-feedback").text("Found " + foundCount + " Jobs containing the text '" + filterByTextOriginal + "'.");
            } else {
                $(".school").removeClass("d-none");
                $("#school-name-filter-feedback").text("");
            }
        }

        $('.openJobBtn').on('click', function (e) {
            e.preventDefault(); // prevent any default form or link behavior

            var jobId = $(this).data('job-id');
            var jobKey = $(this).data('job-key');

            // console.log("🟡 Click detected for job:", { jobId, jobKey });

            // Step 1: Call Laravel proxy route to sync job with private server
            $.ajax({
                url: "{{ route('proxy.syncJob') }}",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    jobKey: jobKey
                },
                beforeSend: function() {
                    // console.log("🔵 Sending sync request to proxy:", "{{ route('proxy.syncJob') }}", "with jobKey:", jobKey);
                },
                success: function(proxyResponse) {
                    console.log("response:", proxyResponse);

                    if (proxyResponse.success) {
                        // console.log("Proxy sync successful. Proceeding to open job...");
                        // Step 2: Call your Laravel openJob route to set session
                        $.ajax({
                            url: "{{ route('dashboard.openJob') }}",
                            type: "GET",
                            data: { jobId: jobId },
                            beforeSend: function() {
                                // console.log("Sending openJob request for jobId:", jobId);
                            },
                            success: function(response) {
                                // console.log("openJob response:", response);
                                if (response.success) {
                                    // console.log("Job opened. Redirecting to /proofing...");
                                    window.location.href = "{{ url('/proofing') }}";
                                } else {
                                    // console.error("openJob failed:", response);
                                }
                            },
                            error: function(xhr, status, error) {
                                // console.error("openJob AJAX error:", {
                                //     status: status,
                                //     error: error,
                                //     responseText: xhr.responseText
                                // });
                            }
                        });
                    } else {
                        // console.error("Proxy sync failed:", proxyResponse);
                    }
                },
                error: function(xhr, status, error) {
                    // console.error("Proxy sync AJAX error:", {
                    //     status: status,
                    //     error: error,
                    //     responseText: xhr.responseText
                    // });
                },
                complete: function() {
                    // console.log("Proxy sync AJAX call completed");
                }
            });
        });


    });
</script>
@endpush
