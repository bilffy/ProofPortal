@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
            <div class="inline-flex -space-x-px text-sm">
                <span>
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white cursor-default rounded-l-md leading-5 dark:bg-gray-800" aria-hidden="true">
                                <x-icon class="px-2" icon="angle-left" /> Prev
                            </span>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white rounded-l-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:active:bg-gray-700" aria-label="{{ __('pagination.previous') }}">
                            <x-icon class="px-2" icon="angle-left" /> Prev
                        </button>
                    @endif
                </span>

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white cursor-default leading-5 dark:bg-gray-800 dark:bg-gray-800">{{ $element }}</span>
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="flex items-center justify-center px-3 h-9 text-blue-600 bg-[#e9e6e3] hover:bg-blue-100 hover:text-blue-700">{{ $page }}</span>
                                    </span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800  dark:text-gray-400 dark:hover:text-gray-300 dark:active:bg-gray-700" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                <span>
                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:border-blue-300 focus:ring ring-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:active:bg-gray-700" aria-label="{{ __('pagination.next') }}">
                            Next <x-icon class="px-2" icon="angle-right" />
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white cursor-default rounded-r-md leading-5 dark:bg-gray-800" aria-hidden="true">
                                Next <x-icon class="px-2" icon="angle-right" />
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </nav>
    @endif
</div>
