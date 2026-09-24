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

                                <div class="paginator mt-3">
                                    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-start">
                                        <div class="flex gap-4">
                                            <button type="button" id="choice-prev-page" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-gray-700">
                                                &lt; {{ __('Previous') }}
                                            </button>
                                            <button type="button" id="choice-next-page" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-gray-700">
                                                {{ __('Next') }} &gt;
                                            </button>
                                        </div>
                                    </nav>
                                    <p class="mt-2 text-muted" id="choice-pagination-summary"></p>
                                </div>
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

                                <div class="paginator mt-3">
                                    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-start">
                                        <div class="flex gap-4">
                                            <button type="button" id="invitation-choice-prev-page" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-gray-700">
                                                &lt; {{ __('Previous') }}
                                            </button>
                                            <button type="button" id="invitation-choice-next-page" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-gray-700">
                                                {{ __('Next') }} &gt;
                                            </button>
                                        </div>
                                    </nav>
                                    <p class="mt-2 text-muted" id="invitation-choice-pagination-summary"></p>
                                </div>
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
    // Shared filter + client-side pagination (10 rows/page) for the Jobs and
    // Schools choice tables - both tables are rendered in full up-front (no
    // AJAX here), so paging and the existing text filter both just show/hide
    // <tr> elements with the "d-none" class.
    function setupPaginatedChoiceTable(options) {
        const rowSelector = options.rowSelector;
        const filterInputSelector = options.filterInputSelector;
        const filterFeedbackSelector = options.filterFeedbackSelector;
        const prevBtnSelector = options.prevBtnSelector;
        const nextBtnSelector = options.nextBtnSelector;
        const summarySelector = options.summarySelector;
        const perPage = options.perPage || 10;

        let currentPage = 1;

        function matchingRows() {
            const filterByTextOriginal = $(filterInputSelector).val() || '';
            const filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");

            if (filterByText.length >= 1) {
                return $(rowSelector).filter(function () {
                    return ($(this).data('choice-name') || '').toString().indexOf(filterByText) !== -1;
                });
            }

            return $(rowSelector);
        }

        function render() {
            const filterByTextOriginal = $(filterInputSelector).val() || '';
            const matches = matchingRows();
            const total = matches.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            $(rowSelector).addClass('d-none');

            const start = (currentPage - 1) * perPage;
            const pageRows = matches.slice(start, start + perPage);
            pageRows.removeClass('d-none');

            if (filterFeedbackSelector) {
                if (filterByTextOriginal.length >= 1) {
                    $(filterFeedbackSelector).text(
                        "Found " + total + " records containing '" + filterByTextOriginal + "'."
                    );
                } else {
                    $(filterFeedbackSelector).text('');
                }
            }

            if (summarySelector) {
                $(summarySelector).text(
                    total === 0
                        ? "{{ __('No records to show.') }}"
                        : "{{ __('Page') }} " + currentPage + " {{ __('of') }} " + totalPages
                            + ", {{ __('showing') }} " + pageRows.length + " {{ __('record(s) out of') }} " + total + " {{ __('total') }}"
                );
            }

            $(prevBtnSelector)
                .prop('disabled', currentPage <= 1)
                .toggleClass('opacity-50 cursor-not-allowed', currentPage <= 1);
            $(nextBtnSelector)
                .prop('disabled', currentPage >= totalPages)
                .toggleClass('opacity-50 cursor-not-allowed', currentPage >= totalPages);
        }

        $(filterInputSelector).on('keyup', function () {
            currentPage = 1;
            render();
        });

        $(prevBtnSelector).on('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                render();
            }
        });

        $(nextBtnSelector).on('click', function () {
            currentPage += 1;
            render();
        });

        render();
    }

    setupPaginatedChoiceTable({
        rowSelector: '.choice',
        filterInputSelector: '#choice-name-filter',
        filterFeedbackSelector: '#choice-name-filter-feedback',
        prevBtnSelector: '#choice-prev-page',
        nextBtnSelector: '#choice-next-page',
        summarySelector: '#choice-pagination-summary',
        perPage: 10,
    });

    setupPaginatedChoiceTable({
        rowSelector: '.invitation-choice',
        filterInputSelector: '#invitation-choice-name-filter',
        filterFeedbackSelector: '#invitation-choice-name-filter-feedback',
        prevBtnSelector: '#invitation-choice-prev-page',
        nextBtnSelector: '#invitation-choice-next-page',
        summarySelector: '#invitation-choice-pagination-summary',
        perPage: 10,
    });

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
