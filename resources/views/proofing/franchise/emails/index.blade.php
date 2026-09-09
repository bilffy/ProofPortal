@extends('proofing.layouts.master')

@section('title', 'Emails')

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">{{ __('Emails') }}</h3>
    </div>

    <div class="row">
        <div class="col-md-12 col-xl-8 m-xl-auto">
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
                                            placeholder="{{ __('Start typing a Job name to filter by...') }}">
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
                                    @endphp
                                    <tr class="choice" data-choice-name="{{ strtolower($title) }}">
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
        </div>
    </div>
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
        });
    </script>
@stop
