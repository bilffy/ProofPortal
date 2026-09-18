{{-- Shared View/Resend modal wiring for the emails pages (index + show).
     Self-contained: safe to @include inside any @section('js') block. --}}
<script>
$(document).ready(function () {
    const spinningHtml = '<div class="text-center py-4"><i class="fa fa-spinner fa-3x fa-spin"></i></div>';

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

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
