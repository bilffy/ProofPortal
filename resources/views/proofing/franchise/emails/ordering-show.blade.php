@extends('proofing.layouts.master')

@section('title', 'Emails')

@section('css')
<style>
    @include('proofing.franchise.emails._results_styles')
</style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header emails-filter-head">
                    <h5 class="emails-head-title">Promotional Materials - {{ $school->name }}</h5>
                    <div class="emails-search-box">
                        <span class="emails-search-icon"><i class="fa fa-search"></i></span>
                        <input type="search" class="form-control emails-search-input" id="email-filter" aria-label="Keyword search" placeholder="Search..." autocomplete="off">
                        <button type="button" class="emails-search-clear" aria-label="Clear search"
                            onclick="var i = document.getElementById('email-filter'); i.value = ''; $(i).trigger('keyup'); i.focus();">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="email-filter-response">
                        @include('proofing.franchise.emails._results', ['messages' => $emails, 'showOrder' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="emails-back-row">
        <a href="{{ route('emails.index') }}#tab-ordering" class="emails-back-btn">Back</a>
    </div>

    @include('proofing.franchise.emails._modals')
@endsection

@section('js')
<script>
$(document).ready(function () {
    const spinningHtml = '<div class="text-center py-4"><i class="fa fa-spinner fa-3x fa-spin"></i></div>';
    const schoolId = @json($schoolIdEncrypted);
    let filterTimeout = null;
    // Column sorting: clicking a header in the table (see _results.blade.php) sets these.
    let sortBy = 'created_at';
    let sortDirection = 'descending';

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function runEmailFilter(page) {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function () {
            $('#email-filter-response').html(spinningHtml);

            $.ajax({
                type: 'POST',
                url: '{{ route('emails.ordering.filter') }}',
                data: {
                    school_id: schoolId,
                    page: page || 1,
                    email_filter_value: $('#email-filter').val(),
                    email_filter_order_by: sortBy,
                    email_filter_order_direction: sortDirection
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

    $('#email-filter').on('keyup', function () { runEmailFilter(1); });

    // Sortable column headers are rendered inside the AJAX response: same column flips the
    // direction, a new column starts ascending.
    $('#email-filter-response').on('click', 'th[data-sort]', function () {
        const column = $(this).data('sort');
        if (sortBy === column) {
            sortDirection = sortDirection === 'ascending' ? 'descending' : 'ascending';
        } else {
            sortBy = column;
            sortDirection = 'ascending';
        }
        runEmailFilter(1);
    });

    // Pagination Previous/Next links are rendered inside the AJAX response
    // (see _results.blade.php) - intercept clicks there instead of navigating.
    $('#email-filter-response').on('click', '[data-page]', function (e) {
        e.preventDefault();
        runEmailFilter(parseInt($(this).data('page'), 10));
    });
});
</script>

@include('proofing.franchise.emails._view_resend_scripts')
@endsection
