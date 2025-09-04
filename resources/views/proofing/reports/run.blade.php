@extends('proofing.layouts.master')

@section('title', $reportName)

@section('content')
@php
// use Illuminate\Support\Facades\Auth;
use App\Services\Proofing\SqlServerReportingServices;
use Illuminate\Support\Facades\Crypt;

$count = $reportQuery->count();

$result = $count > 0 ? SqlServerReportingServices::queryToCsvReport($reportQuery) : false;

$sqlParams = [
    [
        'sqlFriendly' => 'email',
        'urlKey' => 'email',
        'urlValue' => 
        // Auth::user()->email
        // 'itdept@msp.com.au',
        'blueprint_su_rc@msp.com.au',
    ]
];

$currentDate = now();
$downloadNameBuilder = $currentDate->format('Ymd-His') . ' - ' . $report->name;
$passedParamValues = array_values($passedParamValues);

foreach ($reportParams as $k => $reportParam) {
    $q = $reportParam['query']->select($reportParam['valueField'], $reportParam['keyField'])
                            ->where($reportParam['keyField'], $passedParamValues[$k])
                            ->first();

    $friendlyValue = $q[$reportParam['valueField']];

    $sqlParams[] = [
        'sqlFriendly' => strtolower(str_replace(".", "_", str_replace("s.", ".", $reportParam['keyField']))),
        'urlKey' => strtolower(str_replace(".", "", str_replace("s.", ".", $reportParam['keyField']))),
        'urlValue' => $passedParamValues[$k],
        'friendlyName' => $reportParam['name'],
        'friendlyValue' => $q[$reportParam['valueField']],
    ];

    $downloadNameBuilder .= " - " . $friendlyValue;
}

$ssrsParams = [];
foreach ($sqlParams as $sqlParam) {
    $ssrsParams[$sqlParam['urlKey']] = $sqlParam['urlValue'];
}

$reportUrl = [
    'csv' => SqlServerReportingServices::makeSsrsUrl([
        'ssrsServer' => config('settings.ssrs_server'),
        'ssrsFolder' => config('settings.ssrs_folder'),
        'ssrsReport' => str_replace(" ", "", $report->name),
        'format' => 'CSV',
        'params' => $ssrsParams,
    ]),
    'pdf' => SqlServerReportingServices::makeSsrsUrl([
        'ssrsServer' => config('settings.ssrs_server'),
        'ssrsFolder' => config('settings.ssrs_folder'),
        'ssrsReport' => str_replace(" ", "", $report->name),
        'format' => 'PDF',
        'params' => $ssrsParams,
    ]),
    'xlsx' => SqlServerReportingServices::makeSsrsUrl([
        'ssrsServer' => config('settings.ssrs_server'),
        'ssrsFolder' => config('settings.ssrs_folder'),
        'ssrsReport' => str_replace(" ", "", $report->name),
        'format' => 'EXCELOPENXML',
        'params' => $ssrsParams,
    ]),
    'xls' => SqlServerReportingServices::makeSsrsUrl([
        'ssrsServer' => config('settings.ssrs_server'),
        'ssrsFolder' => config('settings.ssrs_folder'),
        'ssrsReport' => str_replace(" ", "", $report->name),
        'format' => 'EXCEL',
        'params' => $ssrsParams,
    ]),
];

$ssrsParamsEncrypt = Crypt::encryptString(json_encode($ssrsParams));
$ssrsUsername = config('settings.ssrs_username');
$ssrsPassword = config('settings.ssrs_password');

@endphp

    <!-- Render Report Name and Description -->
    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
            <div class="reports">
                <div class="card">
                    <div class="card-header">
                        <legend>{{ $reportName }}</legend>
                        <p>{{ $reportDescription }}</p>
                        @foreach ($sqlParams as $sqlParam)
                            @if (isset($sqlParam['friendlyName']))
                                <strong>{{ $sqlParam['friendlyName'] }}:</strong> {{ $sqlParam['friendlyValue'] }}<br>
                            @endif
                        @endforeach
                    </div>

                    <div class="card-body">
                        @if ($count > 0)
                            <p class='mb-2'>{{ __('The report returned :count results.', ['count' => $count]) }}</p>
                            <p class='mb-2'>      
                                <form action="{{ route('report.download') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="format" value="CSV">
                                    <input type="hidden" name="report" value="{{str_replace(" ", "", $report->name)}}">
                                    <input type="hidden" name="params" value="{{ $ssrsParamsEncrypt }}">
                                    <input type="hidden" name="reportName" value="{{ $downloadNameBuilder }}">
                                    <input type="hidden" name="reportExtension" value="csv">
                                    <button type="submit" class="btn btn-primary mt-1 mr-3">{{ __('Download CSV') }}</button>
                                </form>
                                <form action="{{ route('report.download') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="format" value="PDF">
                                    <input type="hidden" name="report" value="{{str_replace(" ", "", $report->name)}}">
                                    <input type="hidden" name="params" value="{{ $ssrsParamsEncrypt }}">
                                    <input type="hidden" name="reportName" value="{{ $downloadNameBuilder }}">
                                    <input type="hidden" name="reportExtension" value="pdf">
                                    <button type="submit" class="btn btn-primary mt-1 mr-3">{{ __('Download PDF') }}</button>
                                </form>
                                <form action="{{ route('report.download') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="format" value="EXCELOPENXML">
                                    <input type="hidden" name="report" value="{{str_replace(" ", "", $report->name)}}">
                                    <input type="hidden" name="params" value="{{ $ssrsParamsEncrypt }}">
                                    <input type="hidden" name="reportName" value="{{ $downloadNameBuilder }}">
                                    <input type="hidden" name="reportExtension" value="xlsx">
                                    <button type="submit" class="btn btn-primary mt-1 mr-3">{{ __('Download Excel') }}</button>
                                </form>
                                <form action="{{ route('report.download') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="format" value="EXCEL">
                                    <input type="hidden" name="report" value="{{str_replace(" ", "", $report->name)}}">
                                    <input type="hidden" name="params" value="{{ $ssrsParamsEncrypt }}">
                                    <input type="hidden" name="reportName" value="{{ $downloadNameBuilder }}">
                                    <input type="hidden" name="reportExtension" value="xls">
                                    <button type="submit" class="btn btn-primary mt-1 mr-3">{{ __('Download Excel 2003') }}</button>
                                </form>
                            </p>
                        @else
                            <p class="mb-2">{{ __('Sorry, the report did not return any results.') }}</p>
                        @endif
                    </div>

                    <div class="card-footer">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary float-left">{{ __('Back') }}</a>
                        <a href="{{ route('reports') }}" class="btn btn-primary float-right">{{ __('All Reports') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- @if (Auth::user()->hasRole('superadmin') || Auth::user()->hasRole('admin')) --}}
        {{-- <div class="row">
            <div class="col-md-12 col-xl-8 m-xl-auto">
                <div class="card">
                    <div class="card-header">
                        <legend>Information for SSRS Reports</legend>
                        <p>Debug of the SQL Command for SSRS + Parameter Key and Value Examples.</p>
                    </div>
                    <div class="card-body">
                        <h3 class="mb-1">SSRS URL Parts</h3>
                        <p class="mb-1"><strong>Server:</strong> {{ config('settings.ssrs_server') }}</p>
                        <p class="mb-1"><strong>Folder:</strong> {{ config('settings.ssrs_folder') }}</p>
                        <p class="mb-1"><strong>Report:</strong> {{ $report->name }}</p>

                        <p class="mt-2 mb-0"><strong>Parameters:</strong></p>
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Plain SQL Syntax</th>
                                    <th scope="col">SSRS URL Key</th>
                                    <th scope="col">SSRS URL Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sqlParams as $index => $sqlParam)
                                    <tr>
                                        <th scope="row">{{ $index + 1 }}</th>
                                        <td>{{ $sqlParam['sqlFriendly'] }}</td>
                                        <td>{{ $sqlParam['urlKey'] }}</td>
                                        <td>{{ $sqlParam['urlValue'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <h3 class="mt-5 mb-1">SSRS URL Examples</h3>
                        <p class="mb-0">{{ $reportUrl['csv'] }}</p>
                        <span>{{ __('Download CSV') }}</span>
                        <p class="mt-5 mb-0">{{ $reportUrl['pdf'] }}</p>
                        <span>{{ __('Download PDF') }}</span>
                        <p class="mt-5 mb-1">{{ $reportUrl['xlsx'] }}</p>
                        <span>{{ __('Download EXCEL') }}</span>
                        <p class="mt-5 mb-1">{{ $reportUrl['xls'] }}</p>
                        <span>{{ __('Download EXCEL 2003') }}</span>

                        {{-- <h3 class="mt-5">File Structure</h3>
                        <pre>{{ print_r($result, true) }}</pre> --}}
                    </div>
                </div>
            </div>
        </div>
    {{-- @endif --}}

@endsection


@section('js')

@stop
