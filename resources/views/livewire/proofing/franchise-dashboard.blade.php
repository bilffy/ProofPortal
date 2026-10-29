@php
    $pct = function (int $value, int $total): int {
        return $total > 0 ? (int) round(($value / $total) * 100) : 0;
    };

    $selectedSeasonLabel = 'All seasons';
    if ($selectedSeasonId !== '' && $selectedSeasonId !== null) {
        $matchedSeason = $seasons->first(
            fn ($season) => (string) $season->ts_season_id === (string) $selectedSeasonId
        );
        if ($matchedSeason) {
            $selectedSeasonLabel = $matchedSeason->code;
        }
    }

    $photographyTotal = max(0, (int) ($metrics['photography_total'] ?? 0));
    $photographyConfigured = max(0, (int) ($metrics['photography_configured'] ?? 0));
    $photographyNotConfigured = max(0, (int) ($metrics['photography_not_configured'] ?? 0));
    $activeProofing = max(0, (int) ($metrics['active_proofing'] ?? 0));
    $schoolsCount = max(0, (int) ($metrics['schools_count'] ?? 0));
    $synced = max(0, (int) ($metrics['synced'] ?? 0));
    $statusOpened = max(0, (int) ($metrics['status_opened'] ?? 0));
    $statusNotOpened = max(0, (int) ($metrics['status_not_opened'] ?? 0));
    $statusCompleted = max(0, (int) ($metrics['status_completed'] ?? 0));
    $statusArchived = max(0, (int) ($metrics['status_archived'] ?? 0));
    $statusDeleted = max(0, (int) ($metrics['status_deleted'] ?? 0));
    $statusIncomplete = max(0, (int) ($metrics['status_incomplete'] ?? 0));
    $statusModified = max(0, (int) ($metrics['status_modified'] ?? 0));
    $statusUnlocked = max(0, (int) ($metrics['status_unlocked'] ?? 0));

    $statusMixTotal = $statusOpened + $statusNotOpened + $statusCompleted + $statusArchived + $statusDeleted + $statusIncomplete + $statusModified + $statusUnlocked;
    $syncedJobsLabel = ($synced === 1) ? 'Synced Job' : 'Synced Jobs';
    $deletedJobsLabel = ($statusDeleted === 1) ? 'Deleted Job' : 'Deleted Jobs';

    // Rainbow palette from MSP chart colour reference (left → right).
    $chartPalette = [
        '#ee3124', // red
        '#f99b1c', // orange-red
        '#fdb913', // orange
        '#FED33D', // golden yellow
        '#fff200', // bright yellow
        '#b5d334', // lime
        '#4cc0ad', // light green
        '#07a88f', // medium green
        '#84b0c7ff', // teal
        '#00B9D5', // cyan
        '#00b4df', // medium blue
        '#005b8e', // deep blue
        '#053775', // navy
    ];

    $statusLegend = [
        ['label' => 'Active', 'value' => $statusOpened, 'color' => $chartPalette[5]],
        ['label' => 'Archived', 'value' => $statusArchived, 'color' => $chartPalette[2]],
        ['label' => 'Deleted', 'value' => $statusDeleted, 'color' => $chartPalette[0]],
        ['label' => 'Incomplete', 'value' => $statusIncomplete, 'color' => $chartPalette[11]],
        ['label' => 'Modified', 'value' => $statusModified, 'color' => $chartPalette[1]],
        ['label' => 'Unlocked', 'value' => $statusUnlocked, 'color' => $chartPalette[8]],
        ['label' => 'None', 'value' => $statusNotOpened, 'color' => $chartPalette[9]],
        ['label' => 'Completed (Pending Archived)', 'value' => $statusCompleted, 'color' => $chartPalette[7]],
    ];
    $statusColorMap = [];
    foreach ($statusLegend as $legendItem) {
        $statusColorMap[strtolower($legendItem['label'])] = $legendItem['color'];
    }
    // The table's job_status column uses the plain "Completed" wording, while the
    // chart legend spells it out as "Completed (Pending Archived)" - map both.
    if (isset($statusColorMap['completed (pending archived)'])) {
        $statusColorMap['completed'] = $statusColorMap['completed (pending archived)'];
    }

    $photographyLegend = [
        ['label' => 'Configured', 'value' => $photographyConfigured, 'color' => $chartPalette[5]],
        ['label' => 'Not Configured', 'value' => $photographyNotConfigured, 'color' => $chartPalette[10]],
    ];

    $chartPayload = [
        'photography' => [
            'labels' => array_column($photographyLegend, 'label'),
            'values' => array_column($photographyLegend, 'value'),
            'colors' => array_column($photographyLegend, 'color'),
            'unit' => 'jobs',
        ],
        'proofingStatus' => [
            'labels' => array_column($statusLegend, 'label'),
            'values' => array_column($statusLegend, 'value'),
            'colors' => array_column($statusLegend, 'color'),
            'unit' => 'jobs',
        ],
    ];

@endphp

<div>
<style>
    .sd-page {
        background: #fff;
        margin: -1rem;
        padding: 1.25rem 1.25rem 2rem;
        min-height: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .sd-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.5rem;
        margin-bottom: 1.25rem;
    }

    .sd-header-left {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        min-width: 0;
        flex: 1 1 16rem;
    }

    .sd-school-sub {
        margin: 0.25rem 0 0;
        font-size: 0.875rem;
        color: #7A8699;
    }

    .sd-season {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-shrink: 0;
        max-width: 100%;
    }

    .sd-season label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #5B6575;
        margin: 0;
        white-space: nowrap;
    }

    .sd-season-select {
        min-width: 12rem;
        max-width: 100%;
        background: #fff;
        border: 1px solid #D0D7E2;
        color: #1A2B4A;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        padding: 0.55rem 2rem 0.55rem 0.75rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        outline: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%235B6575' d='M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
    }

    .sd-season-select:focus {
        border-color: #1E88E5;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
    }

    /* .sd-panel is declared later with the same specificity, so chain both classes
       to make sure the zero padding wins and the header runs edge to edge. */
    .sd-panel.fd-summary-card {
        padding: 0;
        overflow: hidden;
    }

    .fd-summary-card .fd-summary-head {
        margin-bottom: 0;
        padding: 0.75rem 1.25rem;
        background: #f0f3f5;
    }

    .fd-summary-card .fd-summary-body {
        padding: 1rem 1.1rem 1.1rem;
        border-top: 1px solid #c2cfd6;
    }

    .fd-summary-card .fd-summary-body .sd-panel {
        margin-bottom: 0;
    }

    .fd-summary-card .fd-summary-body section:last-child .sd-charts-bottom {
        margin-bottom: 0;
    }

    .fd-summary-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 1px solid #E8EDF3;
        border-radius: 9999px;
        background: #F8FAFC;
        color: #5B6575;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .fd-summary-toggle:hover {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .sd-kpi-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    @media (min-width: 768px) {
        .sd-kpi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .sd-kpi-card {
        position: relative;
        overflow: visible;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #E8EDF3;
        border-radius: 16px;
        padding: 1.1rem 1.15rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        min-height: 5.5rem;
        height: 100%;
        box-sizing: border-box;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    /* Icon + label grouped together on the left (vertically centred, close together),
       count badge on the right - all in one row. */
    .sd-kpi-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .sd-kpi-icon-label {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-width: 0;
    }

    .sd-kpi-icon {
        /* Match /proofing/{job} task tile icons: solid square, white glyph */
        width: auto;
        height: auto;
        min-width: 3rem;
        min-height: 3rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.5rem;
        line-height: 1;
        padding: 0.75rem;
        color: #fff;
        box-sizing: border-box;
    }

    /* KPI icon colours from MSP rainbow bar palette */
    .sd-kpi-card.is-blue .sd-kpi-icon { background: #00b4df; color: #fff; }      
    
    .sd-kpi-card.is-teal .sd-kpi-icon { background: #48BCB3; color: #fff; }     
        
    .sd-kpi-card.is-orange .sd-kpi-icon { background: #FBAC3D; color: #fff; }

    /* Count badge - sits beside the icon on the top row, same colour as the icon
       (mirrors the badge-pill on the /proofing/{job} task tiles). */
    .sd-kpi-count-badge {
        flex-shrink: 0;
        min-width: 2rem;
        height: 2rem;
        padding: 0 0.65rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        box-shadow: 0 2px 6px rgba(16, 24, 40, 0.18);
        box-sizing: border-box;
    }

    .sd-kpi-card.is-blue .sd-kpi-count-badge { background: #00b4df; }
    
    .sd-kpi-card.is-teal .sd-kpi-count-badge { background: #48BCB3; }
    
    .sd-kpi-card.is-orange .sd-kpi-count-badge { background: #FBAC3D; }

    /* Label sits below the icon/badge row, like the h5.mb-0.mt-3 on the task tiles. */
    .sd-kpi-tile-label {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .sd-charts-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
        align-items: stretch;
    }

    @media (min-width: 976px) {

        /* Job Proofing Status (is-blue, 1st in markup) gets 2/3 of the row,
           School Photography Status (is-teal, 2nd) gets the remaining 1/3. */
        .sd-charts-bottom {
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        }
    }

    .sd-panel {
        background: #fff;
        border: 1px solid #E8EDF3;
        border-radius: 16px;
        padding: 1rem 1.1rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        margin-bottom: 1.25rem;
        box-sizing: border-box;
        min-width: 0;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .sd-charts-bottom .sd-panel {
        margin-bottom: 0;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .sd-charts-bottom .sd-panel {
        height: auto;
        max-height: none;
        min-height: 20rem;
        overflow: hidden;
    }

    .sd-pie-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        align-items: center;
        flex: 1;
        min-height: 0;
    }

    @media (min-width: 640px) {
        .sd-pie-layout {
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
        }
    }

    .sd-pie-layout .sd-chart-box {
        height: 13rem;
        width: 100%;
        max-width: 14rem;
        margin: 0 auto;
    }

    @media (min-width: 640px) {
        .sd-pie-layout .sd-chart-box {
            height: 15rem;
            max-width: 15rem;
        }
    }

    .sd-pie-layout .sd-progress-list {
        row-gap: 0.5rem;
    }

    .sd-donut-card.is-teal .sd-progress-list {
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: repeat(2, auto);
        grid-auto-flow: row;
    }

    .fd-jobs-panel {
        margin-top: 0.25rem;
        margin-bottom: 1.25rem;
        background: #fff;
        border: 1px solid #c2cfd6;
        border-radius: 0.25rem;
        padding: 0;
        box-shadow: none;
        overflow: hidden;
    }

    .fd-jobs-panel .sd-panel-head {
        margin-bottom: 1rem;
    }

    /* Header box above the Proofing Jobs table - mirrors the "Your Synced (Active)
       Jobs" panel header on /proofing (proofing-home.blade.php): light background,
       bold title + a plain-language summary line, and a job-name search box. */
    .fd-jobs-info-box {
        background: #f0f3f5;
        border: 0;
        border-bottom: 1px solid #c2cfd6;
        border-radius: 0;
        padding: 0.75rem 1.25rem;
        margin-bottom: 0;
    }

    .fd-jobs-info-line {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.4rem;
        margin-bottom: 0.85rem;
    }

    .fd-jobs-info-line .sd-badge {
        margin-left: auto;
        flex-shrink: 0;
    }

    .fd-jobs-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #1A2B4A;
    }

    .fd-jobs-count-text {
        font-size: 0.85rem;
        color: #5B6575;
    }

    .fd-jobs-count-text strong {
        color: #1A2B4A;
    }

    .fd-jobs-search-row {
        width: 100%;
    }

    .fd-jobs-search-input {
        width: 100%;
        max-width: 28rem;
        padding: 0.375rem 0.75rem;
        border: 1px solid #c2cfd6;
        border-radius: 0;
        background: #fff;
        color: #3e515b;
        font-size: 0.875rem;
        box-sizing: border-box;
    }

    .fd-jobs-search-input:focus {
        outline: none;
        border-color: #60e0ff;
        box-shadow: 0 0 0 0.2rem rgba(0, 179, 223, 0.25);
    }

    .fd-jobs-hint {
        margin: 0.85rem 1.25rem 0.75rem;
        font-size: 0.85rem;
        color: #7A8699;
    }

    .fd-jobs-table-wrap {
        width: 100%;
        min-width: 0;
        padding: 0 1.25rem 1.25rem;
        box-sizing: border-box;
    }

    .fd-jobs-table-wrap .dataTables_wrapper {
        width: 100%;
    }

    .fd-jobs-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 0.75rem;
    }

    .fd-jobs-toolbar .dataTables_length,
    .fd-jobs-toolbar .dataTables_filter,
    .fd-jobs-toolbar .fd-jobs-length,
    .fd-jobs-toolbar .fd-jobs-search {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
        width: auto !important;
        text-align: left !important;
    }

    .fd-jobs-toolbar .fd-jobs-search {
        margin-left: auto !important;
    }

    .fd-jobs-toolbar .dataTables_length label,
    .fd-jobs-toolbar .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #5B6575;
        white-space: nowrap;
    }

    .fd-jobs-toolbar .dataTables_length select {
        min-width: 4.5rem;
        height: 2rem;
        padding: 0.2rem 1.6rem 0.2rem 0.45rem;
        border: 1px solid #c2cfd6;
        border-radius: 0;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%233e515b' d='M4.2 6h7.6a.6.6 0 0 1 .46.98l-3.8 4.56a.6.6 0 0 1-.92 0l-3.8-4.56A.6.6 0 0 1 4.2 6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.5rem center;
        background-size: 0.7rem;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        color: #3e515b;
    }

    .fd-jobs-toolbar .dataTables_filter input {
        height: 2rem;
        min-width: 14rem;
        max-width: 100%;
        margin-left: 0 !important;
        padding: 0.25rem 0.65rem;
        border: 1px solid #c2cfd6;
        border-radius: 0;
        background: #fff;
        color: #3e515b;
    }

    .fd-jobs-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .fd-jobs-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-top: 1rem;
        padding: 0.5rem 0;
        width: 100%;
    }

    .fd-jobs-footer .dataTables_info,
    .fd-jobs-footer .fd-jobs-info {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.85rem;
        color: #5B6575;
        margin: 0 !important;
        padding: 0 !important;
    }

    .fd-jobs-footer .dataTables_paginate,
    .fd-jobs-footer .fd-jobs-paginate {
        display: inline-flex !important;
        margin: 0 auto !important;
        padding: 0 !important;
        float: none !important;
        border: 1px solid #d0d7de !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        background: #ffffff !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04) !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination {
        display: inline-flex !important;
        flex-direction: row !important;
        align-items: stretch !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
        border: 0 !important;
        background: transparent !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item,
    .fd-jobs-footer .dataTables_paginate .paginate_button {
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        align-items: stretch !important;
        border: 0 !important;
        background: transparent !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item:not(:last-child) .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button:not(:last-child) a,
    .fd-jobs-footer .dataTables_paginate > a:not(:last-child),
    .fd-jobs-footer .dataTables_paginate > span > a:not(:last-child),
    .fd-jobs-footer .dataTables_paginate > span > .paginate_button:not(:last-child) {
        border-right: 1px solid #d0d7de !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button a,
    .fd-jobs-footer .dataTables_paginate a {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0.5rem 1rem !important;
        min-width: 2.5rem !important;
        height: 100% !important;
        font-size: 0.85rem !important;
        font-weight: 400 !important;
        color: #0f4c67 !important;
        background: #ffffff !important;
        border-top: 0 !important;
        border-bottom: 0 !important;
        border-left: 0 !important;
        border-radius: 0 !important;
        text-decoration: none !important;
        transition: background-color 0.15s ease, color 0.15s ease !important;
        box-shadow: none !important;
        margin: 0 !important;
        line-height: 1.2 !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item:not(.active):not(.disabled) .page-link:hover,
    .fd-jobs-footer .dataTables_paginate .paginate_button:not(.current):not(.disabled) a:hover {
        background-color: #f0f4f8 !important;
        color: #005b94 !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item.active .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button.current,
    .fd-jobs-footer .dataTables_paginate .paginate_button.current a {
        background-color: #005b94 !important;
        color: #ffffff !important;
        font-weight: 500 !important;
        border-radius: 0 !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item.disabled .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button.disabled,
    .fd-jobs-footer .dataTables_paginate .paginate_button.disabled a {
        color: #9aa8b3 !important;
        background-color: #ffffff !important;
        cursor: default !important;
        opacity: 0.8 !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item.previous .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button.previous a {
        color: #0f4c67 !important;
    }

    .fd-jobs-footer .dataTables_paginate ul.pagination .page-item.next .page-link,
    .fd-jobs-footer .dataTables_paginate .paginate_button.next a {
        color: #0f4c67 !important;
    }

    @media (max-width: 768px) {
        .fd-jobs-footer {
            flex-direction: column;
            gap: 0.75rem;
        }
        .fd-jobs-footer .dataTables_info,
    .fd-jobs-footer .fd-jobs-info {
            position: static;
            transform: none;
            text-align: center;
        }
    }

    .fd-jobs-table,
    .fd-jobs-table.dataTable {
        width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        font-size: 0.85rem;
        color: #3e515b;
        margin: 0 !important;
        border: 1px solid #c2cfd6 !important;
        background: #fff;
    }

    .fd-jobs-table.dataTable.no-footer {
        border-bottom: 1px solid #c2cfd6 !important;
    }

    .fd-jobs-table thead th,
    .fd-jobs-table.dataTable thead th {
        background: #fff !important;
        border: 1px solid #c2cfd6 !important;
        border-bottom: 2px solid #c2cfd6 !important;
        padding: 0.3rem 0.5rem !important;
        text-align: left;
        white-space: nowrap;
        font-weight: 700;
        color: #808080;
        vertical-align: middle;
    }

    /* DataTables (bootstrap4 integration) auto-adds its own up/down sort arrows via
       :before/:after on .sorting/.sorting_asc/.sorting_desc, reserving 30px/20px of
       right padding for them. We already show a fa-sort icon to the left of each
       heading, so hide the library's right-side arrows and reclaim that padding. */
    .fd-jobs-table.dataTable thead > tr > th.sorting,
    .fd-jobs-table.dataTable thead > tr > th.sorting_asc,
    .fd-jobs-table.dataTable thead > tr > th.sorting_desc,
    .fd-jobs-table.dataTable.table-sm > thead > tr > th {
        padding-right: 0.5rem !important;
    }

    .fd-jobs-table.dataTable thead .sorting:before,
    .fd-jobs-table.dataTable thead .sorting:after,
    .fd-jobs-table.dataTable thead .sorting_asc:before,
    .fd-jobs-table.dataTable thead .sorting_asc:after,
    .fd-jobs-table.dataTable thead .sorting_desc:before,
    .fd-jobs-table.dataTable thead .sorting_desc:after,
    .fd-jobs-table.dataTable thead .sorting_asc_disabled:before,
    .fd-jobs-table.dataTable thead .sorting_asc_disabled:after,
    .fd-jobs-table.dataTable thead .sorting_desc_disabled:before,
    .fd-jobs-table.dataTable thead .sorting_desc_disabled:after {
        content: none !important;
        display: none !important;
    }

    .fd-jobs-table tbody td,
    .fd-jobs-table.dataTable tbody td {
        border: 1px solid #c2cfd6 !important;
        padding: 0.3rem 0.5rem;
        vertical-align: top;
        background: #fff;
    }

    .fd-jobs-table tbody tr.is-completed td {
        background: #d0eeea;
    }

    .fd-jobs-table .is-date-start.is-due {
        color: #07A88F;
        font-weight: 700;
    }

    .fd-jobs-table .is-date-warning.is-due {
        color: #fda900;
        font-weight: 700;
    }

    .fd-jobs-table .is-date-due.is-due {
        color: #ED2124;
        font-weight: 700;
    }

    .fd-jobs-table .fd-folder-status-line {
        display: block;
        line-height: 1.35;
    }

    .fd-jobs-table-wrap .dataTables_wrapper .dataTables_length,
    .fd-jobs-table-wrap .dataTables_wrapper .dataTables_filter,
    .fd-jobs-table-wrap .dataTables_wrapper .dataTables_info,
    .fd-jobs-table-wrap .dataTables_wrapper .dataTables_paginate {
        font-size: 0.85rem;
        color: #5B6575;
    }

    .fd-jobs-table thead .fa-play { color: #43A047; }
    .fd-jobs-table thead .fa-circle { color: #FB8C00; }
    .fd-jobs-table thead .fa-stop { color: #E53935; }

    .sd-badge.is-status {
        background: #ffffffff;
        color: #5B6575;
    }

    .sd-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        min-width: 0;
        flex-shrink: 0;
    }

    .sd-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #5B6575;
        background: #F5F7FA;
        border: 1px solid #E8EDF3;
        border-radius: 9999px;
        padding: 0.25rem 0.7rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .sd-chart-box {
        position: relative;
        height: 11.5rem;
        min-width: 0;
    }

    .sd-card-title {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0;
        font-size: 0.88rem;
        font-weight: 700;
        color: #1A2B4A;
        min-width: 0;
        overflow: hidden;
    }

    .sd-progress-summary {
        display: flex;
        align-items: baseline;
        gap: 0.4rem;
        margin: 0.25rem 0 0.85rem;
        flex-shrink: 0;
    }

    .sd-progress-summary strong {
        font-size: 1.6rem;
        line-height: 1;
        color: #1A2B4A;
        font-weight: 700;
    }

    .sd-progress-summary span {
        font-size: 0.85rem;
        color: #7A8699;
        font-weight: 600;
    }

    .sd-progress-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(4, auto);
        grid-auto-flow: column;
        column-gap: 1.1rem;
        row-gap: 0.6rem;
        flex: 1;
        min-height: 0;
    }

    .sd-progress-row .sd-progress-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
        font-size: 0.85rem;
        min-width: 0;
    }

    .sd-progress-row .dot {
        width: 9px;
        height: 9px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .sd-progress-row .lbl {
        flex: 1;
        min-width: 0;
        color: #5B6575;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sd-progress-row .pct {
        font-weight: 700;
        color: #1A2B4A;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .sd-progress-track {
        width: 100%;
        height: 10px;
        border-radius: 9999px;
        background: #EEF2F6;
        overflow: hidden;
    }

    .sd-progress-fill {
        height: 100%;
        border-radius: 9999px;
        min-width: 0;
        transition: width 0.35s ease;
    }

    .sd-empty {
        text-align: center;
        color: #7A8699;
        font-size: 0.875rem;
        padding: 2rem 0;
        margin: 0;
    }

    @media (max-width: 975px) {
        .sd-charts-bottom .sd-panel {
            height: auto;
            min-height: 0;
            max-height: none;
            overflow: hidden;
        }

    }

    @media (max-width: 480px) {
        .sd-page {
            padding: 1rem 0.85rem 1.5rem;
        }

        .sd-season {
            width: 100%;
        }

        .sd-season-select {
            flex: 1;
            min-width: 0;
        }

        .sd-kpi-count-badge {
            min-width: 2.25rem;
            height: 2.25rem;
            font-size: 1.15rem;
        }

        .sd-kpi-tile-label {
            font-size: 1.1rem;
        }
    }
</style>

<div class="sd-page">
    <div class="sd-header">
        <div class="sd-header-left">
            <div>
                <h3 class="text-2xl">Resource Centre</h3>
                <p class="sd-school-sub">Review the status of your schools' photography and proofing jobs.</p>
            </div>
        </div>

        <div class="sd-season">
            <label for="franchise-dashboard-season">Season</label>
            <select
                id="franchise-dashboard-season"
                class="sd-season-select"
                onchange="window.location.href = this.value;"
            >
                <option
                    value="{{ route('franchise.dashboard') }}"
                    @selected($selectedSeasonId === '')
                >
                    All seasons
                </option>
                @foreach ($seasons as $season)
                    <option
                        value="{{ route('franchise.dashboard', ['season' => $season->ts_season_id]) }}"
                        @selected((string) $selectedSeasonId === (string) $season->ts_season_id)
                    >
                        {{ $season->code }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:key="fd-metrics-{{ $selectedSeasonId === '' ? 'all' : $selectedSeasonId }}">
    <script type="application/json" id="franchise-dashboard-chart-data">@json($chartPayload)</script>

    <div class="sd-panel fd-summary-card" x-data="{ open: true }">
        <div class="sd-panel-head fd-summary-head">
            <h5 class="fd-jobs-title">Overview</h5>
            <button
                type="button"
                class="fd-summary-toggle"
                x-on:click="open = ! open; if (open) { $nextTick(() => window.dispatchEvent(new Event('resize'))); }"
                x-bind:aria-expanded="open.toString()"
                x-bind:title="open ? 'Minimise' : 'Expand'"
                aria-label="Toggle overview"
            >
                <i class="fa" x-bind:class="open ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
            </button>
        </div>

        <div class="fd-summary-body" x-show="open" x-transition.opacity.duration.150ms>
    <div class="sd-kpi-grid">
        <div
            class="sd-kpi-card is-orange"
        >
            <div class="sd-kpi-top-row">
                <div class="sd-kpi-icon-label">
                    <span class="sd-kpi-icon"><x-icon icon="university" /></span>
                    <span class="sd-kpi-tile-label" style="color:#fbac3d">Your Schools</span>
                </div>
                <span class="sd-kpi-count-badge">{{ number_format($schoolsCount) }}</span>
            </div>
        </div>


        <div
            class="sd-kpi-card is-blue"
        >
            <div class="sd-kpi-top-row">
                <div class="sd-kpi-icon-label">
                    <span class="sd-kpi-icon"><x-icon icon="refresh" /></span>
                    <span class="sd-kpi-tile-label" style="color:#00b4df">Your Proofing Jobs</span>
                </div>
                <span class="sd-kpi-count-badge">{{ number_format($activeProofing) }}</span>
            </div>
        </div>

        <div
            class="sd-kpi-card is-teal"
        >
            <div class="sd-kpi-top-row">
                <div class="sd-kpi-icon-label">
                    <span class="sd-kpi-icon"><x-icon icon="camera" /></span>
                    <span class="sd-kpi-tile-label" style="color:#07a88f">Your Photography Jobs</span>
                </div>
                <span class="sd-kpi-count-badge">{{ number_format($photographyTotal) }}</span>
            </div>
        </div>
    </div>

    <section>
        <div class="sd-charts-bottom">

            <div class="sd-panel sd-donut-card is-blue">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        Job Proofing Status
                    </h6>
                    <span class="sd-badge is-status">Season: {{ $selectedSeasonLabel }}</span>
                </div>
                @if ($statusMixTotal === 0)
                    <p class="sd-empty">No proofing status data found.</p>
                @else
                    <div class="sd-progress-summary">
                        <strong>{{ number_format($synced) }}</strong>
                        <span>{{ $syncedJobsLabel }}</span>
                        <span aria-hidden="true">•</span>
                        <strong>{{ number_format($statusDeleted) }}</strong>
                        <span>{{ $deletedJobsLabel }}</span>
                    </div>
                    <div class="sd-pie-layout">
                        <div class="sd-chart-box" wire:ignore>
                            <canvas id="franchise-dashboard-proofing-status-chart"></canvas>
                        </div>
                        <div class="sd-progress-list">
                            @foreach ($statusLegend as $item)
                                @php $itemPct = $pct($item['value'], max($statusMixTotal, 1)); @endphp
                                <div class="sd-progress-row">
                                    <div class="sd-progress-meta">
                                        <span class="dot" style="background: {{ $item['color'] }};"></span>
                                        <span class="lbl" style="color: {{ $item['color'] }};">{{ $item['label'] }} ({{ number_format($item['value']) }})</span>
                                        <span class="pct">{{ $itemPct }}%</span>
                                    </div>
                                    <div class="sd-progress-track">
                                        <div class="sd-progress-fill" style="width: {{ $itemPct }}%; background: {{ $item['color'] }};"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            
            <div
                class="sd-panel sd-donut-card is-teal"
            >
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        School Photography Status
                    </h6>
                    <span class="sd-badge is-status">Season: {{ $selectedSeasonLabel }}</span>
                </div>
                @if ($photographyTotal === 0)
                    <p class="sd-empty">No photography jobs found.</p>
                @else
                    <div class="sd-progress-summary">
                        <strong>{{ number_format($photographyTotal) }}</strong>
                        <span>{{ $photographyTotal === 1 ? 'Photography Job' : 'Photography Jobs' }}</span>
                    </div>
                    <div class="sd-pie-layout">
                        <div class="sd-chart-box" wire:ignore>
                            <canvas id="franchise-dashboard-photography-chart"></canvas>
                        </div>
                        <div class="sd-progress-list">
                            @foreach ($photographyLegend as $item)
                                @php $itemPct = $pct($item['value'], max($photographyTotal, 1)); @endphp
                                <div class="sd-progress-row">
                                    <div class="sd-progress-meta">
                                        <span class="dot" style="background: {{ $item['color'] }};"></span>
                                        <span class="lbl">{{ $item['label'] }} ({{ number_format($item['value']) }})</span>
                                        <span class="pct">{{ $itemPct }}%</span>
                                    </div>
                                    <div class="sd-progress-track">
                                        <div class="sd-progress-fill" style="width: {{ $itemPct }}%; background: {{ $item['color'] }};"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
        </div>
    </div>

    @php
        $proofingJobsTable = $proofingJobsTable ?? [];
        $today = \Carbon\Carbon::today();
    @endphp

    <section class="sd-panel fd-jobs-panel">
        <div class="fd-jobs-info-box">
            <div class="fd-jobs-info-line">
                <h5 class="fd-jobs-title">Proofing Jobs</h5>
                <span class="fd-jobs-count-text">
                    - There are <strong>{{ number_format($synced) }}</strong> synced out of
                    <strong>{{ number_format(count($proofingJobsTable)) }}</strong> jobs shown for {{ $selectedSeasonLabel }}.
                </span>
                <span class="sd-badge is-status">Season: {{ $selectedSeasonLabel }}</span>
            </div>
            <div class="fd-jobs-search-row">
                <input
                    type="search"
                    id="fd-proofing-jobs-search"
                    class="fd-jobs-search-input"
                    placeholder="Start typing to filter by..."
                    autocomplete="off"
                >
            </div>
        </div>
        <p class="fd-jobs-hint">Click headings to sort by that column.</p>

        @if (count($proofingJobsTable) === 0)
            <p class="sd-empty">No synced proofing jobs found for this season.</p>
        @else
            <div class="fd-jobs-table-wrap">
                <table id="fd-proofing-jobs-table" class="fd-jobs-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th><i class="fa fa-sort"></i> ID</th>
                            <th><i class="fa fa-sort"></i> School Name</th>
                            <th><i class="fa fa-sort"></i> Job Key</th>
                            <th><i class="fa fa-sort"></i> Job</th>
                            <th><i class="fa fa-sort"></i> Season</th>
                            <th><i class="fa fa-sort"></i> Job Proofing Status</th>
                            <th><i class="fa fa-sort"></i> Folder Proofing Statuses</th>
                            <th>
                                <i class="fa fa-sort"></i>
                                <span class="fa fa-play"></span>
                                Proofing Start
                            </th>
                            <th>
                                <i class="fa fa-sort"></i>
                                <span class="fa fa-circle"></span>
                                Proofing Warning
                            </th>
                            <th>
                                <i class="fa fa-sort"></i>
                                <span class="fa fa-stop"></span>
                                Proofing Due
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proofingJobsTable as $index => $jobRow)
                            @php
                                $proofStart = !empty($jobRow['proof_start']) ? \Carbon\Carbon::parse($jobRow['proof_start']) : null;
                                $proofWarning = !empty($jobRow['proof_warning']) ? \Carbon\Carbon::parse($jobRow['proof_warning']) : null;
                                $proofDue = !empty($jobRow['proof_due']) ? \Carbon\Carbon::parse($jobRow['proof_due']) : null;
                                $statusName = (string) ($jobRow['job_status'] ?? '');
                                $isIncomplete = strcasecmp($statusName, 'Incomplete') === 0
                                    || strcasecmp((string) ($jobRow['job_status_internal'] ?? ''), 'INCOMPLETE') === 0;
                                $statusColor = $statusColorMap[strtolower($statusName)] ?? null;
                            @endphp
                            <tr class="{{ !empty($jobRow['is_completed']) ? 'is-completed' : '' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $jobRow['school_name'] }}</td>
                                <td>{{ $jobRow['ts_jobkey'] }}</td>
                                <td>{{ $jobRow['ts_jobname'] }}</td>
                                <td>{{ $jobRow['season_code'] }}</td>
                                <td class="{{ $isIncomplete ? 'is-incomplete' : '' }}" style="{{ $statusColor ? 'color: ' . $statusColor . ';' : '' }} font-weight: 700;">{{ $statusName }}</td>
                                <td>
                                    @foreach (($jobRow['folder_statuses'] ?? []) as $folderLine)
                                        <span class="fd-folder-status-line">{{ $folderLine }}</span>
                                    @endforeach
                                </td>
                                <td class="is-date-start {{ $proofStart && $today->gte($proofStart) ? 'is-due' : '' }}">
                                    {{ $proofStart ? $proofStart->format('Y-m-d') : '' }}
                                </td>
                                <td class="is-date-warning {{ $proofWarning && $today->gte($proofWarning) ? 'is-due' : '' }}">
                                    {{ $proofWarning ? $proofWarning->format('Y-m-d') : '' }}
                                </td>
                                <td class="is-date-due {{ $proofDue && $today->gte($proofDue) ? 'is-due' : '' }}">
                                    {{ $proofDue ? $proofDue->format('Y-m-d') : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div> {{-- wire:key metrics --}}
</div> {{-- sd-page --}}

</div>

@push('scripts')
{{-- Use non-.min CSS — dataTables.bootstrap4.min.css is not present in public assets. --}}
<link rel="stylesheet" href="{{ URL::asset('proofing-assets/plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">
{{-- Load DataTables before Chart.js so a blocked/slow CDN cannot prevent pagination init. --}}
<script src="{{ URL::asset('proofing-assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
    (function () {
        var chartInstances = {
            photography: null,
            proofingStatus: null,
        };
        var tablesBooted = false;

        function readChartData() {
            var el = document.getElementById('franchise-dashboard-chart-data');
            if (!el) {
                return null;
            }
            try {
                return JSON.parse(el.textContent || '{}');
            } catch (e) {
                return null;
            }
        }

        function destroyCharts() {
            Object.keys(chartInstances).forEach(function (key) {
                if (chartInstances[key]) {
                    try {
                        chartInstances[key].destroy();
                    } catch (e) {
                        // Canvas may already be gone after a Livewire morph.
                    }
                    chartInstances[key] = null;
                }
            });
        }

        function buildPie(canvasId, payload) {
            var canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined' || !payload) {
                return null;
            }

            var labels = payload.labels || [];
            var values = (payload.values || []).map(function (v) { return Number(v) || 0; });
            var colors = payload.colors || [];
            var total = values.reduce(function (sum, v) { return sum + v; }, 0);
            if (total <= 0) {
                return null;
            }

            var pcts = values.map(function (v) {
                return total > 0 ? Math.round((v / total) * 100) : 0;
            });

            return new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        hoverBackgroundColor: colors,
                        borderWidth: 4,
                        borderColor: '#ffffff',
                        hoverBorderColor: '#ffffff',
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 8,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#ffffff',
                            titleColor: '#374151',
                            bodyColor: '#4B5563',
                            titleFont: { size: 13, weight: '600', family: 'Montserrat, sans-serif' },
                            bodyFont: { size: 12, family: 'Montserrat, sans-serif' },
                            padding: 12,
                            cornerRadius: 6,
                            displayColors: true,
                            boxWidth: 8,
                            boxHeight: 8,
                            boxPadding: 4,
                            usePointStyle: true,
                            borderColor: 'rgba(16, 24, 40, 0.08)',
                            borderWidth: 1,
                            callbacks: {
                                label: function (context) {
                                    var value = Number(context.raw) || 0;
                                    var pct = pcts[context.dataIndex] != null ? pcts[context.dataIndex] : 0;
                                    return ' ' + context.label + ': ' + value.toLocaleString() + ' (' + pct + '%)';
                                },
                            },
                        },
                    },
                },
            });
        }

        function renderCharts() {
            try {
                var data = readChartData();
                if (!data) {
                    return;
                }

                destroyCharts();
                chartInstances.photography = buildPie(
                    'franchise-dashboard-photography-chart',
                    data.photography
                );
                chartInstances.proofingStatus = buildPie(
                    'franchise-dashboard-proofing-status-chart',
                    data.proofingStatus
                );
            } catch (e) {
                // Never block Livewire updates if chart redraw fails.
            }
        }

        function initJobsTableById(tableId, opts) {
            opts = opts || {};
            var table = document.getElementById(tableId);
            if (!table || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
                return false;
            }

            var $table = window.jQuery(table);
            if (window.jQuery.fn.DataTable.isDataTable(table)) {
                try {
                    $table.DataTable().destroy();
                } catch (e) {
                    // ignore
                }
            }

            // When an external search input is supplied (e.g. the "Start typing a Job
            // name to filter by..." box in the panel header), drop DataTables' own
            // built-in search box from the toolbar so there isn't a duplicate.
            var domStr = opts.externalSearchId
                ? "<'fd-jobs-toolbar'<'fd-jobs-length'l>>" +
                  "<'fd-jobs-scroll't>" +
                  "<'fd-jobs-footer'<'fd-jobs-info'i><'fd-jobs-paginate'p>>"
                : "<'fd-jobs-toolbar'<'fd-jobs-length'l><'fd-jobs-search'f>>" +
                  "<'fd-jobs-scroll't>" +
                  "<'fd-jobs-footer'<'fd-jobs-info'i><'fd-jobs-paginate'p>>";

            try {
                var dt = $table.DataTable({
                    pageLength: 20,
                    lengthChange: true,
                    lengthMenu: [[5, 10, 20, 50, 100], [5, 10, 20, 50, 100]],
                    paging: true,
                    searching: true,
                    info: true,
                    autoWidth: false,
                    scrollX: false,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: 0 },
                    ],
                    dom: domStr,
                    language: {
                        lengthMenu: 'Display _MENU_',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 to 0 of 0 entries',
                        search: 'Search:',
                        paginate: {
                            previous: 'Previous',
                            next: 'Next',
                        },
                    },
                    drawCallback: function () {
                        var api = this.api();
                        var start = api.page.info().start;
                        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                            cell.innerHTML = String(start + i + 1);
                        });
                    },
                });

                if (opts.externalSearchId) {
                    var searchInput = document.getElementById(opts.externalSearchId);
                    if (searchInput && searchInput.dataset.bound !== '1') {
                        searchInput.dataset.bound = '1';
                        searchInput.addEventListener('keyup', function () {
                            dt.search(searchInput.value || '').draw();
                        });
                        searchInput.addEventListener('search', function () {
                            dt.search(searchInput.value || '').draw();
                        });
                    }
                }

                return true;
            } catch (e) {
                console.warn('Franchise dashboard DataTable init failed for #' + tableId, e);
                return false;
            }
        }

        function initJobsTable() {
            return initJobsTableById('fd-proofing-jobs-table', { externalSearchId: 'fd-proofing-jobs-search' });
        }

        function boot() {
            renderCharts();
            tablesBooted = initJobsTable() || tablesBooted;
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }
        }

        // Stacked scripts can run after DOMContentLoaded on UAT — still boot immediately.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
        window.addEventListener('load', function () {
            renderCharts();
            if (!tablesBooted) {
                boot();
            }
        });
        document.addEventListener('livewire:navigated', boot);
        document.addEventListener('livewire:load', boot);

        function onMorphUpdated() {
            renderCharts();
            initJobsTable();
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }
        }

        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('morph.updated', onMorphUpdated);
        } else {
            document.addEventListener('livewire:initialized', function () {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('morph.updated', onMorphUpdated);
                }
            });
        }
    })();
</script>
@endpush
