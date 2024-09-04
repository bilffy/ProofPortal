@php
    $activeClass = 'text-blue-600 bg-[#e9e6e3] hover:bg-blue-100 hover:text-blue-700';
    $inactiveClass = 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700';
    $previousClass = 'ms-0 rounded-s-lg';
    $nextClass = 'rounded-e-lg';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <ul class="inline-flex -space-x-px text-sm" >
            {{-- Previous Page Link --}}
            <a href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}" rel="prev" class="flex items-center justify-center px-3 h-8 ms-0 rounded-s-lg {{ $paginator->onFirstPage() ? 'opacity-50' : '' }}" aria-label="{{ __('pagination.previous') }}">
                <x-icon class="px-2" icon="angle-left" /> Prev
            </a>
            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <div class="flex items-center justify-center px-3 h-8">
                        {{ $element }}
                    </div>
                @endif
                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="flex items-center justify-center px-3 h-8 {{ $activeClass }}">{{ $page }}</span>
                            </span>
                        @else
                            <a href="{{ $url }}" class="flex items-center justify-center px-3 h-8 {{ $inactiveClass }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            {{-- Next Page Link --}}
            <a href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#'}}" rel="next" class="flex items-center justify-center px-3 h-8 rounded-e-lg {{ $paginator->hasMorePages() ? '' : 'opacity-50' }}" aria-label="{{ __('pagination.next') }}">
                Next <x-icon class="px-2" icon="angle-right" />
            </a>
        </ul>
    </nav>
@endif
