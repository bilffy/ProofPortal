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

                    <div class="paginator mt-3 d-none" id="email-paginator">
                        {{ $emails->links('proofing.layouts.pagination-custom') }}
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('emails.index') }}" class="btn btn-secondary">Back to Jobs</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewMessageModal" tabindex="-1" role="dialog" aria-labelledby="viewMessageModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMessageModalLabel">View Email Details</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="modal-body-view-message" class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resendMessageModal" tabindex="-1" role="dialog" aria-labelledby="resendMessageModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resendMessageModalLabel">Resend Email</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="modal-body-resend-message" class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
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

    function blurModalFocus(modalEl) {
        const active = document.activeElement;
        if (active && modalEl.contains(active) && typeof active.blur === 'function') {
            active.blur();
        }
    }

    function openBootstrapModal(selector) {
        const el = document.querySelector(selector);
        if (!el) {
            return;
        }

        blurModalFocus(el);
        el.classList.add('show');
        el.style.display = 'block';
        el.removeAttribute('aria-hidden');
        el.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');

        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function closeBootstrapModal(selectorOrEl) {
        if (typeof selectorOrEl === 'string' && selectorOrEl.includes(',')) {
            document.querySelectorAll(selectorOrEl).forEach(function (node) {
                closeBootstrapModal(node);
            });
            return;
        }

        const el = typeof selectorOrEl === 'string'
            ? document.querySelector(selectorOrEl)
            : selectorOrEl;
        if (!el || !el.classList.contains('show')) {
            return;
        }

        blurModalFocus(el);
        el.classList.remove('show');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        el.removeAttribute('aria-modal');

        if (!document.querySelector('.modal.show')) {
            document.body.classList.remove('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                backdrop.remove();
            });
        }
    }

    $(document).on('click', '#viewMessageModal [data-dismiss="modal"], #viewMessageModal [data-bs-dismiss="modal"], #resendMessageModal [data-dismiss="modal"], #resendMessageModal [data-bs-dismiss="modal"]', function (e) {
        e.preventDefault();
        closeBootstrapModal(this.closest('.modal'));
    });

    $(document).on('click', '.modal-backdrop', function () {
        closeBootstrapModal('#viewMessageModal.show, #resendMessageModal.show');
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeBootstrapModal('#viewMessageModal.show, #resendMessageModal.show');
        }
    });

    $(document).on('click', '.js-view-email', function (e) {
        e.preventDefault();
        const messageId = $(this).data('message-id');
        const $modal = $('#viewMessageModal');
        const $body = $('#modal-body-view-message');

        $modal.data('message-id', messageId);
        $body.html(spinningHtml);
        openBootstrapModal('#viewMessageModal');

        $.ajax({
            type: 'POST',
            url: '{{ route('emails.view') }}',
            data: { view_message_id: messageId },
            dataType: 'json',
            success: function (data) {
                const esc = function (value) {
                    return $('<div>').text(value == null ? '' : String(value)).html();
                };

                let metaHtml = ''
                    + '<div class="mb-3">'
                    + '<p class="mb-1"><strong>To:</strong> ' + esc(data.to) + '</p>'
                    + '<p class="mb-1"><strong>From:</strong> ' + esc(data.from) + '</p>';

                if (data.cc) {
                    metaHtml += '<p class="mb-1"><strong>CC:</strong> ' + esc(data.cc) + '</p>';
                }

                metaHtml += ''
                    + '<p class="mb-1"><strong>Template:</strong> ' + esc(data.template) + '</p>'
                    + '<p class="mb-1"><strong>Status:</strong> ' + esc(data.status) + '</p>'
                    + '<p class="mb-1"><strong>Created:</strong> ' + esc(data.created) + '</p>'
                    + '<p class="mb-3"><strong>Sent:</strong> ' + esc(data.sent) + '</p>'
                    + '</div>'
                    + '<div class="border rounded bg-white" style="max-height: 60vh; overflow: auto;">'
                    + '<iframe id="email-preview-frame" title="Email body" sandbox="allow-popups allow-popups-to-escape-sandbox" '
                    + 'style="width: 100%; min-height: 420px; border: 0;"></iframe>'
                    + '</div>';

                $body.html(metaHtml);
                const frame = document.getElementById('email-preview-frame');
                if (frame) {
                    frame.srcdoc = data.htmlBody || '<p>No email content.</p>';
                }
            },
            error: function (xhr) {
                const message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unable to load email.';
                $body.html('<div class="alert alert-danger">' + $('<div>').text(message).html() + '</div>');
            }
        });
    });

    $(document).on('click', '.js-resend-email', function (e) {
        e.preventDefault();
        const messageId = $(this).data('message-id');
        const $body = $('#modal-body-resend-message');

        $body.html(spinningHtml);
        openBootstrapModal('#resendMessageModal');

        $.ajax({
            type: 'POST',
            url: '{{ route('emails.resend') }}',
            data: { resend_message_id: messageId },
            success: function (response) { $body.html(response); },
            error: function () {
                $body.html('<div class="alert alert-danger">Unable to resend email.</div>');
            }
        });
    });
});
</script>
@endsection
