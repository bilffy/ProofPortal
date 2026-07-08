@extends('proofing.layouts.master')

@section('title', $reportName)

@section('content')
    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
            <div class="reports">
                <div class="card">
                    <div class="card-header">
                        <legend>{{ $reportName }}</legend>
                        <p>{{ $reportDescription }}</p>
                        <!-- <p class="mb-0 text-muted small">
                            {{ __('Report Server report:') }} <code>{{ $ssrsReportName }}</code>
                        </p> -->
                        @foreach ($sqlParams as $sqlParam)
                            @if (isset($sqlParam['friendlyName']))
                                <strong>{{ $sqlParam['friendlyName'] }}:</strong> {{ $sqlParam['friendlyValue'] }}<br>
                            @endif
                        @endforeach
                    </div>

                    <div class="card-body">
                        @if ($resultCount > 0)
                            <p class="mb-2">{{ __('The report returned :count results.', ['count' => $resultCount]) }}</p>
                            <p class="mb-2">
                                @foreach ([
                                    'csv' => __('Download CSV'),
                                    'pdf' => __('Download PDF'),
                                    'xlsx' => __('Download Excel (.xlsx)'),
                                    'xls' => __('Download Excel (.xls)'),
                                ] as $format => $label)
                                    <form action="{{ route('report.download') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="format" value="{{ $format }}">
                                        <input type="hidden" name="report" value="{{ $ssrsReportName }}">
                                        <input type="hidden" name="params" value="{{ $ssrsParamsEncrypt }}">
                                        <input type="hidden" name="reportName" value="{{ $downloadNameBuilder }}">
                                        <button type="submit" class="btn btn-primary mt-1 mr-3">{{ $label }}</button>
                                    </form>
                                @endforeach
                            </p>
                        @else
                            <p class="mb-2">{{ __('No Jobs have been synced for proofing yet.') }}</p>
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
@endsection

@section('js')
@stop
