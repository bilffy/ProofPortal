@extends('proofing.layouts.master')

@section('title', 'Emails')

@section('css')
<style>
    #emails-messages-table thead th {
        color: #808080;
    }
    #emails-messages-table .btn-link {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Emails — {{ $jobTitle }}</h3>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="mb-4">
                        <h5 class="text-black d-inline mr-2">Emails for this Job</h5>
                        <span class="text-muted">
                            - There are
                            <strong id="emails-total-count">{{ $emails->total() }}</strong>
                            emails.
                        </span>
                    </div>
                    <div class="row mt-3 mb-1">
                        <div class="col-lg-4">
                            <div class="form-group mb-2 mb-lg-0">
                                <label for="email-filter" class="mb-0">Keyword Search</label>
                                <input type="search" class="form-control" id="email-filter" placeholder="Start typing to filter by to, from, or content..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group mb-2 mb-lg-0">
                                <label for="email-filter-limit" class="mb-0">Limit Results</label>
                                <select class="form-control" id="email-filter-limit">
                                    @foreach ([25, 50, 100] as $limit)
                                        <option value="{{ $limit }}" @selected($limit === 25)>{{ $limit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group mb-2 mb-lg-0">
                                <label for="email-filter-order-by" class="mb-0">Order By</label>
                                <select class="form-control" id="email-filter-order-by">
                                    <option value="created_at">Created</option>
                                    <option value="sentdate">Sent</option>
                                    <option value="email_to">To</option>
                                    <option value="email_from">From</option>
                                    <option value="template_id">Template</option>
                                    <option value="status_id">Status</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group mb-0">
                                <label for="email-filter-order-direction" class="mb-0">Order Direction</label>
                                <select class="form-control" id="email-filter-order-direction">
                                    <option value="ascending">Ascending</option>
                                    <option value="descending" selected>Descending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="email-filter-response">
                        @include('proofing.franchise.emails._results', ['messages' => $emails])
                    </div>

                    <div class="paginator mt-3" id="email-paginator">
                        {{ $emails->links('proofing.layouts.pagination-custom') }}

                        @if ($emails->total())
                            <p class="mt-2 text-muted">
                                {{ __('Page :page of :pages, showing :current record(s) out of :count total', [
                                    'page' => $emails->currentPage(),
                                    'pages' => $emails->lastPage(),
                                    'current' => $emails->count(),
                                    'count' => $emails->total(),
                                ]) }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('emails.index') }}#tab-proofing" class="btn btn-secondary">Back to Jobs</a>
                </div>
            </div>
        </div>
    </div>

    @include('proofing.franchise.emails._modals')
@endsection

@section('js')
<script>
$(document).ready(function () {
    const spinningHtml = '<div class="text-center py-4"><i class="fa fa-spinner fa-3x fa-spin"></i></div>';
    const tsJobId = @json($tsJobIdEncrypted);
    let filterTimeout = null;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function runEmailFilter() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function () {
            $('#email-filter-response').html(spinningHtml);
            $('#email-paginator').hide();

            $.ajax({
                type: 'POST',
                url: '{{ route('emails.filter') }}',
                data: {
                    ts_job_id: tsJobId,
                    email_filter_value: $('#email-filter').val(),
                    email_filter_limit: $('#email-filter-limit').val(),
                    email_filter_order_by: $('#email-filter-order-by').val(),
                    email_filter_order_direction: $('#email-filter-order-direction').val()
                },
                success: function (response) {
                    $('#email-filter-response').html(response);
                },
                error: function () {
                    $('#email-filter-response').html('<div class="alert alert-danger">Unable to load emails.</div>');
                }
            });
        }, 400);
    }

    $('#email-filter').on('keyup', runEmailFilter);
    $('#email-filter-limit, #email-filter-order-by, #email-filter-order-direction').on('change', runEmailFilter);
});
</script>

@include('proofing.franchise.emails._view_resend_scripts')
@endsection
