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

    @if (method_exists($messages, 'hasPages'))
        <div class="paginator mt-3">
            <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-start">
                <div class="flex gap-4">
                    @if ($messages->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                            &lt; {{ __('Previous') }}
                        </span>
                    @else
                        <a href="#" data-page="{{ $messages->currentPage() - 1 }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300">
                            &lt; {{ __('Previous') }}
                        </a>
                    @endif

                    @if ($messages->hasMorePages())
                        <a href="#" data-page="{{ $messages->currentPage() + 1 }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-400 dark:active:bg-gray-700 dark:focus:border-blue-800">
                            {{ __('Next') }} &gt;
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md dark:text-gray-600 dark:bg-gray-800 dark:border-gray-600">
                            {{ __('Next') }} &gt;
                        </span>
                    @endif
                </div>
            </nav>

            <p class="mt-2 text-muted">
                {{ __('Page :page of :pages, showing :current record(s) out of :count total', [
                    'page' => $messages->currentPage(),
                    'pages' => $messages->lastPage(),
                    'current' => $messages->count(),
                    'count' => $messages->total(),
                ]) }}
            </p>
        </div>
    @endif
@else
    <div class="text-center py-4">
        <i class="fa fa-envelope-o fa-3x text-muted"></i>
        <p class="mt-3 mb-0">{{ __('No emails found.') }}</p>
    </div>
@endif
