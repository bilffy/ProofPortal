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

    /* Section boxes ("Select a Job" / "Select a School"): same as the franchise dashboard "Overview" box
       (.fd-box-head + .fd-jobs-title: #f0f3f5 strip, 1.09375rem / 600 / black title). */
    #emailsCategoryTabsContent .card {
        border: 1px solid #c2cfd6;
        border-radius: 0.25rem;
        overflow: hidden;
    }
    #emailsCategoryTabsContent .card-header {
        padding: 0.75rem 1.25rem;
        background: #f0f3f5;
        border-bottom: 1px solid #c2cfd6;
        margin-bottom: 0;
        color: #000;
        font-size: 1.09375rem;
        font-weight: 600;
        line-height: 1.1;
    }

    /* Search box in each card header (right side), same look as the franchise dashboard search. */
    #emailsCategoryTabsContent .emails-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    #emailsCategoryTabsContent .emails-search-box {
        position: relative;
        width: 216px;
        height: 36px;
        font-size: 0.875rem;
        font-weight: 400;
    }
    #emailsCategoryTabsContent .emails-search-icon {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0.75rem;
        display: flex;
        align-items: center;
        color: #000;
        pointer-events: none;
    }
    #emailsCategoryTabsContent .emails-search-input {
        display: block;
        width: 216px;
        height: 36px;
        padding: 0 2.25rem;
        border: 0;
        border-radius: 0.5rem;
        background: #fff;
        color: #111827;
        font-size: 0.875rem;
        line-height: 36px;
        box-sizing: border-box;
    }
    #emailsCategoryTabsContent .emails-search-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px #005890;
    }
    #emailsCategoryTabsContent .emails-search-clear {
        position: absolute;
        top: 0;
        bottom: 0;
        right: 0;
        display: flex;
        align-items: center;
        padding: 0 0.75rem;
        color: #6b7280;
        background: transparent;
        border: 0;
        cursor: pointer;
    }
    #emailsCategoryTabsContent .emails-search-clear:hover { color: #111827; }
    #emailsCategoryTabsContent .emails-search-clear svg { width: 1rem; height: 1rem; }

    /* Pagination like /school: "< Prev 1 2 3 ... Next >", current page on a #e9e6e3 square with blue text. */
    #emailsCategoryTabsContent .emails-pager {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem 0 0.25rem;
    }
    #emailsCategoryTabsContent .emails-page-btn,
    #emailsCategoryTabsContent .emails-page-gap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        height: 2.25rem;
        padding: 0 1rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #374151;
        background: transparent;
        border: 0;
        border-radius: 0;
    }
    #emailsCategoryTabsContent .emails-page-btn { cursor: pointer; }
    #emailsCategoryTabsContent .emails-page-btn:hover:not(:disabled):not(.is-current) { color: #9ca3af; }
    #emailsCategoryTabsContent .emails-page-nav { color: #6b7280; }
    #emailsCategoryTabsContent .emails-page-btn:disabled { cursor: default; }
    #emailsCategoryTabsContent .emails-page-btn.is-current {
        background: #e9e6e3;
        color: #2563eb;
        cursor: default;
    }

    /* Choice tables look like the /school table: bold header over a 2px rule, one thin rule per row. */
    #emailsCategoryTabsContent .emails-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        font-size: 0.875rem;
        color: #151b1e;
        background: #fff;
    }
    #emailsCategoryTabsContent .emails-table thead th {
        padding: 1rem;
        background: #fff;
        border: 0;
        border-bottom: 2px solid #d4d4d4;
        font-weight: 700;
        color: #151b1e;
        text-align: left;
        vertical-align: middle;
    }
    #emailsCategoryTabsContent .emails-table thead th.emails-sortable {
        cursor: pointer;
        user-select: none;
    }
    #emailsCategoryTabsContent .emails-table thead th.emails-sortable .emails-sort-icon {
        margin-right: 0.5rem;
    }
    #emailsCategoryTabsContent .emails-table thead th.text-center {
        text-align: center;
    }
    #emailsCategoryTabsContent .emails-table tbody td {
        padding: 1rem;
        background: #fff;
        border: 0;
        border-bottom: 1px solid #E6E7E8;
        vertical-align: middle;
    }
    #emailsCategoryTabsContent .emails-table tbody td.text-center {
        text-align: center;
    }
    /* Links in the cells look like /school: same colour as the text, no underline. */
    #emailsCategoryTabsContent .emails-table tbody td a:not(.btn-link),
    #emailsCategoryTabsContent .emails-table tbody td a:not(.btn-link):hover {
        color: inherit;
        text-decoration: none;
    }
    /* Row height like /users (60px: 1rem padding + a 28px badge line). The Select link is
       compact so it does not make the row taller than the plain text rows. */
    #emailsCategoryTabsContent .emails-table tbody tr {
        height: 60px;
    }
    #emailsCategoryTabsContent .emails-table .btn-link {
        padding: 0;
        font-size: inherit;
        line-height: 1.25rem;
        vertical-align: baseline;
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
        {{-- Promotional Products tab hidden for now (its content below is commented out too).
        <x-tabs.tab id="tab-ordering" :isActive="false">{{ __('Promotional Products') }}</x-tabs.tab>
        --}}
    </x-tabs.tabContainer>

    <x-tabs.tabContentContainer id="emailsCategoryTabsContent">
        <x-tabs.tabContent id="tab-proofing">
            <div class="card">
                        <div class="card-header emails-card-head">
                            <span>{{ __('Select a Job') }}</span>
                            @if ($jobs->isNotEmpty())
                                <div class="emails-search-box">
                                    <span class="emails-search-icon"><i class="fa fa-search"></i></span>
                                    <input class="emails-search-input" id="choice-name-filter" type="text" placeholder="{{ __('Search...') }}" autocomplete="off">
                                    <button type="button" class="emails-search-clear" aria-label="{{ __('Clear search') }}"
                                        onclick="var i = document.getElementById('choice-name-filter'); i.value = ''; $(i).trigger('keyup'); i.focus();">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($jobs->isEmpty())
                                <p class="mb-0">{{ __('No Jobs have been synced for proofing yet.') }}</p>
                            @else
                                <table class="table emails-table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="emails-sortable" data-sort-key="school">{{ __('School Name') }}</th>
                                            <th scope="col" class="emails-sortable" data-sort-key="job">{{ __('Job') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($jobs as $job)
                                            @php
                                                $title = !empty($job->ts_season_id) && isset($seasonList[$job->ts_season_id])
                                                    ? __(':name (:season)', ['name' => $job->ts_jobname, 'season' => $seasonList[$job->ts_season_id]])
                                                    : $job->ts_jobname;
                                                $encryptedTsJobId = Crypt::encryptString($job->ts_job_id);
                                                $choiceName = mb_strtolower(trim($title . ' ' . ($job->school_name ?? '')));
                                                $proofingUrl = route('emails.show', ['tsJobId' => $encryptedTsJobId]);
                                            @endphp
                                            <tr class="choice" data-choice-name="{{ $choiceName }}" data-sort-school="{{ mb_strtolower($job->school_name ?? '') }}" data-sort-job="{{ mb_strtolower($title) }}">
                                                <td class="align-middle"><a href="{{ $proofingUrl }}">{{ $job->school_name ?? '—' }}</a></td>
                                                <td class="align-middle"><a href="{{ $proofingUrl }}">{{ $title }}</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="emails-pager" id="choice-pager"></div>
                            @endif
                        </div>
            </div>
        </x-tabs.tabContent>

        <x-tabs.tabContent id="tab-invitation">
            <div class="card">
                        <div class="card-header emails-card-head">
                            <span>{{ __('Select a School') }}</span>
                            @if ($invitationSchools->isNotEmpty())
                                <div class="emails-search-box">
                                    <span class="emails-search-icon"><i class="fa fa-search"></i></span>
                                    <input class="emails-search-input" id="invitation-choice-name-filter" type="text" placeholder="{{ __('Search...') }}" autocomplete="off">
                                    <button type="button" class="emails-search-clear" aria-label="{{ __('Clear search') }}"
                                        onclick="var i = document.getElementById('invitation-choice-name-filter'); i.value = ''; $(i).trigger('keyup'); i.focus();">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($invitationSchools->isEmpty())
                                <p class="mb-0">{{ __('No user invitation emails have been sent yet.') }}</p>
                            @else
                                <table class="table emails-table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="emails-sortable" data-sort-key="schoolkey">{{ __('School Key') }}</th>
                                            <th scope="col" class="emails-sortable" data-sort-key="school">{{ __('School Name') }}</th>
                                            <th scope="col" class="emails-sortable" data-sort-key="suburb">{{ __('Suburb') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invitationSchools as $school)
                                            @php
                                                $encryptedSchoolId = Crypt::encryptString($school->id);
                                                $invitationUrl = route('emails.invitations.show', ['schoolId' => $encryptedSchoolId]);
                                            @endphp
                                            <tr class="invitation-choice"
                                                data-choice-name="{{ mb_strtolower(trim($school->schoolkey . ' ' . $school->name . ' ' . $school->suburb)) }}"
                                                data-sort-schoolkey="{{ mb_strtolower((string) $school->schoolkey) }}"
                                                data-sort-school="{{ mb_strtolower((string) $school->name) }}"
                                                data-sort-suburb="{{ mb_strtolower((string) $school->suburb) }}">
                                                <td class="align-middle"><a href="{{ $invitationUrl }}">{{ $school->schoolkey }}</a></td>
                                                <td class="align-middle"><a href="{{ $invitationUrl }}">{{ $school->name }}</a></td>
                                                <td class="align-middle"><a href="{{ $invitationUrl }}">{{ $school->suburb }}</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="emails-pager" id="invitation-choice-pager"></div>
                            @endif
                        </div>
            </div>
        </x-tabs.tabContent>
        {{-- Promotional Products tab content hidden for now.
        <x-tabs.tabContent id="tab-ordering">
            <div class="card">
                        <div class="card-header emails-card-head">
                            <span>{{ __('Select a School') }}</span>
                            @if ($orderingSchools->isNotEmpty())
                                <div class="emails-search-box">
                                    <span class="emails-search-icon"><i class="fa fa-search"></i></span>
                                    <input class="emails-search-input" id="ordering-choice-name-filter" type="text" placeholder="{{ __('Search...') }}" autocomplete="off">
                                    <button type="button" class="emails-search-clear" aria-label="{{ __('Clear search') }}"
                                        onclick="var i = document.getElementById('ordering-choice-name-filter'); i.value = ''; $(i).trigger('keyup'); i.focus();">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if ($orderingSchools->isEmpty())
                                <p class="mb-0">{{ __('No order emails have been sent yet.') }}</p>
                            @else
                                <table class="table emails-table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="emails-sortable" data-sort-key="school">{{ __('School') }}</th>
                                            <th scope="col" class="text-center">{{ __('Select') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orderingSchools as $school)
                                            @php
                                                $encryptedSchoolId = Crypt::encryptString($school->id);
                                            @endphp
                                            <tr class="ordering-choice" data-choice-name="{{ strtolower($school->name) }}" data-sort-school="{{ strtolower($school->name) }}">
                                                <td class="align-middle">{{ $school->name }}</td>
                                                <td class="align-middle text-center">
                                                    <a href="{{ route('emails.ordering.show', ['schoolId' => $encryptedSchoolId]) }}" class="btn btn-link">
                                                        {{ __('Select') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="emails-pager" id="ordering-choice-pager"></div>
                            @endif
                        </div>
            </div>
        </x-tabs.tabContent>
        --}}
    </x-tabs.tabContentContainer>

    @include('proofing.franchise.emails._modals')
@endsection

@section('js')
<script>
$(document).ready(function () {
    // Shared filter + client-side pagination (20 rows/page) for the Jobs and
    // Schools choice tables - both tables are rendered in full up-front (no
    // AJAX here), so paging and the existing text filter both just show/hide
    // <tr> elements with the "d-none" class.
    function setupPaginatedChoiceTable(options) {
        const rowSelector = options.rowSelector;
        const filterInputSelector = options.filterInputSelector;
        const filterFeedbackSelector = options.filterFeedbackSelector;
        const pagerSelector = options.pagerSelector;
        const perPage = options.perPage || 20;

        let currentPage = 1;

        // Click a sortable header to sort the rows (asc, then desc), like /school: an
        // up / down arrow shows beside the active column; it loads sorted by School ascending. Rows are reordered in the DOM,
        // so filtering and paging keep working on the new order.
        const $table = $(rowSelector).first().closest('table');
        let sortKey = null;
        let sortDir = 'asc';

        function applySort(key, dir) {
            sortKey = key;
            sortDir = dir;

            const $tbody = $table.find('tbody');
            const rows = $(rowSelector).get().sort(function (x, y) {
                const xv = ($(x).attr('data-sort-' + key) || '').toString();
                const yv = ($(y).attr('data-sort-' + key) || '').toString();
                const cmp = xv.localeCompare(yv, undefined, { numeric: true, sensitivity: 'base' });
                return dir === 'asc' ? cmp : -cmp;
            });
            $tbody.append(rows);

            $table.find('.emails-sort-icon').remove();
            $table.find('th.emails-sortable').filter(function () {
                return $(this).data('sort-key') === key;
            }).prepend('<i class="fa fa-arrow-' + (dir === 'asc' ? 'up' : 'down') + ' fa-sm emails-sort-icon"></i>');
        }

        $table.find('th.emails-sortable').on('click', function () {
            const key = $(this).data('sort-key');
            applySort(key, (sortKey === key && sortDir === 'asc') ? 'desc' : 'asc');
            currentPage = 1;
            render();
        });

        // Initial order: School ascending, arrow up.
        if ($table.find('th.emails-sortable[data-sort-key="school"]').length) {
            applySort('school', 'asc');
        }

        function matchingRows() {
            const filterByText = ($(filterInputSelector).val() || '').trim().toLowerCase();

            if (filterByText.length >= 1) {
                return $(rowSelector).filter(function () {
                    // attr(), not data(): data() turns numeric-looking text into numbers.
                    return ($(this).attr('data-choice-name') || '').indexOf(filterByText) !== -1;
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

            renderPager(totalPages);
        }

        // "< Prev 1 2 3 ... Next >" like the /school pagination.
        function renderPager(totalPages) {
            const $pager = $(pagerSelector).empty();
            if ($pager.length === 0) {
                return;
            }

            const pages = [];
            if (totalPages <= 7) {
                for (let p = 1; p <= totalPages; p++) pages.push(p);
            } else {
                const set = new Set([1, 2, totalPages - 1, totalPages, currentPage - 1, currentPage, currentPage + 1]);
                let last = 0;
                Array.from(set).filter(p => p >= 1 && p <= totalPages).sort((x, y) => x - y).forEach(function (p) {
                    if (last && p - last > 1) pages.push('...');
                    pages.push(p);
                    last = p;
                });
            }

            const $prev = $('<button type="button" class="emails-page-btn emails-page-nav"><i class="fa fa-angle-left"></i> Prev</button>')
                .prop('disabled', currentPage <= 1)
                .on('click', function () { currentPage -= 1; render(); });
            const $next = $('<button type="button" class="emails-page-btn emails-page-nav">Next <i class="fa fa-angle-right"></i></button>')
                .prop('disabled', currentPage >= totalPages)
                .on('click', function () { currentPage += 1; render(); });

            $pager.append($prev);
            pages.forEach(function (p) {
                if (p === '...') {
                    $pager.append('<span class="emails-page-gap">...</span>');
                } else {
                    $('<button type="button" class="emails-page-btn"></button>')
                        .text(p)
                        .toggleClass('is-current', p === currentPage)
                        .on('click', function () { currentPage = p; render(); })
                        .appendTo($pager);
                }
            });
            $pager.append($next);
        }

        $(filterInputSelector).on('keyup', function () {
            currentPage = 1;
            render();
        });

        render();
    }

    setupPaginatedChoiceTable({
        rowSelector: '.choice',
        filterInputSelector: '#choice-name-filter',
        filterFeedbackSelector: '#choice-name-filter-feedback',
        pagerSelector: '#choice-pager',
        perPage: 20,
    });

    setupPaginatedChoiceTable({
        rowSelector: '.invitation-choice',
        filterInputSelector: '#invitation-choice-name-filter',
        filterFeedbackSelector: '#invitation-choice-name-filter-feedback',
        pagerSelector: '#invitation-choice-pager',
        perPage: 20,
    });

    setupPaginatedChoiceTable({
        rowSelector: '.ordering-choice',
        filterInputSelector: '#ordering-choice-name-filter',
        filterFeedbackSelector: '#ordering-choice-name-filter-feedback',
        pagerSelector: '#ordering-choice-pager',
        perPage: 20,
    });

    // Tab switching itself is handled by Flowbite's Tabs component (initialised
    // app-wide from data-tabs-toggle/data-tabs-target attributes - see
    // x-tabs.tabContainer/x-tabs.tab). This just keeps the address bar hash in
    // sync so links can deep-link to a specific tab.
    $('#tab-proofing-tab, #tab-invitation-tab, #tab-ordering-tab').on('click', function () {
        const target = $(this).data('tabs-target');
        if (history.replaceState) {
            history.replaceState(null, '', target);
        } else {
            window.location.hash = target;
        }
    });

    // Deep-link support: /emails#tab-invitation lands directly on that tab
    // (used by the "Back to Schools" link on the per-school invitation page).
    if (/^#tab-[a-z-]+$/.test(window.location.hash)) {
        $(window.location.hash + '-tab').trigger('click');
    }
});
</script>

@include('proofing.franchise.emails._view_resend_scripts')
@endsection
