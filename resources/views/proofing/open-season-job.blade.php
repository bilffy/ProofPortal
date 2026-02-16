@extends('proofing.layouts.master')
@section('title', 'Proofing | Open Season')


@section('content')
@php
    use Illuminate\Support\Facades\Crypt;
@endphp

<style>
    #pageLoader {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loader-box {
        text-align: center;
        color: #fff;
    }
</style>
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
                <div id="pageLoader" style="display:none;">
                    <div class="loader-box">
                        <div class="spinner-border text-light" role="status"></div>
                        <p class="mt-3">Opening Job, please wait…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="pageLoader" style="display:none;">
    <div class="loader-box">
        <div class="spinner-border text-light" role="status"></div>
        <p class="mt-3">Opening job, please wait…</p>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {

        function showLoader() { $('#pageLoader').fadeIn(150); } function hideLoader() { $('#pageLoader').fadeOut(150); }
        // === Auto Refresh ===
        const targetUrl = "{{ url()->current() }}";
        const refreshDelay = 4000;
        let refreshCounter = 1;
        const refreshLimit = 100;
        const refreshTimer = setInterval(refreshJobs, refreshDelay);
    
        function refreshJobs() {
            $.ajax({
                dataType: 'html',
                type: 'GET',
                url: targetUrl,
                success: function (htmlResult) {
                    const newContent = $('<div>').html(htmlResult).find("#jobs-container").html();
                    $("#jobs-container").html(newContent);
                    if (typeof filterSchoolsTable === "function") filterSchoolsTable();
                },
                error: function () {
                    console.error("Failed to refresh jobs section.");
                }
            });
    
            if (refreshCounter++ >= refreshLimit) clearInterval(refreshTimer);
        }
    
        // === Filter Jobs ===
        $('#school-name-filter').on('keyup', filterSchoolsTable);
    
        function filterSchoolsTable() {
            const searchText = $('#school-name-filter').val().toLowerCase().replace("'", "\\'");
            if (searchText.length > 0) {
                $(".school").addClass("d-none");
                const matched = $("[data-school-name*='" + searchText + "']").removeClass("d-none");
                $("#school-name-filter-feedback").text(`Found ${matched.length} Jobs containing "${searchText}".`);
            } else {
                $(".school").removeClass("d-none");
                $("#school-name-filter-feedback").text("");
            }
        }
    
        // === Open Job Button ===
        $(document).on('click', '.openJobBtn', function (e) {
            e.preventDefault();
    
            const $btn   = $(this);
            const jobId = $(this).data('job-id');
            const jobKey = $(this).data('job-key');

            // prevent double click
            if ($btn.data('loading')) return;
            $btn.data('loading', true);

            showLoader();                    // SHOW LOADER
            $btn.prop('disabled', true);
    
            $.ajax({
                url: "{{ route('proxy.syncJob') }}",
                type: "POST",
                data: { _token: '{{ csrf_token() }}', jobKey },
                success: function (proxyResponse) {
                    if (proxyResponse.success) {
                        $.ajax({
                            url: "{{ route('dashboard.openJob') }}",
                            type: "GET",
                            data: { jobKey },
                            success: function (response) {
                                if (response.success) {
                                    if (response.redirectUrl) {
                                        window.location.href = response.redirectUrl;
                                    } else {
                                        window.location.href = "{{ url('/proofing') }}";
                                    }
                                }
                            }
                        });
                    } else {
                        console.error("Proxy sync failed:", proxyResponse);
                    }
                },
                error: function () {
                    console.error("Proxy sync AJAX error.");
                }
            });
        });
    });
    </script>
    
@endpush
