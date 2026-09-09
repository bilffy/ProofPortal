@php
    $pct = function (int $value, int $total): int {
        return $total > 0 ? (int) round(($value / $total) * 100) : 0;
    };

    $palette = [
        'deepRed' => '#B71C1C',
        'darkOrange' => '#FB8C00',
        'lightGreen' => '#7CB342',
        'teal' => '#26A69A',
        'lightBlue' => '#42A5F5',
        'mediumBlue' => '#1E88E5',
        'navy' => '#0D47A1',
        'purple' => '#7B61FF',
    ];

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
    $totalUsers = max(0, (int) ($metrics['total_users'] ?? 0));
    $schoolsCount = max(0, (int) ($metrics['schools_count'] ?? 0));
    $synced = max(0, (int) ($metrics['synced'] ?? 0));
    $statusOpened = max(0, (int) ($metrics['status_opened'] ?? 0));
    $statusNotOpened = max(0, (int) ($metrics['status_not_opened'] ?? 0));
    $statusCompleted = max(0, (int) ($metrics['status_completed'] ?? 0));
    $statusArchived = max(0, (int) ($metrics['status_archived'] ?? 0));
    $statusDeleted = max(0, (int) ($metrics['status_deleted'] ?? 0));
    $stageNotStarted = max(0, (int) ($metrics['stage_not_started'] ?? 0));
    $stageActive = max(0, (int) ($metrics['stage_active'] ?? 0));
    $stageCompleted = max(0, (int) ($metrics['stage_completed'] ?? 0));
    $stageCatchup = max(0, (int) ($metrics['stage_catchup'] ?? 0));
    $foldersTotal = max(0, (int) ($metrics['folders_total'] ?? 0));
    $foldersCompleted = max(0, (int) ($metrics['folders_completed'] ?? 0));
    $foldersUnlockedModified = max(0, (int) ($metrics['folders_unlocked_modified'] ?? 0));

    $stageTotal = $stageNotStarted + $stageActive + $stageCompleted + $stageCatchup;
    $statusMixTotal = $statusOpened + $statusNotOpened + $statusCompleted + $statusArchived + $statusDeleted;
    $syncedJobsLabel = ($synced === 1) ? 'Synced Job' : 'Synced Jobs';
    $deletedJobsLabel = ($statusDeleted === 1) ? 'Deleted Job' : 'Deleted Jobs';

    $statusLegend = [
        ['label' => 'Opened', 'value' => $statusOpened, 'color' => '#2196F3'],
        ['label' => 'Not Opened', 'value' => $statusNotOpened, 'color' => '#26C6DA'],
        ['label' => 'Completed (Pending Archived)', 'value' => $statusCompleted, 'color' => '#8BC34A'],
        ['label' => 'Archived', 'value' => $statusArchived, 'color' => '#7B61FF'],
        ['label' => 'Deleted', 'value' => $statusDeleted, 'color' => '#E91E8C'],
    ];
    $photographyLegend = [
        ['label' => 'Configured', 'value' => $photographyConfigured, 'color' => '#E91E8C'],
        ['label' => 'Not Configured', 'value' => $photographyNotConfigured, 'color' => '#2196F3'],
    ];
    $folderLegend = [
        ['label' => 'Total Folders', 'value' => $foldersTotal, 'color' => $palette['mediumBlue']],
        ['label' => 'In Progress Folders', 'value' => $foldersUnlockedModified, 'color' => $palette['darkOrange']],
        ['label' => 'Completed Folders', 'value' => $foldersCompleted, 'color' => $palette['lightGreen']],
    ];

    $breakdowns = $breakdowns ?? [];
    $schoolsBreakdown = $breakdowns['schools'] ?? [];
    $photographyBreakdown = $breakdowns['photography'] ?? [];
    $activeProofingBreakdown = $breakdowns['active_proofing'] ?? [];
    $usersBreakdown = $breakdowns['users'] ?? [];
    $proofingStatusBreakdown = $breakdowns['proofing_status'] ?? [];
    $stagesBreakdown = $breakdowns['stages'] ?? [];
    $foldersBreakdown = $breakdowns['folders'] ?? [];
    $recentActivityBreakdown = $breakdowns['recent_activity'] ?? [];
    $recentActivity = $recentActivity ?? [];

    $franchiseName = $franchise->name ?? 'Franchise';

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

    $closeBtnClass = 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center';
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

    .sd-school-icon {
        width: 48px;
        height: 48px;
        border-radius: 9999px;
        background: #E8F1FB;
        color: #1E88E5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
        overflow: hidden;
        border: 1px solid #D7E3F0;
    }

    .sd-school-title {
        margin: 0;
        font-size: 1.5rem;
        line-height: 1.25;
        font-weight: 700;
        color: #1A2B4A;
        word-break: break-word;
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
        font-size: 0.8rem;
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

    .sd-season-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: -0.35rem 0 1.1rem;
    }

    .sd-season-pill {
        border: 1px solid #D0D7E2;
        background: #fff;
        color: #5B6575;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 9999px;
        padding: 0.35rem 0.85rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .sd-season-pill:hover {
        border-color: #1E88E5;
        color: #1E88E5;
        text-decoration: none;
    }

    .sd-season-pill.is-active {
        background: #1E88E5;
        border-color: #1E88E5;
        color: #fff;
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
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        background: #fff;
        border: 1px solid #E8EDF3;
        border-radius: 16px;
        padding: 1.1rem 1.15rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        min-height: 6.5rem;
        height: 100%;
        box-sizing: border-box;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .sd-kpi-card::after {
        content: '';
        position: absolute;
        right: -18px;
        bottom: -28px;
        width: 90px;
        height: 90px;
        border-radius: 9999px;
        opacity: 0.35;
        pointer-events: none;
    }

    .sd-kpi-card.is-blue::after { background: #90CAF9; }
    .sd-kpi-card.is-green::after { background: #A5D6A7; }
    .sd-kpi-card.is-teal::after { background: #80CBC4; }
    .sd-kpi-card.is-purple::after { background: #CE93D8; }
    .sd-kpi-card.is-orange::after { background: #FFCC80; }

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
    }

    .sd-kpi-card.is-blue .sd-kpi-icon { background: #77a9dbff; color: #fff; }
    .sd-kpi-card.is-green .sd-kpi-icon { background: #43A047; color: #fff; }
    .sd-kpi-card.is-teal .sd-kpi-icon { background: #5EA49D; color: #fff; }
    .sd-kpi-card.is-purple .sd-kpi-icon { background: #d4a2c3ff; color: #fff; }
    .sd-kpi-card.is-orange .sd-kpi-icon { background: #FB8C00; color: #fff; }

    .sd-kpi-body { min-width: 0; }
    .sd-kpi-label {
        margin: 0;
        font-size: 0.78rem;
        font-weight: 600;
        color: #5B6575;
    }
    .sd-kpi-value {
        margin: 0.2rem 0 0;
        font-size: 1.75rem;
        line-height: 1.1;
        font-weight: 700;
        color: #1A2B4A;
    }
    .sd-kpi-meta {
        margin: 0.4rem 0 0;
        font-size: 0.75rem;
        color: #7A8699;
    }

    .sd-charts-3,
    .sd-charts-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
        align-items: stretch;
    }

    @media (min-width: 976px) {
        .sd-charts-3,
        .sd-charts-bottom {
            grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .sd-charts-3 .sd-panel,
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
        min-height: 22rem;
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
        height: 14rem;
        width: 100%;
        max-width: 16rem;
        margin: 0 auto;
    }

    .sd-pie-layout .sd-progress-list {
        gap: 0.65rem;
    }

    .fd-jobs-panel {
        margin-top: 0.25rem;
        margin-bottom: 1.25rem;
    }

    .fd-jobs-panel .sd-panel-head {
        margin-bottom: 1rem;
    }

    .fd-jobs-hint {
        margin: 0 0 0.75rem;
        font-size: 0.8rem;
        color: #7A8699;
    }

    .fd-jobs-table-wrap {
        width: 100%;
        min-width: 0;
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
        font-size: 0.8rem;
        font-weight: 600;
        color: #5B6575;
        white-space: nowrap;
    }

    .fd-jobs-toolbar .dataTables_length select {
        min-width: 4.5rem;
        height: 2rem;
        padding: 0.2rem 0.45rem;
        border: 1px solid #D0D7E2;
        border-radius: 0.4rem;
        background: #fff;
        color: #1A2B4A;
    }

    .fd-jobs-toolbar .dataTables_filter input {
        height: 2rem;
        min-width: 14rem;
        max-width: 100%;
        margin-left: 0 !important;
        padding: 0.25rem 0.65rem;
        border: 1px solid #D0D7E2;
        border-radius: 0.4rem;
        background: #fff;
        color: #1A2B4A;
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
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.75rem 1rem;
        margin-top: 0.85rem;
        position: relative;
    }

    .fd-jobs-footer .dataTables_info,
    .fd-jobs-footer .fd-jobs-info {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
        width: auto !important;
        text-align: left !important;
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
    }

    .fd-jobs-footer .dataTables_paginate,
    .fd-jobs-footer .fd-jobs-paginate {
        float: none !important;
        margin: 0 auto !important;
        padding: 0 !important;
        width: auto !important;
        text-align: center !important;
    }

    @media (max-width: 768px) {
        .fd-jobs-footer {
            flex-direction: column;
            justify-content: center;
        }

        .fd-jobs-footer .dataTables_info,
        .fd-jobs-footer .fd-jobs-info {
            position: static;
            transform: none;
            text-align: center !important;
        }
    }

    .fd-jobs-table,
    .fd-jobs-table.dataTable {
        width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        font-size: 0.8rem;
        color: #1A2B4A;
        margin: 0 !important;
        border: 1px solid #E8EDF3 !important;
        background: #fff;
    }

    .fd-jobs-table.dataTable.no-footer {
        border-bottom: 1px solid #E8EDF3 !important;
    }

    .fd-jobs-table thead th,
    .fd-jobs-table.dataTable thead th {
        background: #F5F8FC !important;
        border: 1px solid #E8EDF3 !important;
        border-top: 0 !important;
        padding: 0.65rem 0.7rem;
        text-align: left;
        white-space: nowrap;
        font-weight: 700;
        color: #5B6575;
        vertical-align: middle;
    }

    .fd-jobs-table thead th:first-child,
    .fd-jobs-table.dataTable thead th:first-child {
        border-left: 0 !important;
    }

    .fd-jobs-table thead th:last-child,
    .fd-jobs-table.dataTable thead th:last-child {
        border-right: 0 !important;
    }

    .fd-jobs-table tbody td,
    .fd-jobs-table.dataTable tbody td {
        border: 1px solid #E8EDF3 !important;
        padding: 0.55rem 0.7rem;
        vertical-align: top;
        background: #fff;
    }

    .fd-jobs-table tbody td:first-child,
    .fd-jobs-table.dataTable tbody td:first-child {
        border-left: 0 !important;
    }

    .fd-jobs-table tbody td:last-child,
    .fd-jobs-table.dataTable tbody td:last-child {
        border-right: 0 !important;
    }

    .fd-jobs-table tbody tr:last-child td,
    .fd-jobs-table.dataTable tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .fd-jobs-table tbody tr.is-completed td {
        background: #F1F8E9;
    }

    .fd-jobs-table .is-incomplete {
        color: #B71C1C;
        font-weight: 700;
    }

    .fd-jobs-table .is-photo-configured {
        color: #2E7D32;
        font-weight: 700;
    }

    .fd-jobs-table .is-photo-not-configured {
        color: #EF6C00;
        font-weight: 700;
    }

    .fd-jobs-table .is-date-start.is-due {
        color: #2E7D32;
        font-weight: 700;
    }

    .fd-jobs-table .is-date-warning.is-due {
        color: #EF6C00;
        font-weight: 700;
    }

    .fd-jobs-table .is-date-due.is-due {
        color: #C62828;
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
        font-size: 0.8rem;
        color: #5B6575;
    }

    .fd-jobs-footer .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.55rem !important;
        margin: 0 0.1rem !important;
        border-radius: 0.35rem !important;
        border: 1px solid #D0D7E2 !important;
        background: #fff !important;
        color: #5B6575 !important;
    }

    .fd-jobs-footer .dataTables_paginate .paginate_button.current,
    .fd-jobs-footer .dataTables_paginate .paginate_button.current:hover {
        background: #1E88E5 !important;
        border-color: #1E88E5 !important;
        color: #fff !important;
    }

    .fd-jobs-footer .dataTables_paginate .paginate_button.disabled,
    .fd-jobs-footer .dataTables_paginate .paginate_button.disabled:hover {
        opacity: 0.45;
        cursor: default !important;
    }

    .fd-jobs-table thead .fa-play { color: #43A047; }
    .fd-jobs-table thead .fa-circle { color: #FB8C00; }
    .fd-jobs-table thead .fa-stop { color: #E53935; }

    .sd-detail-cards {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.25rem;
        align-items: stretch;
    }

    @media (min-width: 976px) {
        .sd-detail-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .sd-detail-panel {
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        min-height: 28rem;
        scroll-margin-top: 1.25rem;
        transition: box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .sd-detail-panel.is-highlighted {
        border-color: #90CAF9;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.16), 0 8px 24px rgba(16, 24, 40, 0.06);
    }

    .sd-detail-panel .sd-panel-head {
        margin-bottom: 0.9rem;
        align-items: flex-start;
    }

    .sd-detail-panel .sd-card-title {
        flex: 1 1 auto;
        white-space: normal;
        line-height: 1.35;
    }

    .fd-detail-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        align-items: center;
        margin-bottom: 0.85rem;
        padding: 0.65rem 0.75rem;
        background: #F5F8FC;
        border: 1px solid #E8EDF3;
        border-radius: 12px;
        flex-shrink: 0;
    }

    .fd-detail-filter-wrap {
        position: relative;
        flex: 1 1 220px;
        min-width: 0;
    }

    .fd-detail-filter-wrap > i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9AA3B2;
        font-size: 0.8rem;
        pointer-events: none;
    }

    .fd-detail-filter-input {
        width: 100%;
        border: 1px solid #D8DEE8;
        border-radius: 10px;
        padding: 0.55rem 0.75rem 0.55rem 2rem;
        font-size: 0.84rem;
        color: #1A2B4A;
        background: #fff;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .fd-detail-filter-input::placeholder {
        color: #9AA3B2;
    }

    .fd-detail-filter-input:focus {
        border-color: #90CAF9;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.12);
    }

    .fd-detail-filter-meta {
        font-size: 0.75rem;
        font-weight: 600;
        color: #5B6575;
        background: #fff;
        border: 1px solid #E8EDF3;
        border-radius: 9999px;
        padding: 0.28rem 0.7rem;
        white-space: nowrap;
    }

    .fd-detail-body {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 32rem;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.15rem 0.2rem 0.15rem 0;
        scrollbar-width: thin;
        scrollbar-color: #C5CDD8 transparent;
    }

    .fd-detail-body::-webkit-scrollbar {
        width: 6px;
    }

    .fd-detail-body::-webkit-scrollbar-thumb {
        background: #C5CDD8;
        border-radius: 999px;
    }

    .fd-detail-body .fd-job-groups {
        margin: 0;
        gap: 0.75rem;
    }

    .fd-detail-body .fd-job-school.is-page-hidden,
    .fd-detail-body .fd-job-school.is-filter-hidden {
        display: none;
    }

    .fd-detail-empty {
        display: none;
        margin: 0.75rem 0 0;
        text-align: center;
    }

    .fd-detail-empty.is-visible {
        display: block;
    }

    .fd-detail-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: auto;
        padding-top: 0.85rem;
        border-top: 1px solid #EEF2F6;
        flex-shrink: 0;
    }

    .fd-detail-page-info {
        font-size: 0.75rem;
        font-weight: 600;
        color: #7A8699;
    }

    .fd-detail-page-actions {
        display: flex;
        gap: 0.45rem;
    }

    .fd-detail-page-btn {
        border: 1px solid #E8EDF3;
        background: #F8FAFC;
        color: #1A2B4A;
        border-radius: 9999px;
        padding: 0.38rem 0.9rem;
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .fd-detail-page-btn:hover:not(:disabled) {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .sd-detail-panel.is-stage .fd-detail-page-btn:hover:not(:disabled) {
        background: #F3E8FF;
        border-color: #E9D5FF;
        color: #5E35B1;
    }

    .fd-detail-page-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Compact school rows inside bottom detail cards */
    #fd-proofing-status-card.sd-detail-panel .fd-status-school,
    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school {
        margin: 0;
        background: #fff;
        border: 1px solid #E8EDF3;
        border-radius: 12px;
        border-top: 1px solid #E8EDF3;
        border-left: 3px solid #42A5F5;
        box-shadow: none;
        padding: 0.75rem 0.85rem;
        overflow: hidden;
    }

    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school {
        border-left-color: #7B61FF;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-status-school-head,
    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school-head {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin: 0 0 0.65rem;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-status-school-label,
    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school-label {
        display: none;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-status-school-title,
    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school-title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #1A2B4A;
        line-height: 1.3;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-status-school-title span,
    #fd-proofing-stage-card.sd-detail-panel .fd-stage-school-title span {
        color: #7A8699;
        font-weight: 500;
        font-size: 0.78rem;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-category-sections,
    #fd-proofing-stage-card.sd-detail-panel .fd-category-sections {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.55rem;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-category-group,
    #fd-proofing-stage-card.sd-detail-panel .fd-category-group {
        flex: 1 1 11rem;
        min-width: 10rem;
        max-width: 100%;
        background: #F8FAFC;
        border-color: #EEF2F6;
        border-radius: 10px;
        padding: 0.55rem 0.7rem;
        box-sizing: border-box;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-category-tag,
    #fd-proofing-stage-card.sd-detail-panel .fd-category-tag {
        margin-bottom: 0.35rem;
    }

    #fd-proofing-status-card.sd-detail-panel .fd-category-jobs,
    #fd-proofing-stage-card.sd-detail-panel .fd-category-jobs {
        font-size: 0.8rem;
        padding-left: 1rem;
        margin: 0;
    }

    #fd-proofing-stage-card.sd-detail-panel .fd-job-dates {
        font-size: 0.72rem;
    }

    .sd-badge.is-status {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .sd-clickable:hover {
        box-shadow: 0 4px 14px rgba(30, 136, 229, 0.14);
        border-color: #BBDEFB;
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

    .sd-badge.is-stage {
        background: #EDE7F6;
        border-color: #D1C4E9;
        color: #5E35B1;
    }

    .sd-badge.is-roles {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
        font-weight: 700;
    }

    .sd-chart-box {
        position: relative;
        height: 11.5rem;
        min-width: 0;
    }

    .sd-chart-box.is-tall {
        height: 14rem;
    }

    .sd-donut-card {
        border-top: 3px solid #CFD8DC;
    }

    .sd-donut-card.is-green { border-top-color: #7CB342; }
    .sd-donut-card.is-red { border-top-color: #E53935; }
    .sd-donut-card.is-blue { border-top-color: #42a5f5; }
    .sd-donut-card.is-teal { border-top-color: #26A69A; }
    .sd-donut-card.is-stage { border-top-color: #7B61FF; }
    .sd-donut-card.is-role { border-top-color: #1E88E5; }
    .sd-donut-card.is-recent-activity { border-top-color: #4CC0AD; }

    .sd-card-title,
    .sd-roles-title,
    .sd-activity-heading {
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

    .sd-card-title-icon,
    .sd-roles-title-icon,
    .sd-activity-title-icon {
        width: 28px;
        height: 28px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.72rem;
        flex-shrink: 0;
    }

    .sd-card-title-icon.is-green { background: #7CB342; }
    .sd-card-title-icon.is-red { background: #E53935; }
    .sd-card-title-icon.is-blue { background: #42a5f5; }
    .sd-card-title-icon.is-teal { background: #26A69A; }
    .sd-card-title-icon.is-stage { background: #7B61FF; }
    .sd-roles-title-icon { background: #1E88E5; width: 30px; height: 30px; font-size: 0.78rem; }
    .sd-activity-title-icon { background: #4CC0AD; width: 30px; height: 30px; font-size: 0.78rem; }

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
        font-size: 0.8rem;
        color: #7A8699;
        font-weight: 600;
    }

    .sd-progress-list {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        flex: 1;
        min-height: 0;
    }

    .sd-progress-row .sd-progress-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
        font-size: 0.8rem;
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

    .sd-activity-scroll {
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
        max-height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 0.25rem;
        -webkit-overflow-scrolling: touch;
    }

    .sd-activity-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .sd-activity-scroll::-webkit-scrollbar-thumb {
        background: #D0D7E2;
        border-radius: 9999px;
    }

    .sd-activity-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .sd-activity-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .sd-activity-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.7rem 0;
        border-bottom: 1px solid #F0F2F5;
        min-width: 0;
    }

    .sd-activity-item:last-child {
        border-bottom: 0;
    }

    .sd-activity-icon {
        width: 38px;
        height: 38px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.9rem;
    }

    .sd-activity-copy {
        min-width: 0;
        flex: 1;
    }

    .sd-activity-title {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 700;
        color: #1A2B4A;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sd-activity-desc {
        margin: 0.15rem 0 0;
        font-size: 0.75rem;
        color: #7A8699;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sd-activity-time {
        font-size: 0.75rem;
        color: #9AA3B2;
        white-space: nowrap;
        margin-left: auto;
        flex-shrink: 0;
    }

    .sd-empty {
        text-align: center;
        color: #7A8699;
        font-size: 0.875rem;
        padding: 2rem 0;
        margin: 0;
    }

    .sd-roles-body {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        min-width: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    .sd-roles-chart-wrap {
        position: relative;
        width: 240px;
        height: 240px;
        flex: 0 0 240px;
        max-width: 240px;
        margin: 0;
    }

    .sd-roles-chart-wrap canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .sd-roles-legend {
        list-style: none;
        margin: 0;
        padding: 0.15rem 0;
        flex: 1 1 140px;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 1rem;
    }

    .sd-roles-legend li {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.84rem;
        color: #1A2B4A;
        min-width: 0;
    }

    .sd-roles-dot {
        width: 10px;
        height: 10px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .sd-roles-name {
        flex: 1;
        font-weight: 700;
        min-width: 0;
        color: #1A2B4A;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sd-roles-stat {
        font-weight: 700;
        white-space: nowrap;
        color: #1A2B4A;
        font-size: 0.84rem;
        flex-shrink: 0;
    }

    .sd-roles-stat span {
        font-weight: 500;
        color: #7A8699;
    }

    .fd-modal-table {
        width: 100%;
        border-collapse: collapse;
    }

    .fd-modal-table th,
    .fd-modal-table td {
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #EEF2F6;
        white-space: nowrap;
    }

    .fd-modal-table th:first-child,
    .fd-modal-table td:first-child {
        white-space: normal;
        min-width: 10rem;
        width: 20%;
    }

    .fd-modal-table th {
        font-size: 0.75rem;
        font-weight: 700;
        color: #5B6575;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        background: #F8FAFC;
    }

    .fd-modal-table td {
        font-size: 0.875rem;
        color: #1A2B4A;
    }

    .fd-modal-table td.num,
    .fd-modal-table th.num {
        text-align: right;
        white-space: nowrap;
    }

    .fd-modal-table tfoot td {
        font-weight: 700;
        background: #F8FAFC;
        border-bottom: 0;
    }

    .fd-activity-section {
        margin-bottom: 1.25rem;
    }

    .fd-activity-section:last-child {
        margin-bottom: 0;
    }

    .fd-activity-section h6 {
        margin: 0 0 0.65rem;
        font-size: 0.9rem;
        font-weight: 700;
        color: #1A2B4A;
    }

    .fd-activity-jobs {
        margin: 0.25rem 0 0;
        font-size: 0.78rem;
        color: #7A8699;
        white-space: normal;
    }

    .fd-modal-season {
        margin: 0 0 1rem;
        padding: 0.55rem 0.85rem;
        border-radius: 10px;
        background: #EEF4FF;
        border: 1px solid #D6E4FF;
        color: #1A2B4A;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .fd-modal-season span {
        color: #5B6575;
        font-weight: 500;
    }

    .fd-job-groups {
        margin-top: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .fd-job-school {
        border: 1px solid #E8EDF3;
        border-radius: 12px;
        background: #F8FAFC;
        padding: 0.85rem 1rem;
    }

    .fd-job-school-title {
        margin: 0 0 0.75rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1A2B4A;
    }

    .fd-job-school-title span {
        color: #7A8699;
        font-weight: 500;
        font-size: 0.8rem;
    }

    .fd-job-categories {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .fd-job-category {
        background: #fff;
        border: 1px solid #EEF2F6;
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
    }

    .fd-job-tag {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0.45rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: #E8EEF7;
        color: #374151;
    }

    .fd-job-list {
        margin: 0;
        padding-left: 1.1rem;
        color: #1A2B4A;
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .fd-job-list li {
        margin: 0.15rem 0;
        white-space: normal;
    }

    .fd-job-empty {
        margin: 0;
        color: #9AA3B2;
        font-size: 0.8rem;
        font-style: italic;
    }

    .fd-category-sections {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .fd-category-group {
        background: #fff;
        border: 1px solid #EEF2F6;
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
    }

    .fd-category-tag {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0.5rem;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }

    .fd-category-jobs,
    .fd-simple-job-list {
        margin: 0;
        padding-left: 1.1rem;
        color: #1A2B4A;
        font-size: 0.84rem;
        line-height: 1.5;
    }

    .fd-category-jobs li,
    .fd-simple-job-list li {
        margin: 0.2rem 0;
        white-space: normal;
    }

    .fd-job-dates {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.76rem;
        color: #7A8699;
        font-weight: 500;
    }

    .fd-simple-job-list {
        padding: 0.25rem 0 0.25rem 1.1rem;
    }

    .fd-category-tag.is-configured {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    .fd-category-tag.is-not-configured {
        background: #FFF3E0;
        border-color: #FFE0B2;
        color: #EF6C00;
    }

    .fd-category-tag.is-opened {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .fd-category-tag.is-not-opened {
        background: #E0F2F1;
        border-color: #B2DFDB;
        color: #00897B;
    }

    .fd-category-tag.is-completed {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    .fd-category-tag.is-archived {
        background: #E3EAF3;
        border-color: #CBD5E1;
        color: #0D47A1;
    }

    .fd-category-tag.is-deleted {
        background: #FFEBEE;
        border-color: #FFCDD2;
        color: #B71C1C;
    }

    .fd-category-tag.is-not-started {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .fd-category-tag.is-active {
        background: #EDE9FE;
        border-color: #DDD6FE;
        color: #7B61FF;
    }

    .fd-category-tag.is-catchup {
        background: #FFF3E0;
        border-color: #FFE0B2;
        color: #EF6C00;
    }

    .fd-category-tag.is-starting {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    .fd-folder-job {
        background: #fff;
        border: 1px solid #EEF2F6;
        border-radius: 10px;
        padding: 0.75rem 0.85rem;
        margin-bottom: 0.65rem;
    }

    .fd-folder-job:last-child {
        margin-bottom: 0;
    }

    .fd-folder-job-name {
        margin: 0 0 0.55rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1A2B4A;
        white-space: normal;
    }

    .fd-folder-job-counts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .fd-folder-job-counts li {
        font-size: 0.78rem;
        color: #5B6575;
    }

    .fd-folder-job-counts strong {
        color: #1A2B4A;
        font-weight: 700;
    }

    #fdFoldersModal .fd-folder-school {
        background: #fff;
        border: 1px solid #D6E4FF;
        border-top: 3px solid #1E88E5;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    #fdFoldersModal .fd-folder-school-head {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin: -0.85rem -1rem 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 9px 9px 0 0;
        background: linear-gradient(180deg, #EEF4FF 0%, #F8FAFC 100%);
        border-bottom: 1px solid #D6E4FF;
    }

    #fdFoldersModal .fd-folder-school-label,
    #fdFoldersModal .fd-folder-job-label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    #fdFoldersModal .fd-folder-school-label {
        background: #1E88E5;
        color: #fff;
    }

    #fdFoldersModal .fd-folder-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0D47A1;
    }

    #fdFoldersModal .fd-folder-school-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.82rem;
    }

    #fdFoldersModal .fd-folder-job {
        background: #F3F6FB;
        border: 1px solid #E3EAF3;
        border-left: 4px solid #7B61FF;
        padding: 0;
        overflow: hidden;
    }

    #fdFoldersModal .fd-folder-job-head {
        padding: 0.65rem 0.85rem;
        background: #fff;
        border-bottom: 1px solid #EEF2F6;
    }

    #fdFoldersModal .fd-folder-job-label {
        background: #EDE9FE;
        color: #7B61FF;
        margin-bottom: 0.35rem;
    }

    #fdFoldersModal .fd-folder-job-name {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1A2B4A;
    }

    #fdFoldersModal .fd-folder-job-counts {
        padding: 0.65rem 0.85rem;
        gap: 0.45rem;
    }

    #fdFoldersModal .fd-folder-job-counts li {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        border: 1px solid transparent;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-total {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-total strong {
        color: #0D47A1;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-progress {
        background: #FFF3E0;
        border-color: #FFE0B2;
        color: #EF6C00;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-progress strong {
        color: #E65100;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-completed {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    #fdFoldersModal .fd-folder-job-counts li.is-completed strong {
        color: #33691E;
    }

    #fdFoldersModal .fd-folder-job-list {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.65rem;
    }

    #fdFoldersModal .fd-folder-job {
        flex: 1 1 16rem;
        min-width: 14rem;
        max-width: 100%;
        margin-bottom: 0;
        box-sizing: border-box;
    }

    #fdRecentActivityModal .fd-category-sections {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.65rem;
    }

    #fdRecentActivityModal .fd-category-group {
        flex: 1 1 16rem;
        min-width: 14rem;
        max-width: 100%;
        box-sizing: border-box;
    }

    #fd-proofing-stage-card .fd-stage-school {
        background: #fff;
        border: 1px solid #D6E4FF;
        border-top: 3px solid #7B61FF;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    #fdRecentActivityModal .fd-activity-school {
        background: #fff;
        border: 1px solid #D6E4FF;
        border-top: 3px solid #43A047;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    #fdUsersModal .fd-users-school {
        background: #fff;
        border: 1px solid #FFE0B2;
        border-top: 3px solid #FB8C00;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    #fd-proofing-stage-card .fd-stage-school-head {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin: -0.85rem -1rem 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 9px 9px 0 0;
        background: linear-gradient(180deg, #F3E8FF 0%, #F8FAFC 100%);
        border-bottom: 1px solid #E9D5FF;
    }

    #fdRecentActivityModal .fd-activity-school-head {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin: -0.85rem -1rem 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 9px 9px 0 0;
        background: linear-gradient(180deg, #E8F5E9 0%, #F8FAFC 100%);
        border-bottom: 1px solid #C8E6C9;
    }

    #fdUsersModal .fd-users-school-head {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin: -0.85rem -1rem 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 9px 9px 0 0;
        background: linear-gradient(180deg, #FFF3E0 0%, #F8FAFC 100%);
        border-bottom: 1px solid #FFE0B2;
    }

    #fd-proofing-stage-card .fd-stage-school-label,
    #fd-proofing-stage-card .fd-stage-job-label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    #fd-proofing-stage-card .fd-stage-school-label {
        background: #7B61FF;
        color: #fff;
    }

    #fdRecentActivityModal .fd-activity-school-label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: #43A047;
        color: #fff;
    }

    #fdUsersModal .fd-users-school-label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: #FB8C00;
        color: #fff;
    }

    #fd-proofing-stage-card .fd-stage-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #5B21B6;
    }

    #fdRecentActivityModal .fd-activity-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #1B5E20;
    }

    #fdUsersModal .fd-users-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #E65100;
    }

    #fd-proofing-stage-card .fd-stage-school-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.82rem;
    }

    #fdRecentActivityModal .fd-activity-school-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.82rem;
    }

    #fdUsersModal .fd-users-school-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.82rem;
    }

    #fdUsersModal .fd-users-role-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    #fdUsersModal .fd-users-role-list li {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.7rem;
        border-radius: 8px;
        border: 1px solid transparent;
        font-size: 0.82rem;
        font-weight: 600;
    }

    #fdUsersModal .fd-users-role-list li strong {
        font-size: 0.95rem;
        font-weight: 700;
    }

    #fdUsersModal .fd-users-role-list li.is-admin {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    #fdUsersModal .fd-users-role-list li.is-coordinator {
        background: #EDE9FE;
        border-color: #DDD6FE;
        color: #6D28D9;
    }

    #fdUsersModal .fd-users-role-list li.is-teacher {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #2E7D32;
    }

    #fdSchoolsModal .fd-schools-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-bottom: 1rem;
        align-items: center;
    }

    #fdSchoolsModal .fd-schools-filter-input {
        flex: 1 1 220px;
        min-width: 0;
        border: 1px solid #D0D7E2;
        border-radius: 8px;
        padding: 0.55rem 0.75rem;
        font-size: 0.875rem;
        color: #1A2B4A;
        background: #fff;
        outline: none;
    }

    #fdSchoolsModal .fd-schools-filter-input:focus {
        border-color: #90CAF9;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.15);
    }

    #fdSchoolsModal .fd-schools-filter-meta {
        font-size: 0.8rem;
        color: #6B7280;
        white-space: nowrap;
    }

    #fdSchoolsModal .fd-schools-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 0.85rem;
    }

    #fdSchoolsModal .fd-school-card {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        background: #fff;
        border: 1px solid #D6E4FF;
        border-top: 3px solid #0D47A1;
        border-radius: 10px;
        padding: 0.85rem 1rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        cursor: pointer;
    }

    #fdSchoolsModal .fd-school-card:hover {
        border-color: #90CAF9;
        box-shadow: 0 4px 12px rgba(13, 71, 161, 0.12);
        transform: translateY(-1px);
    }

    #fdSchoolsModal .fd-school-card.is-hidden {
        display: none;
    }

    #fdSchoolsModal .fd-school-logo {
        flex: 0 0 56px;
        width: 56px;
        height: 56px;
        border-radius: 8px;
        border: 1px solid #E8EDF3;
        background: #F8FAFC;
        object-fit: contain;
        padding: 0.25rem;
    }

    #fdSchoolsModal .fd-school-logo-placeholder {
        flex: 0 0 56px;
        width: 56px;
        height: 56px;
        border-radius: 8px;
        border: 1px solid #E8EDF3;
        background: #E3EAF3;
        color: #0D47A1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    #fdSchoolsModal .fd-school-card-body {
        min-width: 0;
        flex: 1;
    }

    #fdSchoolsModal .fd-school-card-title {
        margin: 0 0 0.35rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0D47A1;
        line-height: 1.3;
    }

    #fdSchoolsModal .fd-school-card-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.8rem;
    }

    #fdSchoolsModal .fd-school-meta {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        color: #4B5563;
        font-size: 0.8rem;
    }

    #fdSchoolsModal .fd-school-meta strong {
        color: #374151;
        font-weight: 600;
        margin-right: 0.25rem;
    }

    #fdSchoolsModal .fd-schools-empty-filter {
        display: none;
        margin: 0.5rem 0 0;
    }

    #fdSchoolsModal .fd-schools-empty-filter.is-visible {
        display: block;
    }

    #fd-proofing-stage-card .fd-stage-job {
        background: #F3F6FB;
        border: 1px solid #E3EAF3;
        border-left: 4px solid #1E88E5;
        padding: 0;
        overflow: hidden;
        margin-bottom: 0.65rem;
    }

    #fd-proofing-stage-card .fd-stage-job:last-child {
        margin-bottom: 0;
    }

    #fd-proofing-stage-card .fd-stage-job-head {
        padding: 0.65rem 0.85rem;
        background: #fff;
        border-bottom: 1px solid #EEF2F6;
    }

    #fd-proofing-stage-card .fd-stage-job-label {
        background: #E3F2FD;
        color: #1E88E5;
        margin-bottom: 0.35rem;
    }

    #fd-proofing-stage-card .fd-stage-job-name {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1A2B4A;
        white-space: normal;
    }

    #fd-proofing-stage-card .fd-stage-job-counts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin: 0;
        padding: 0.65rem 0.85rem;
        list-style: none;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        border: 1px solid transparent;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-not-started {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-not-started strong {
        color: #0D47A1;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-active {
        background: #EDE9FE;
        border-color: #DDD6FE;
        color: #7B61FF;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-active strong {
        color: #5B21B6;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-completed {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-completed strong {
        color: #33691E;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-catchup {
        background: #FFF3E0;
        border-color: #FFE0B2;
        color: #EF6C00;
    }

    #fd-proofing-stage-card .fd-stage-job-counts li.is-catchup strong {
        color: #E65100;
    }

    #fdPhotographyModal .fd-photo-school,
    #fdActiveProofingModal .fd-active-school,
    #fd-proofing-status-card .fd-status-school {
        background: #fff;
        border: 1px solid #E8EDF3;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    #fdPhotographyModal .fd-photo-school {
        border-top: 3px solid #7CB342;
    }

    #fdActiveProofingModal .fd-active-school {
        border-top: 3px solid #7B61FF;
    }

    #fd-proofing-status-card .fd-status-school {
        border-top: 3px solid #42A5F5;
    }

    #fdPhotographyModal .fd-photo-school-head,
    #fdActiveProofingModal .fd-active-school-head,
    #fd-proofing-status-card .fd-status-school-head {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin: -0.85rem -1rem 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 9px 9px 0 0;
        border-bottom: 1px solid #E8EDF3;
    }

    #fdPhotographyModal .fd-photo-school-head {
        background: linear-gradient(180deg, #E8F5E9 0%, #F8FAFC 100%);
        border-bottom-color: #C8E6C9;
    }

    #fdActiveProofingModal .fd-active-school-head {
        background: linear-gradient(180deg, #F3E8FF 0%, #F8FAFC 100%);
        border-bottom-color: #E9D5FF;
    }

    #fd-proofing-status-card .fd-status-school-head {
        background: linear-gradient(180deg, #EEF4FF 0%, #F8FAFC 100%);
        border-bottom-color: #D6E4FF;
    }

    #fdPhotographyModal .fd-photo-school-label,
    #fdPhotographyModal .fd-photo-job-label,
    #fdActiveProofingModal .fd-active-school-label,
    #fdActiveProofingModal .fd-active-job-label,
    #fd-proofing-status-card .fd-status-school-label,
    #fd-proofing-status-card .fd-status-job-label {
        display: inline-flex;
        align-self: flex-start;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    #fdPhotographyModal .fd-photo-school-label {
        background: #7CB342;
        color: #fff;
    }

    #fdActiveProofingModal .fd-active-school-label {
        background: #7B61FF;
        color: #fff;
    }

    #fd-proofing-status-card .fd-status-school-label {
        background: #42A5F5;
        color: #fff;
    }

    #fdPhotographyModal .fd-photo-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #33691E;
    }

    #fdActiveProofingModal .fd-active-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #5B21B6;
    }

    #fd-proofing-status-card .fd-status-school-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0D47A1;
    }

    #fdPhotographyModal .fd-photo-school-title span,
    #fdActiveProofingModal .fd-active-school-title span,
    #fd-proofing-status-card .fd-status-school-title span {
        color: #5B6575;
        font-weight: 500;
        font-size: 0.82rem;
    }

    #fdPhotographyModal .fd-photo-job,
    #fdActiveProofingModal .fd-active-job,
    #fd-proofing-status-card .fd-status-job {
        background: #F3F6FB;
        border: 1px solid #E3EAF3;
        padding: 0;
        overflow: hidden;
        margin-bottom: 0.65rem;
    }

    #fdPhotographyModal .fd-photo-job {
        border-left: 4px solid #7CB342;
    }

    #fdActiveProofingModal .fd-active-job {
        border-left: 4px solid #7B61FF;
    }

    #fd-proofing-status-card .fd-status-job {
        border-left: 4px solid #1E88E5;
    }

    #fdPhotographyModal .fd-photo-job:last-child,
    #fdActiveProofingModal .fd-active-job:last-child,
    #fd-proofing-status-card .fd-status-job:last-child {
        margin-bottom: 0;
    }

    #fdPhotographyModal .fd-photo-job-head,
    #fdActiveProofingModal .fd-active-job-head,
    #fd-proofing-status-card .fd-status-job-head {
        padding: 0.65rem 0.85rem;
        background: #fff;
        border-bottom: 1px solid #EEF2F6;
    }

    #fdPhotographyModal .fd-photo-job-label {
        background: #E8F5E9;
        color: #558B2F;
        margin-bottom: 0.35rem;
    }

    #fdActiveProofingModal .fd-active-job-label {
        background: #EDE9FE;
        color: #7B61FF;
        margin-bottom: 0.35rem;
    }

    #fd-proofing-status-card .fd-status-job-label {
        background: #E3F2FD;
        color: #1E88E5;
        margin-bottom: 0.35rem;
    }

    #fdPhotographyModal .fd-photo-job-name,
    #fdActiveProofingModal .fd-active-job-name,
    #fd-proofing-status-card .fd-status-job-name {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1A2B4A;
        white-space: normal;
    }

    #fdPhotographyModal .fd-photo-job-counts,
    #fdActiveProofingModal .fd-active-job-counts,
    #fd-proofing-status-card .fd-status-job-counts {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin: 0;
        padding: 0.65rem 0.85rem;
        list-style: none;
    }

    #fdPhotographyModal .fd-photo-job-counts li,
    #fdActiveProofingModal .fd-active-job-counts li,
    #fd-proofing-status-card .fd-status-job-counts li {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        border: 1px solid transparent;
    }

    #fdPhotographyModal .fd-photo-job-counts li.is-configured {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    #fdPhotographyModal .fd-photo-job-counts li.is-configured strong {
        color: #33691E;
    }

    #fdPhotographyModal .fd-photo-job-counts li.is-not-configured {
        background: #FFF3E0;
        border-color: #FFE0B2;
        color: #EF6C00;
    }

    #fdPhotographyModal .fd-photo-job-counts li.is-not-configured strong {
        color: #E65100;
    }

    #fdPhotographyModal .fd-category-sections {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.65rem;
    }

    #fdPhotographyModal .fd-category-group {
        flex: 1 1 14rem;
        min-width: 12rem;
        max-width: 100%;
        box-sizing: border-box;
    }

    #fdActiveProofingModal .fd-active-job-counts li.is-active {
        background: #EDE9FE;
        border-color: #DDD6FE;
        color: #7B61FF;
    }

    #fdActiveProofingModal .fd-active-job-counts li.is-active strong {
        color: #5B21B6;
    }

    #fdActiveProofingModal .fd-simple-job-list {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.5rem;
        list-style: none;
        margin: 0;
        padding: 0.25rem 0 0;
    }

    #fdActiveProofingModal .fd-simple-job-list li {
        flex: 1 1 12rem;
        min-width: 10rem;
        max-width: 100%;
        margin: 0;
        padding: 0.55rem 0.7rem;
        background: #F8FAFC;
        border: 1px solid #EEF2F6;
        border-radius: 10px;
        box-sizing: border-box;
        font-size: 0.82rem;
        line-height: 1.4;
        color: #1A2B4A;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-synced {
        background: #E8EEF7;
        border-color: #CBD5E1;
        color: #475569;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-synced strong {
        color: #1A2B4A;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-opened {
        background: #E3F2FD;
        border-color: #BBDEFB;
        color: #1565C0;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-opened strong {
        color: #0D47A1;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-not-opened {
        background: #E0F2F1;
        border-color: #B2DFDB;
        color: #00897B;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-not-opened strong {
        color: #00695C;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-completed {
        background: #E8F5E9;
        border-color: #C8E6C9;
        color: #558B2F;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-completed strong {
        color: #33691E;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-archived {
        background: #E3EAF3;
        border-color: #CBD5E1;
        color: #0D47A1;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-archived strong {
        color: #0D47A1;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-deleted {
        background: #FFEBEE;
        border-color: #FFCDD2;
        color: #B71C1C;
    }

    #fd-proofing-status-card .fd-status-job-counts li.is-deleted strong {
        color: #B71C1C;
    }

    #fdPhotographyModal > div,
    #fdActiveProofingModal > div,
    #fdUsersModal > div,
    #fdSchoolsModal > div,
    #fdFoldersModal > div,
    #fdRecentActivityModal > div {
        width: calc(100vw - 2rem) !important;
        max-width: 96rem !important;
    }

    #fdPhotographyModal .modal-body,
    #fdActiveProofingModal .modal-body,
    #fdUsersModal .modal-body,
    #fdSchoolsModal .modal-body,
    #fdFoldersModal .modal-body,
    #fdRecentActivityModal .modal-body {
        overflow-x: visible;
    }

    @media (max-width: 975px) {
        .sd-charts-bottom .sd-panel {
            height: auto;
            min-height: 0;
            max-height: none;
            overflow: hidden;
        }

        .sd-charts-bottom .sd-panel.is-stage {
            min-height: 18rem;
        }

        .sd-charts-bottom .sd-panel.is-recent-activity {
            max-height: 22rem;
        }

        .sd-activity-scroll {
            max-height: 16rem;
            height: auto;
        }
    }

    @media (max-width: 480px) {
        .sd-page {
            padding: 1rem 0.85rem 1.5rem;
        }

        .sd-school-title {
            font-size: 1.25rem;
        }

        .sd-season {
            width: 100%;
        }

        .sd-season-select {
            flex: 1;
            min-width: 0;
        }

        .sd-kpi-value {
            font-size: 1.5rem;
        }
    }
</style>

<div class="sd-page">
    <div class="sd-header">
        <div class="sd-header-left">
            <span class="sd-school-icon">
                <x-icon icon="university" />
            </span>
            <div>
                <h5 class="sd-school-title">Resource Centre</h5>
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

    <div class="sd-season-pills">
        <a
            href="{{ route('franchise.dashboard') }}"
            class="sd-season-pill {{ $selectedSeasonId === '' ? 'is-active' : '' }}"
        >
            All seasons
        </a>
        @foreach ($seasons as $season)
            <a
                href="{{ route('franchise.dashboard', ['season' => $season->ts_season_id]) }}"
                class="sd-season-pill {{ (string) $selectedSeasonId === (string) $season->ts_season_id ? 'is-active' : '' }}"
            >
                {{ $season->code }}
            </a>
        @endforeach
    </div>

    <div wire:key="fd-metrics-{{ $selectedSeasonId === '' ? 'all' : $selectedSeasonId }}">
    <script type="application/json" id="franchise-dashboard-chart-data">@json($chartPayload)</script>

    <div class="sd-kpi-grid">
        <div
            class="sd-kpi-card is-teal sd-clickable cursor-pointer"
            title="Click for school list"
            data-modal-target="fdSchoolsModal"
            data-modal-toggle="fdSchoolsModal"
        >
            <span class="sd-kpi-icon"><x-icon icon="university" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Total Schools</p>
                <p class="sd-kpi-value">{{ number_format($schoolsCount) }}</p>
                <p class="sd-kpi-meta">In this franchise</p>
            </div>
        </div>

        <!-- <div
            class="sd-kpi-card is-orange sd-clickable cursor-pointer"
            title="Click for school breakdown"
            data-modal-target="fdUsersModal"
            data-modal-toggle="fdUsersModal"
        >
            <span class="sd-kpi-icon"><x-icon icon="users" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Total Users</p>
                <p class="sd-kpi-value">{{ number_format($totalUsers) }}</p>
                <p class="sd-kpi-meta">Across all schools</p>
            </div>
        </div> -->

        <div
            class="sd-kpi-card is-blue sd-clickable cursor-pointer"
            title="Click for school breakdown"
            data-modal-target="fdPhotographyModal"
            data-modal-toggle="fdPhotographyModal"
        >
            <span class="sd-kpi-icon"><x-icon icon="camera" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Photography Jobs</p>
                <p class="sd-kpi-value">{{ number_format($photographyTotal) }}</p>
                <p class="sd-kpi-meta">{{ number_format($photographyConfigured) }} Configured Jobs • {{ number_format($photographyNotConfigured) }} Not Configured Jobs</p>
            </div>
        </div>

        <div
            class="sd-kpi-card is-purple sd-clickable cursor-pointer"
            title="Click for school breakdown"
            data-modal-target="fdActiveProofingModal"
            data-modal-toggle="fdActiveProofingModal"
        >
            <span class="sd-kpi-icon"><x-icon icon="refresh" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Active Proofing Jobs</p>
                <p class="sd-kpi-value">{{ number_format($activeProofing) }}</p>
                <p class="sd-kpi-meta">{{ $selectedSeasonLabel }}</p>
            </div>
        </div>
    </div>

    <section>
        <div class="sd-charts-bottom">
            <div
                class="sd-panel sd-donut-card is-teal sd-clickable cursor-pointer"
                title="Click for school breakdown"
                data-modal-target="fdPhotographyModal"
                data-modal-toggle="fdPhotographyModal"
            >
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-teal"><x-icon icon="camera" /></span>
                        School Photography Status
                    </h6>
                    <span class="sd-badge">{{ $selectedSeasonLabel }}</span>
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

            <div class="sd-panel sd-donut-card is-blue">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-blue"><x-icon icon="shield" /></span>
                        Job Proofing Status
                    </h6>
                    <span class="sd-badge is-status">{{ $selectedSeasonLabel }}</span>
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

    @php
        $proofingJobsTable = $proofingJobsTable ?? [];
        $today = \Carbon\Carbon::today();
    @endphp

    <section class="sd-panel fd-jobs-panel">
        <div class="sd-panel-head">
            <h6 class="sd-card-title">
                <span class="sd-card-title-icon is-blue"><x-icon icon="table" /></span>
                Synced / Deleted Proofing Jobs
            </h6>
            <span class="sd-badge is-status">Season: {{ $selectedSeasonLabel }}</span>
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
                            {{-- <th><i class="fa fa-sort"></i> Photography Configured</th> --}}
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
                            @endphp
                            <tr class="{{ !empty($jobRow['is_completed']) ? 'is-completed' : '' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $jobRow['school_name'] }}</td>
                                <td>{{ $jobRow['ts_jobkey'] }}</td>
                                <td>{{ $jobRow['ts_jobname'] }}</td>
                                <td>{{ $jobRow['season_code'] }}</td>
                                {{-- <td class="{{ !empty($jobRow['photography_configured']) ? 'is-photo-configured' : 'is-photo-not-configured' }}">
                                    {{ !empty($jobRow['photography_configured']) ? 'Yes' : 'No' }}
                                </td> --}}
                                <td class="{{ $isIncomplete ? 'is-incomplete' : '' }}">{{ $statusName }}</td>
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

    @php
        $unsyncedProofingJobsTable = $unsyncedProofingJobsTable ?? [];
    @endphp

    <section class="sd-panel fd-jobs-panel">
        <div class="sd-panel-head">
            <h6 class="sd-card-title">
                <span class="sd-card-title-icon is-teal"><x-icon icon="table" /></span>
                Unsynced Proofing Jobs
            </h6>
            <span class="sd-badge">Season: {{ $selectedSeasonLabel }}</span>
        </div>
        <p class="fd-jobs-hint">Click headings to sort by that column.</p>

        @if (count($unsyncedProofingJobsTable) === 0)
            <p class="sd-empty">No unsynced proofing jobs found for this season.</p>
        @else
            <div class="fd-jobs-table-wrap">
                <table id="fd-unsynced-proofing-jobs-table" class="fd-jobs-table display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th><i class="fa fa-sort"></i> ID</th>
                            <th><i class="fa fa-sort"></i> School Name</th>
                            <th><i class="fa fa-sort"></i> Job Key</th>
                            <th><i class="fa fa-sort"></i> Job</th>
                            <th><i class="fa fa-sort"></i> Season</th>
                            {{-- <th><i class="fa fa-sort"></i> Photography Configured</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unsyncedProofingJobsTable as $index => $jobRow)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $jobRow['school_name'] }}</td>
                                <td>{{ $jobRow['ts_jobkey'] }}</td>
                                <td>{{ $jobRow['ts_jobname'] }}</td>
                                <td>{{ $jobRow['season_code'] }}</td>
                                {{-- <td class="{{ !empty($jobRow['photography_configured']) ? 'is-photo-configured' : 'is-photo-not-configured' }}">
                                    {{ !empty($jobRow['photography_configured']) ? 'Yes' : 'No' }}
                                </td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    </div> {{-- wire:key metrics --}}
</div> {{-- sd-page --}}

{{-- Photography Jobs Modal --}}
<x-modal.base id="fdPhotographyModal" title="Photography Jobs by Schools" size="max-w-[min(96rem,calc(100vw-2rem))]" body="components.modal.body" footer="components.modal.footer">
    <x-slot name="body">
        <x-modal.body class="overflow-y-auto overflow-x-hidden max-h-[70vh]">
            <p class="fd-modal-season"><span>Season:</span> {{ $selectedSeasonLabel }}</p>
            @if (count($photographyBreakdown) === 0)
                <p class="sd-empty">No photography jobs found.</p>
            @else
                @php
                    $photoCategories = [
                        ['key' => 'configured', 'label' => 'Configured', 'class' => 'is-configured'],
                        ['key' => 'not_configured', 'label' => 'Not Configured', 'class' => 'is-not-configured'],
                    ];
                @endphp
                <div class="fd-job-groups">
                    @foreach ($photographyBreakdown as $row)
                        <div class="fd-job-school fd-photo-school">
                            <div class="fd-photo-school-head">
                                <span class="fd-photo-school-label">School</span>
                                <h6 class="fd-photo-school-title">
                                    {{ $row['school'] }}
                                    @if (!empty($row['schoolkey']))
                                        <span>({{ $row['schoolkey'] }})</span>
                                    @endif
                                </h6>
                            </div>
                            <div class="fd-category-sections">
                                @foreach ($photoCategories as $category)
                                    @php $items = $row['jobs'][$category['key']] ?? []; @endphp
                                    @if (count($items) > 0)
                                        <div class="fd-category-group">
                                            <span class="fd-category-tag {{ $category['class'] }}">{{ $category['label'] }}</span>
                                            <ul class="fd-category-jobs">
                                                @foreach ($items as $label)
                                                    <li>{{ $label }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-modal.body>
    </x-slot>
    <x-slot name="footer">
        <x-modal.footer>
            <button type="button" data-modal-hide="fdPhotographyModal" class="{{ $closeBtnClass }}">Close</button>
        </x-modal.footer>
    </x-slot>
</x-modal.base>

{{-- Active Proofing Modal --}}
<x-modal.base id="fdActiveProofingModal" title="Active Proofing Jobs by Schools" size="max-w-[min(96rem,calc(100vw-2rem))]" body="components.modal.body" footer="components.modal.footer">
    <x-slot name="body">
        <x-modal.body class="overflow-y-auto overflow-x-hidden max-h-[70vh]">
            <p class="fd-modal-season"><span>Season:</span> {{ $selectedSeasonLabel }}</p>
            @if (count($activeProofingBreakdown) === 0)
                <p class="sd-empty">No active proofing jobs found.</p>
            @else
                <div class="fd-job-groups">
                    @foreach ($activeProofingBreakdown as $row)
                        <div class="fd-job-school fd-active-school">
                            <div class="fd-active-school-head">
                                <span class="fd-active-school-label">School</span>
                                <h6 class="fd-active-school-title">
                                    {{ $row['school'] }}
                                    @if (!empty($row['schoolkey']))
                                        <span>({{ $row['schoolkey'] }})</span>
                                    @endif
                                </h6>
                            </div>
                            <ul class="fd-simple-job-list">
                                @foreach (($row['jobs'] ?? []) as $label)
                                    <li>{{ $label }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-modal.body>
    </x-slot>
    <x-slot name="footer">
        <x-modal.footer>
            <button type="button" data-modal-hide="fdActiveProofingModal" class="{{ $closeBtnClass }}">Close</button>
        </x-modal.footer>
    </x-slot>
</x-modal.base>

{{-- Schools Modal --}}
<x-modal.base id="fdSchoolsModal" title="Schools" size="max-w-[min(96rem,calc(100vw-2rem))]" body="components.modal.body" footer="components.modal.footer">
    <x-slot name="body">
        <x-modal.body class="overflow-y-auto overflow-x-hidden max-h-[70vh]">
            @if (count($schoolsBreakdown) === 0)
                <p class="sd-empty">No schools found.</p>
            @else
                <div class="fd-schools-filter">
                    <input
                        type="search"
                        id="fd-schools-filter-input"
                        class="fd-schools-filter-input"
                        placeholder="Filter by school name or school key…"
                        autocomplete="off"
                        aria-label="Filter schools by name or school key"
                    >
                    <span class="fd-schools-filter-meta" id="fd-schools-filter-count">
                        {{ count($schoolsBreakdown) }} school{{ count($schoolsBreakdown) === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="fd-schools-list" id="fd-schools-list">
                    @foreach ($schoolsBreakdown as $row)
                        <a
                            class="fd-school-card"
                            href="{{ $row['url'] }}"
                            data-school-name="{{ strtolower($row['school'] ?? '') }}"
                            data-school-key="{{ strtolower($row['schoolkey'] ?? '') }}"
                            title="Open photography configure for {{ $row['school'] }}"
                        >
                            @if (!empty($row['school_logo']))
                                <img
                                    class="fd-school-logo"
                                    src="{{ $row['school_logo'] }}"
                                    alt="{{ $row['school'] }} logo"
                                    loading="lazy"
                                >
                            @else
                                <span class="fd-school-logo-placeholder" aria-hidden="true">
                                    <x-icon icon="university" />
                                </span>
                            @endif
                            <div class="fd-school-card-body">
                                <h6 class="fd-school-card-title">
                                    {{ $row['school'] }}
                                    @if (!empty($row['schoolkey']))
                                        <span>({{ $row['schoolkey'] }})</span>
                                    @endif
                                </h6>
                                <ul class="fd-school-meta">
                                    <li><strong>Suburb:</strong> {{ $row['suburb'] !== '' ? $row['suburb'] : '—' }}</li>
                                    <li><strong>Postcode:</strong> {{ $row['postcode'] !== '' ? $row['postcode'] : '—' }}</li>
                                    <li><strong>State:</strong> {{ $row['state'] !== '' ? $row['state'] : '—' }}</li>
                                </ul>
                            </div>
                        </a>
                    @endforeach
                </div>
                <p class="sd-empty fd-schools-empty-filter" id="fd-schools-empty-filter">No schools match your filter.</p>
            @endif
        </x-modal.body>
    </x-slot>
    <x-slot name="footer">
        <x-modal.footer>
            <button type="button" data-modal-hide="fdSchoolsModal" class="{{ $closeBtnClass }}">Close</button>
        </x-modal.footer>
    </x-slot>
</x-modal.base>

</div>

@push('scripts')
<link rel="stylesheet" href="{{ URL::asset('proofing-assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('proofing-assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
    (function () {
        var chartInstances = {
            photography: null,
            proofingStatus: null,
        };

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

        function initJobsTableById(tableId) {
            var table = document.getElementById(tableId);
            if (!table || typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
                return;
            }

            var $table = window.jQuery(table);
            if (window.jQuery.fn.DataTable.isDataTable(table)) {
                try {
                    $table.DataTable().destroy();
                } catch (e) {
                    // ignore
                }
            }

            $table.DataTable({
                pageLength: 5,
                lengthChange: true,
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                paging: true,
                searching: true,
                info: true,
                autoWidth: false,
                scrollX: false,
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: 0 },
                ],
                dom: "<'fd-jobs-toolbar'<'fd-jobs-length'l><'fd-jobs-search'f>>" +
                    "<'fd-jobs-scroll't>" +
                    "<'fd-jobs-footer'<'fd-jobs-info'i><'fd-jobs-paginate'p>>",
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
        }

        function initJobsTable() {
            initJobsTableById('fd-proofing-jobs-table');
            initJobsTableById('fd-unsynced-proofing-jobs-table');
        }

        function bindSchoolsFilter() {
            var input = document.getElementById('fd-schools-filter-input');
            if (!input || input.dataset.bound === '1') {
                return;
            }
            input.dataset.bound = '1';

            var applyFilter = function () {
                var query = String(input.value || '').trim().toLowerCase();
                var cards = document.querySelectorAll('#fd-schools-list .fd-school-card');
                var visible = 0;

                cards.forEach(function (card) {
                    var name = String(card.getAttribute('data-school-name') || '');
                    var key = String(card.getAttribute('data-school-key') || '');
                    var match = !query || name.indexOf(query) !== -1 || key.indexOf(query) !== -1;
                    card.classList.toggle('is-hidden', !match);
                    if (match) {
                        visible += 1;
                    }
                });

                var countEl = document.getElementById('fd-schools-filter-count');
                if (countEl) {
                    countEl.textContent = visible + ' school' + (visible === 1 ? '' : 's');
                }

                var emptyEl = document.getElementById('fd-schools-empty-filter');
                if (emptyEl) {
                    emptyEl.classList.toggle('is-visible', visible === 0);
                }
            };

            input.addEventListener('input', applyFilter);
            input.addEventListener('search', applyFilter);
        }

        function boot() {
            renderCharts();
            initJobsTable();
            bindSchoolsFilter();
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }
        }

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
        if (window.Livewire) {
            Livewire.hook('morph.updated', function () {
                renderCharts();
                initJobsTable();
                bindSchoolsFilter();
                if (typeof window.initFlowbite === 'function') {
                    window.initFlowbite();
                }
            });
        }
    })();
</script>
@endpush
