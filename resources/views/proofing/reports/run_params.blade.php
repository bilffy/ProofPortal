@extends('proofing.layouts.master')
@section('title', 'MyReports')

@section('content')

    @php
        $currentReportParam = $reportParams[$passedParamCount];
  
        // Load necessary session data
        $choiceFilerValue = '';
        if ($currentReportParam['keyField'] == 'ts_job_id') {
            $choiceFilerValue = Session::get('selectedJob')->ts_jobname;
        }
        // // Reverse routing logic for links
        $urlArray = request()->route()->parameters();
    @endphp

    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
            <div class="reports">
                <div class="card">
                    <div class="card-header">
                        <legend>{{ $reportName }}</legend>
                        <p>{{ $reportDescription }}</p>
                    </div>
                    <div class="card-body">
                        @php
                            $query = $currentReportParam['query'];
                            $k = last(explode('.', $currentReportParam['keyField']));
                            $v = last(explode('.', $currentReportParam['valueField']));
                        @endphp

                        <h3>
                            {{ __('Please select a :param', ['param' => $currentReportParam['name']]) }}
                        </h3>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="mb-0">
                                    <div class="row">
                                        <div class="col-md-1 col-sm-12 align-self-center">
                                            Filter:
                                        </div>
                                        <div class="col-md-5 col-sm-12">
                                            <input class="form-control" id="choice-name-filter" type="text"
                                                name="choice-name-filter"
                                                placeholder="{{ __('Start typing a :name name to filter by...', ['name' => $currentReportParam['name']]) }}"
                                                value="{{ $choiceFilerValue }}">
                                        </div>
                                        <div class="col-md-6 col-sm-12 align-self-center" id="choice-name-filter-feedback">
                                            &nbsp;
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        {{ $currentReportParam['name'] }}
                                    </th>
                                    <th scope="col" class="text-center">Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($query as $record)
                                    @php
                                        $title = isset($record->ts_season_id)
                                            ? __(':name (:season)', ['name' => $record->$v, 'season' => $seasonList[$record->ts_season_id]])
                                            : __(':name', ['name' => $record->$v]);

                                        $url = array_merge($urlArray, [$record->$k]);
                                        $url = url('reports/'.implode('/', $urlArray));
                                    @endphp
                                    <tr class="choice" data-choice-name="{{ strtolower($title) }}">
                                        <td class="align-middle">
                                            {{ $title }}
                                        </td>
                                        <td class="align-middle text-center">
                                            <a href="{{$url}}/{{$record->$k}}" class="btn btn-link">Select</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
    <script>
        $(document).ready(function () {
            jQuery.noConflict();
            filterChoicesTable();

            $('#choice-name-filter').keyup(filterChoicesTable);

            function filterChoicesTable() {
                let filterByTextOriginal = $('#choice-name-filter').val();
                let filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");

                if (filterByText.length >= 1) {
                    $(".choice").addClass("d-none");
                    let foundChoices = $("[data-choice-name*='" + filterByText + "']").removeClass("d-none");
                    let foundCount = foundChoices.length;
                    $("#choice-name-filter-feedback").text("Found " + foundCount + " records containing the text '" + filterByTextOriginal + "'.");
                } else {
                    $(".choice").removeClass("d-none");
                    $("#choice-name-filter-feedback").text("");
                }
            }
        });
    </script>
@stop

