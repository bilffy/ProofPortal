@extends('proofing.layouts.master')

@section('title', 'Emails')

@section('css')
<style>
    #invitation-messages-table thead th {
        color: #808080;
    }
    #invitation-messages-table .btn-link {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">{{ __('Emails') }}</h3>
    </div>

    <x-tabs.tabContainer tabsWrapper="emailsCategoryTabsContent">
        <x-tabs.tab id="tab-proofing" :isActive="true">{{ __('Proofing') }}</x-tabs.tab>
        <x-tabs.tab id="tab-invitation" :isActive="false">{{ __('User Invitation') }}</x-tabs.tab>
    </x-tabs.tabContainer>

    <x-tabs.tabContentContainer id="emailsCategoryTabsContent">
        <x-tabs.tabContent id="tab-proofing">
            <div class="card">
                        <div class="card-header">
                            <i class="fa fa-envelope"></i> {{ __('Select a Job') }}
                        </div>
                        <div class="card-body">
                            @if ($jobs->isEmpty())
                                <p class="mb-0">{{ __('No Jobs have been synced for proofing yet.') }}</p>
                            @else
                                <div class="report-text mb-3">
                                    {{ __('Please select a Job') }}
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-md-1 col-sm-12 align-self-center">
                                                Filter:
                                            </div>
                                            <div class="col-md-5 col-sm-12">
                                                <input class="form-control" id="choice-name-filter" type="text"
                                                    placeholder="{{ __('Start typing to filter by...') }}">
                                            </div>
                                            <div class="col-md-6 col-sm-12 align-self-center" id="choice-name-filter-feedback">
                                                &nbsp;
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('School') }}</th>
                                            <th scope="col">{{ __('Job') }}</th>
                                            <th scope="col" class="text-center">{{ __('Select') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jobs as $job)
                                            @php
                                                $title = !empty($job->ts_season_id) && isset($seasonList[$job->ts_season_id])
                                                    ? __(':name (:season)', ['name' => $job->ts_jobname, 'season' => $seasonList[$job->ts_season_id]])
                                                    : $job->ts_jobname;
                                                $encryptedTsJobId = Crypt::encryptString($job->ts_job_id);
                                                $choiceName = strtolower(trim($title . ' ' . ($job->school_name ?? '')));
                                            @endphp
                                            <tr class="choice" data-choice-name="{{ $choiceName }}">
                                                <td class="align-middle">{{ $job->school_name ?? '—' }}</td>
                                                <td class="align-middle">{{ $title }}</td>
                                                <td class="align-middle text-center">
                                                    <a href="{{ route('emails.show', ['tsJobId' => $encryptedTsJobId]) }}" class="btn btn-link">
                                                        {{ __('Select') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
            </div>
        </x-tabs.tabContent>

        <x-tabs.tabContent id="tab-invitation">
            <div class="card">
                        <div class="card-header">
                            <i class="fa fa-envelope"></i> {{ __('Select a School') }}
                        </div>
                        <div class="card-body">
                            @if ($invitationSchools->isEmpty())
                                <p class="mb-0">{{ __('No user invitation emails have been sent yet.') }}</p>
                            @else
                                <div class="report-text mb-3">
                                    {{ __('Please select a School') }}
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-md-1 col-sm-12 align-self-center">
                                                Filter:
                                            </div>
                                            <div class="col-md-5 col-sm-12">
                                                <input class="form-control" id="invitation-choice-name-filter" type="text"
                                                    placeholder="{{ __('Start typing to filter by...') }}">
                                            </div>
                                            <div class="col-md-6 col-sm-12 align-self-center" id="invitation-choice-name-filter-feedback">
                                                &nbsp;
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('School') }}</th>
                                            <th scope="col" class="text-center">{{ __('Select') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invitationSchools as $school)
                                            @php
                                                $encryptedSchoolId = Crypt::encryptString($school->id);
                                            @endphp
                                            <tr class="invitation-choice" data-choice-name="{{ strtolower($school->name) }}">
                                                <td class="align-middle">{{ $school->name }}</td>
                                                <td class="align-middle text-center">
                                                    <a href="{{ route('emails.invitations.show', ['schoolId' => $encryptedSchoolId]) }}" class="btn btn-link">
                                                        {{ __('Select') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
            </div>
        </x-tabs.tabContent>
    </x-tabs.tabContentContainer>

    @include('proofing.franchise.emails._modals')
@endsection

@section('js')
<script>
$(document).ready(function () {
    $('#choice-name-filter').on('keyup', filterChoicesTable);

    function filterChoicesTable() {
        let filterByTextOriginal = $('#choice-name-filter').val();
        let filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");

        if (filterByText.length >= 1) {
            $(".choice").addClass("d-none");
            let allMatches = $("[data-choice-name*='" + filterByText + "']");
            allMatches.removeClass("d-none");
            $("#choice-name-filter-feedback").text(
                "Found " + allMatches.length + " records containing '" + filterByTextOriginal + "'."
            );
        } else {
            $(".choice").removeClass("d-none");
            $("#choice-name-filter-feedback").text("");
        }
    }

    $('#invitation-choice-name-filter').on('keyup', filterInvitationChoicesTable);

    function filterInvitationChoicesTable() {
        let filterByTextOriginal = $('#invitation-choice-name-filter').val();
        let filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");

        if (filterByText.length >= 1) {
            $(".invitation-choice").addClass("d-none");
            let allMatches = $(".invitation-choice[data-choice-name*='" + filterByText + "']");
            allMatches.removeClass("d-none");
            $("#invitation-choice-name-filter-feedback").text(
                "Found " + allMatches.length + " records containing '" + filterByTextOriginal + "'."
            );
        } else {
            $(".invitation-choice").removeClass("d-none");
            $("#invitation-choice-name-filter-feedback").text("");
        }
    }

    // Tab switching itself is handled by Flowbite's Tabs component (initialised
    // app-wide from data-tabs-toggle/data-tabs-target attributes - see
    // x-tabs.tabContainer/x-tabs.tab). This just keeps the address bar hash in
    // sync so links can deep-link to a specific tab.
    $('#tab-proofing-tab, #tab-invitation-tab').on('click', function () {
        const target = $(this).data('tabs-target');
        if (history.replaceState) {
            history.replaceState(null, '', target);
        } else {
            window.location.hash = target;
        }
    });

    // Deep-link support: /emails#tab-invitation lands directly on that tab
    // (used by the "Back to Schools" link on the per-school invitation page).
    if (window.location.hash) {
        $(window.location.hash + '-tab').trigger('click');
    }
});
</script>

@include('proofing.franchise.emails._view_resend_scripts')
@endsection
