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

                                        // Encrypt the parameter value for security
                                        $encryptedParamValue = Crypt::encryptString($record->$k);
                                        
                                        $url = array_merge($urlArray, [$encryptedParamValue]);
                                        $url = url('reports/'.implode('/', $urlArray));
                                    @endphp
                                    <tr class="choice" data-choice-name="{{ strtolower($title) }}">
                                        <td class="align-middle">
                                            {{ $title }}
                                        </td>
                                        <td class="align-middle text-center">
                                            <a href="{{$url}}/{{$encryptedParamValue}}" class="btn btn-link">Select</a>
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
                    // Hide all choices first
                    $(".choice").addClass("d-none").removeClass("exact-match");
                    
                    // Find all matching choices
                    let allMatches = $("[data-choice-name*='" + filterByText + "']");
                    
                    // Separate exact matches from partial matches
                    let exactMatches = [];
                    let partialMatches = [];
                    
                    allMatches.each(function() {
                        let choiceName = $(this).data('choice-name');
                        if (choiceName === filterByText) {
                            exactMatches.push(this);
                            $(this).addClass("exact-match");
                        } else {
                            partialMatches.push(this);
                        }
                    });
                    
                    // Show exact matches first, then partial matches
                    $(exactMatches).removeClass("d-none");
                    $(partialMatches).removeClass("d-none");
                    
                    let foundCount = allMatches.length;
                    let exactCount = exactMatches.length;
                    
                    if (exactCount > 0) {
                        $("#choice-name-filter-feedback").html(
                            "Found " + foundCount + " records containing '" + filterByTextOriginal + "'. " +
                            "<strong>" + exactCount + " exact match(es)</strong> shown first."
                        );
                    } else {
                        $("#choice-name-filter-feedback").text(
                            "Found " + foundCount + " records containing '" + filterByTextOriginal + "'."
                        );
                    }
                } else {
                    $(".choice").removeClass("d-none").removeClass("exact-match");
                    $("#choice-name-filter-feedback").text("");
                }
            }
        });
    </script>
    
    <style>
        /* Highlight exact matches */
        tr.exact-match {
            background-color: #e8f4f8 !important;
            border-left: 4px solid #0066cc;
        }
        tr.exact-match td {
            font-weight: 600;
        }
    </style>
@stop

