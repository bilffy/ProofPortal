@php
    $pct = function (int $value, int $total): int {
        return $total > 0 ? (int) round(($value / $total) * 100) : 0;
    };

    $syncTotal = max(0, (int) $metrics['synced']);
    $stageTotal = max(0, (int) $metrics['before_proofing'] + (int) $metrics['after_starting'] + (int) $metrics['after_finished'] + (int) $metrics['in_catchup']);
    $photographyTotal = max(0, (int) $metrics['photography']);

    $palette = [
        'deepRed' => '#B71C1C',
        'brightRed' => '#E53935',
        'darkOrange' => '#FB8C00',
        'goldenOrange' => '#FB8C00',
        'lime' => '#C0CA33',
        'lightGreen' => '#7CB342',
        'teal' => '#26A69A',
        'lightBlue' => '#42A5F5',
        'mediumBlue' => '#1E88E5',
        'darkBlue' => '#1565C0',
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

    $syncedPctOfPhoto = $pct((int) $metrics['synced'], max($photographyTotal, 1));
    $inProofingPctOfPhoto = $pct((int) $metrics['after_starting'], max($photographyTotal, 1));
    if ($photographyTotal === 0) {
        $syncedPctOfPhoto = $pct((int) $metrics['synced'], max($syncTotal, 1));
        $inProofingPctOfPhoto = $pct((int) $metrics['after_starting'], max((int) $metrics['total_proofing'], 1));
    }

    $photoLegend = [
        ['label' => 'Ready to view', 'value' => (int) $metrics['photography_ready'], 'color' => $palette['lightGreen']],
        ['label' => 'Waiting (in lab)', 'value' => (int) $metrics['photography_waiting'], 'color' => $palette['darkOrange']],
    ];
    $syncLegend = [
        ['label' => 'Synced', 'value' => (int) $metrics['synced'], 'color' => $palette['lightGreen']],
    ];
    $statusLegend = [
        ['label' => 'Opened', 'value' => (int) $metrics['opened'], 'color' => $palette['lightBlue']],
        ['label' => 'Archived', 'value' => (int) $metrics['archived'], 'color' => $palette['navy']],
        ['label' => 'Not Opened', 'value' => (int) $metrics['not_opened'], 'color' => $palette['teal']],
        ['label' => 'Deleted', 'value' => (int) $metrics['deleted'], 'color' => $palette['deepRed']],
    ];
    $statusMixTotal = max(0, (int) $metrics['opened'] + (int) $metrics['not_opened'] + (int) $metrics['archived'] + (int) $metrics['deleted']);
    $syncedJobsLabel = ((int) $metrics['synced'] === 1) ? 'Synced Job' : 'Synced Jobs';

    $schoolLogoUrl = '';
    if (!empty($school->school_logo)) {
        $logoRelativePath = \App\Helpers\SchoolLogoHelper::relativePath($school, $school->school_logo);
        $schoolLogoUrl = \App\Helpers\SchoolLogoHelper::publicUrl($school, $school->school_logo)
            ?? route('school.logo', ['encryptedPath' => \Illuminate\Support\Facades\Crypt::encryptString($logoRelativePath)]);
    }

    $chartPayload = [
        'stages' => [
            'labels' => ['Before Proofing', 'In Proofing', 'Finished Proofing', 'Catchup Jobs'],
            'values' => [
                (int) $metrics['before_proofing'],
                (int) $metrics['after_starting'],
                (int) $metrics['after_finished'],
                (int) $metrics['in_catchup'],
            ],
            'colors' => [$palette['mediumBlue'], $palette['purple'], $palette['lime'], $palette['goldenOrange']],
            'unit' => 'jobs',
        ],
        'roles' => [
            'labels' => ['School Admin', 'Photo Coordinator', 'Teacher'],
            'values' => [
                (int) $roleCounts['school_admin'],
                (int) $roleCounts['photo_coordinator'],
                (int) $roleCounts['teacher'],
            ],
            'colors' => [$palette['lightBlue'], $palette['teal'], $palette['navy']],
            'center' => ['value' => (int) $roleCounts['total'], 'label' => 'Users'],
            'unit' => 'users',
        ],
    ];
@endphp

<style>
    .sd-page {
        background: #F4F7FB;
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

    .sd-school-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #fff;
        display: block;
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

    @media (min-width: 640px) {
        .sd-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1100px) {
        .sd-kpi-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
    .sd-kpi-card.is-purple::after { background: #CE93D8; }
    .sd-kpi-card.is-orange::after { background: #FFCC80; }

    .sd-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
    }

    .sd-kpi-card.is-blue .sd-kpi-icon { background: #E3F2FD; color: #1E88E5; }
    .sd-kpi-card.is-green .sd-kpi-icon { background: #E8F5E9; color: #43A047; }
    .sd-kpi-card.is-purple .sd-kpi-icon { background: #F3E8FF; color: #7B61FF; }
    .sd-kpi-card.is-orange .sd-kpi-icon { background: #FFF3E0; color: #EF6C00; }

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
        .sd-charts-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .sd-charts-bottom {
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
    }

    .sd-charts-3 .sd-panel,
    .sd-charts-bottom .sd-panel {
        margin-bottom: 0;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    /* Fixed row height so activity scrolls and charts stay aligned */
    .sd-charts-bottom .sd-panel {
        height: 24rem;
        max-height: 24rem;
        min-height: 24rem;
        overflow: hidden;
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

    @media (max-width: 975px) {
        .sd-charts-bottom .sd-panel {
            height: auto;
            min-height: 0;
            max-height: none;
            overflow: hidden;
        }

        .sd-charts-bottom .sd-panel.is-role {
            min-height: 18rem;
        }

        .sd-charts-bottom .sd-panel.is-stage {
            min-height: 18rem;
        }

        .sd-charts-bottom .sd-panel.is-recent-activity {
            max-height: 22rem;
        }

        .sd-roles-body {
            flex-wrap: wrap;
            justify-content: center;
            padding: 0.25rem 0 0.5rem;
        }

        .sd-roles-chart-wrap {
            width: 220px;
            height: 220px;
            flex: 0 0 220px;
            max-width: 220px;
            margin: 0 auto;
        }

        .sd-roles-legend {
            flex: 1 1 100%;
            max-width: 100%;
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
                @if ($schoolLogoUrl !== '')
                    <img src="{{ $schoolLogoUrl }}" alt="{{ $school->name }} logo">
                @else
                    <x-icon icon="university" />
                @endif
            </span>
            <div>
                <h5 class="sd-school-title">{{ $school->name }}</h5>
                <p class="sd-school-sub">Overview of your users, photography and proofing activities</p>
            </div>
        </div>

        <div class="sd-season">
            <label for="school-dashboard-season">Season</label>
            <select
                id="school-dashboard-season"
                class="sd-season-select"
                onchange="window.location.href = this.value;"
            >
                <option
                    value="{{ route('school.dashboard') }}"
                    @selected($selectedSeasonId === '')
                >
                    All seasons
                </option>
                @foreach ($seasons as $season)
                    <option
                        value="{{ route('school.dashboard', ['season' => $season->ts_season_id]) }}"
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
            href="{{ route('school.dashboard') }}"
            class="sd-season-pill {{ $selectedSeasonId === '' ? 'is-active' : '' }}"
        >
            All seasons
        </a>
        @foreach ($seasons as $season)
            <a
                href="{{ route('school.dashboard', ['season' => $season->ts_season_id]) }}"
                class="sd-season-pill {{ (string) $selectedSeasonId === (string) $season->ts_season_id ? 'is-active' : '' }}"
            >
                {{ $season->code }}
            </a>
        @endforeach
    </div>

    <div wire:key="sd-metrics-{{ $selectedSeasonId === '' ? 'all' : $selectedSeasonId }}">
    <script type="application/json" id="school-dashboard-chart-data">@json($chartPayload)</script>

    <div class="sd-kpi-grid">
        <div class="sd-kpi-card is-blue">
            <span class="sd-kpi-icon"><x-icon icon="camera" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Jobs Ready to View</p>
                <p class="sd-kpi-value">{{ number_format($metrics['photography_ready']) }}</p>
                <p class="sd-kpi-meta">{{ $selectedSeasonLabel }}</p>
            </div>
        </div>

        <div class="sd-kpi-card is-green">
            <span class="sd-kpi-icon"><x-icon icon="check" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Synced Jobs</p>
                <p class="sd-kpi-value">{{ number_format($metrics['synced']) }}</p>
                <p class="sd-kpi-meta">{{ $syncedPctOfPhoto }}% of total</p>
            </div>
        </div>

        <div class="sd-kpi-card is-purple">
            <span class="sd-kpi-icon"><x-icon icon="refresh" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">In Proofing</p>
                <p class="sd-kpi-value">{{ number_format($metrics['after_starting']) }}</p>
                <p class="sd-kpi-meta">{{ $inProofingPctOfPhoto }}% of total</p>
            </div>
        </div>

        <div class="sd-kpi-card is-orange">
            <span class="sd-kpi-icon"><x-icon icon="users" /></span>
            <div class="sd-kpi-body">
                <p class="sd-kpi-label">Total Users</p>
                <p class="sd-kpi-value">{{ number_format($roleCounts['total']) }}</p>
                <p class="sd-kpi-meta">Active in system</p>
            </div>
        </div>
    </div>

    <section>
        <div class="sd-charts-3">
            <div class="sd-panel sd-donut-card is-green">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-green"><x-icon icon="camera" /></span>
                        Photography Jobs
                    </h6>
                </div>
                <div class="sd-progress-summary">
                    <strong>{{ number_format($metrics['photography']) }}</strong>
                    <span>Total Jobs</span>
                </div>
                <div class="sd-progress-list">
                    @foreach ($photoLegend as $item)
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

            <div class="sd-panel sd-donut-card is-green">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-green"><x-icon icon="image" /></span>
                        Proofing - Synced Jobs
                    </h6>
                </div>
                <div class="sd-progress-summary">
                    <strong>{{ number_format($syncTotal) }}</strong>
                    <span>Total Jobs</span>
                </div>
                <div class="sd-progress-list">
                    @foreach ($syncLegend as $item)
                        @php $itemPct = $pct($item['value'], max($syncTotal, 1)); @endphp
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

            <div class="sd-panel sd-donut-card is-blue">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-blue"><x-icon icon="shield" /></span>
                        Proofing Status of Synced Jobs
                    </h6>
                </div>
                <div class="sd-progress-summary">
                    <strong>{{ number_format($metrics['synced']) }}</strong>
                    <span>{{ $syncedJobsLabel }}</span>
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
        </div>
    </section>

    <section>
        <div class="sd-charts-bottom">
            @php
                $roleLegend = [
                    [
                        'label' => 'School Admin',
                        'value' => (int) $roleCounts['school_admin'],
                        'color' => $palette['lightBlue'],
                    ],
                    [
                        'label' => 'Photo Coordinator',
                        'value' => (int) $roleCounts['photo_coordinator'],
                        'color' => $palette['teal'],
                    ],
                    [
                        'label' => 'Teacher',
                        'value' => (int) $roleCounts['teacher'],
                        'color' => $palette['navy'],
                    ],
                ];
                $roleTotal = max(1, (int) $roleCounts['total']);
            @endphp

            <div class="sd-panel sd-donut-card is-stage">
                <div class="sd-panel-head">
                    <h6 class="sd-card-title">
                        <span class="sd-card-title-icon is-stage"><x-icon icon="bar-chart" /></span>
                        Jobs by Proofing Stage
                    </h6>
                    <span class="sd-badge is-stage">
                        Total Jobs in Proofing: {{ number_format($stageTotal) }}
                    </span>
                </div>
                <div class="sd-chart-box is-tall" style="flex: 1; height: auto; min-height: 0;">
                    <canvas id="school-dashboard-stage-chart"></canvas>
                </div>
            </div>

            <div class="sd-panel sd-donut-card is-role" wire:key="sd-roles-{{ $selectedSeasonId === '' ? 'all' : $selectedSeasonId }}">
                <div class="sd-panel-head">
                    <h6 class="sd-roles-title">
                        <span class="sd-roles-title-icon"><x-icon icon="users" /></span>
                        Users by Role
                    </h6>
                    <span class="sd-badge is-roles">Total Users: {{ number_format($roleCounts['total']) }}</span>
                </div>
                <div class="sd-roles-body">
                    <div class="sd-roles-chart-wrap" wire:ignore>
                        <canvas id="school-dashboard-roles-chart"></canvas>
                    </div>
                    <ul class="sd-roles-legend">
                        @foreach ($roleLegend as $role)
                            @php $rolePct = (int) round(($role['value'] / $roleTotal) * 100); @endphp
                            <li>
                                <span class="sd-roles-dot" style="background: {{ $role['color'] }};"></span>
                                <span class="sd-roles-name">{{ $role['label'] }}</span>
                                <span class="sd-roles-stat">
                                    {{ number_format($role['value']) }}
                                    <span>({{ $rolePct }}%)</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="sd-panel sd-donut-card is-recent-activity">
                <div class="sd-panel-head">
                    <h6 class="sd-activity-heading">
                        <span class="sd-activity-title-icon"><x-icon icon="bolt" /></span>
                        Recent Activity
                    </h6>
                </div>

                @if (count($recentActivity) === 0)
                    <p class="sd-empty">No recent activity for this school.</p>
                @else
                    <div class="sd-activity-scroll">
                        <ul class="sd-activity-list">
                            @foreach ($recentActivity as $activity)
                                <li class="sd-activity-item">
                                    <span
                                        class="sd-activity-icon"
                                        style="background-color: {{ $activity['color'] }}1A; color: {{ $activity['color'] }};"
                                    >
                                        <x-icon icon="{{ $activity['icon'] }}" />
                                    </span>
                                    <div class="sd-activity-copy">
                                        <p class="sd-activity-title">{{ $activity['title'] }}</p>
                                        <p class="sd-activity-desc">{{ $activity['description'] }}</p>
                                    </div>
                                    <span class="sd-activity-time">{{ $activity['time'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var chartInstances = {
            stage: null,
            roles: null,
        };

        function readChartData() {
            var el = document.getElementById('school-dashboard-chart-data');
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

        function centerTextPlugin(center) {
            return {
                id: 'centerText',
                afterDraw: function (chart) {
                    if (!center) {
                        return;
                    }

                    var ctx = chart.ctx;
                    var meta = chart.getDatasetMeta(0);
                    if (!meta || !meta.data || !meta.data.length) {
                        return;
                    }

                    var x = meta.data[0].x;
                    var y = meta.data[0].y;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = '#1A2B4A';
                    ctx.font = 'bold 24px Montserrat, sans-serif';
                    ctx.fillText(String(center.value != null ? center.value : ''), x, y - 8);
                    ctx.fillStyle = '#7A8699';
                    ctx.font = '12px Montserrat, sans-serif';
                    ctx.fillText(center.label || '', x, y + 14);
                    ctx.restore();
                },
            };
        }

        function buildRolesRadial(canvasId, labels, values, colors, center) {
            var canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') {
                return null;
            }

            var track = '#E9ECEF';
            var scale = Math.max.apply(null, values.concat([1]));
            var datasets = values.map(function (value, index) {
                var filled = Math.max(0, Number(value) || 0);
                var remaining = Math.max(0, scale - filled);

                return {
                    label: labels[index],
                    data: [filled, remaining],
                    backgroundColor: [colors[index], track],
                    borderWidth: 0,
                    hoverOffset: 0,
                    weight: 1,
                };
            });

            return new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['Filled', 'Remaining'],
                    datasets: datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '52%',
                    rotation: -110,
                    circumference: 270,
                    spacing: 2,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            filter: function (item) {
                                return item.dataIndex === 0;
                            },
                            callbacks: {
                                label: function (context) {
                                    var value = Number(context.raw) || 0;
                                    var total = (values || []).reduce(function (a, b) {
                                        return a + (Number(b) || 0);
                                    }, 0);
                                    var pct = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return ' ' + context.dataset.label + ': ' + value + ' (' + pct + '%)';
                                },
                            },
                        },
                    },
                },
                plugins: [centerTextPlugin(center)],
            });
        }

        function buildBar(canvasId, labels, values, colors, unitLabel) {
            var canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') {
                return null;
            }

            var barColors = Array.isArray(colors) && colors.length
                ? colors
                : ['#1E88E5', '#7B61FF', '#C0CA33', '#FB8C00'];
            var nums = (values || []).map(function (v) { return Number(v) || 0; });
            var total = nums.reduce(function (sum, v) { return sum + v; }, 0);
            var labeledTicks = (labels || []).map(function (label, i) {
                return String(label) + ' (' + nums[i].toLocaleString() + ')';
            });
            var pcts = nums.map(function (v) {
                return total > 0 ? Math.round((v / total) * 100) : 0;
            });

            var pctLabelsPlugin = {
                id: 'stagePctLabels',
                afterDatasetsDraw: function (chart) {
                    var c = chart.ctx;
                    var meta = chart.getDatasetMeta(0);
                    if (!meta || !meta.data) {
                        return;
                    }
                    meta.data.forEach(function (bar, index) {
                        var pct = pcts[index] != null ? pcts[index] : 0;
                        c.save();
                        c.fillStyle = '#5B6575';
                        c.font = '600 12px Montserrat, sans-serif';
                        c.textAlign = 'left';
                        c.textBaseline = 'middle';
                        c.fillText(pct + '%', bar.x + 8, bar.y);
                        c.restore();
                    });
                },
            };

            return new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labeledTicks,
                    datasets: [{
                        label: 'Jobs',
                        data: nums,
                        backgroundColor: barColors,
                        hoverBackgroundColor: barColors,
                        borderColor: 'transparent',
                        borderWidth: 0,
                        borderSkipped: false,
                        borderRadius: {
                            topLeft: 0,
                            bottomLeft: 0,
                            topRight: 8,
                            bottomRight: 8,
                        },
                        maxBarThickness: 28,
                        categoryPercentage: 0.7,
                        barPercentage: 0.85,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 4, right: 40, bottom: 0, left: 0 } },
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
                            caretSize: 6,
                            caretPadding: 8,
                            callbacks: {
                                title: function (items) {
                                    if (!items || !items.length) {
                                        return '';
                                    }
                                    var i = items[0].dataIndex;
                                    return labels[i] != null ? String(labels[i]) : '';
                                },
                                label: function (context) {
                                    var value = context.parsed && context.parsed.x != null
                                        ? Number(context.parsed.x)
                                        : (Number(context.raw) || 0);
                                    var pct = pcts[context.dataIndex] != null ? pcts[context.dataIndex] : 0;
                                    return ' Jobs: ' + value.toLocaleString() + ' (' + pct + '%)';
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grace: '18%',
                            ticks: {
                                precision: 0,
                                font: { size: 11, family: 'Montserrat, sans-serif' },
                                color: '#6B7280',
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.35)',
                                drawBorder: false,
                            },
                            border: { display: false },
                        },
                        y: {
                            ticks: {
                                font: { size: 12, weight: '500', family: 'Montserrat, sans-serif' },
                                color: '#374151',
                                padding: 8,
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.28)',
                                drawBorder: false,
                            },
                            border: { display: false },
                        },
                    },
                },
                plugins: [pctLabelsPlugin],
            });
        }

        function renderCharts() {
            try {
                var data = readChartData();
                if (!data || !data.stages || !data.roles) {
                    return;
                }

                destroyCharts();
                chartInstances.stage = buildBar(
                    'school-dashboard-stage-chart',
                    data.stages.labels,
                    data.stages.values,
                    data.stages.colors,
                    'jobs'
                );
                chartInstances.roles = buildRolesRadial(
                    'school-dashboard-roles-chart',
                    data.roles.labels,
                    data.roles.values,
                    data.roles.colors,
                    data.roles.center
                );
            } catch (e) {
                // Never block Livewire updates if chart redraw fails.
            }
        }

        function boot() {
            renderCharts();
        }

        function registerLivewireHooks() {
            if (!window.Livewire || window.__schoolDashboardLwHooks) {
                return;
            }
            window.__schoolDashboardLwHooks = true;

            Livewire.hook('morph.updated', function () {
                setTimeout(renderCharts, 0);
            });

            Livewire.hook('commit', function (args) {
                var succeed = args && args.succeed;
                if (typeof succeed !== 'function') {
                    return;
                }
                succeed(function () {
                    setTimeout(renderCharts, 0);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
        document.addEventListener('livewire:init', registerLivewireHooks);
        registerLivewireHooks();
    })();
</script>
@endpush
