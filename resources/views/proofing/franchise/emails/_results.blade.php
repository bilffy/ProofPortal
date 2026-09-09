@if ($messages->count())
    <div class="table-responsive">
        <table id="emails-messages-table" class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col" style="width: 70px; max-width: 70px; white-space: nowrap;">{{ __('ID') }}</th>
                    <th scope="col">{{ __('Created') }}</th>
                    <th scope="col">{{ __('Sent') }}</th>
                    <th scope="col">{{ __('To') }}</th>
                    <th scope="col">{{ __('From') }}</th>
                    <th scope="col">{{ __('Template') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($messages as $message)
                    @php
                        $rowNumber = method_exists($messages, 'firstItem') && $messages->firstItem()
                            ? $messages->firstItem() + $loop->index
                            : $loop->iteration;
                    @endphp
                    <tr>
                        <td>{{ $rowNumber }}</td>
                        <td>{{ $message->created_at ? $message->created_at->format('Y-m-d H:i') : '' }}</td>
                        <td>{{ $message->sentdate ? \Carbon\Carbon::parse($message->sentdate)->format('Y-m-d H:i') : '—' }}</td>
                        <td>{{ $message->email_to }}</td>
                        <td>{{ $message->email_from }}</td>
                        <td>{{ $message->template->external_template_name ?? '—' }}</td>
                        <td>{{ $message->status->status_external_name ?? $message->status->status_internal_name ?? '—' }}</td>
                        <td>
                            <a
                                href="#"
                                class="btn btn-link p-0 js-view-email"
                                data-message-id="{{ $message->id }}"
                            >
                                {{ __('View') }}
                            </a>
                            <span class="text-muted mx-1">|</span>
                            <a
                                href="#"
                                class="btn btn-link p-0 js-resend-email"
                                data-message-id="{{ $message->id }}"
                            >
                                {{ __('Resend') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <i class="fa fa-envelope-o fa-3x text-muted"></i>
        <p class="mt-3 mb-0">{{ __('No emails found.') }}</p>
    </div>
@endif
