
{{-- code by IT --}}
{{-- Per-page image count is sized BEFORE this view first renders, by the
     query-free photo-grid-measuring view (see PhotoGrid::render()). The
     x-init below only handles LIVE browser resizes after that: it re-measures
     the already-rendered grid's resolved column count (debounced) and asks
     the server to reload with the new preferred count when it changes. --}}
<div class="w-full text-center"
    x-data="{}"
    x-init="
        let photoGridResizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(photoGridResizeTimer);
            photoGridResizeTimer = setTimeout(() => {
                const gridEl = $el.querySelector('[total-image-count]');
                if (!gridEl) { return; }
                const resolved = window.getComputedStyle(gridEl).gridTemplateColumns.trim();
                const tracks = resolved.length ? resolved.split(' ').filter(Boolean) : [];
                const allPx = tracks.length > 0 && tracks.every((t) => t.indexOf('px') !== -1);
                if (allPx) {
                    $wire.resizeColumns(tracks.length);
                }
            }, 400);
        });
    "
>

    <div class="w-full text-center">
        <div class="grid grid-cols-[repeat(auto-fit,195px)] gap-auto" total-image-count="{{ $totalWithImages }}">
            @foreach ($paginatedImages as $image)
                @php
                    $isFolder = $image['category'] == 'FOLDER';
                    $imageId = $image['id'];
                    $name = $isFolder ? $image['classGroup'] : $image['firstname'] . ' ' . $image['lastname'];
                    $folderName = $isFolder ? '' : $image['classGroup'];
                    $landscape = !$image['isPortrait'];
                    $key = "img_{{$imageId}}";
                    $isUploaded = $image['isUploaded'] ?? false;
                    $externalSubjectId = !$isFolder ? $image['externalSubjectId'] : null;
                    $hasImage = $image['hasPhoto'] ?? false; // Added this line
                @endphp
                {{-- <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isUploaded :$externalSubjectId :key="$key" /> --}} {{-- code by chromedia--}}
                {{-- code by IT--}}
                <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isUploaded :$externalSubjectId :$category :$hasImage wire:key="grid-{{ $imageId }}" />
                {{-- code by IT--}}
            @endforeach
        </div>
        <div class="mt-4 mb-4 flex flex-col items-center gap-2">
            <div>
                @if (count($paginatedImages) == 0)
                    @if ($this->search)
                        {{ $this->category == \App\Helpers\PhotographyHelper::TAB_PORTRAITS ? 'No Subject Found.' : '' }}
                    @else
                        Your MSP photos are currently being processed and will appear here shortly.
                    @endif
                @else
                    {{-- <p class="text-sm text-neutral-600 mb-3">
                        Showing {{ $paginatedImages->firstItem() }}&ndash;{{ $paginatedImages->lastItem() }}
                        of {{ number_format($paginatedImages->total()) }}
                        @if ($paginatedImages->hasMorePages())
                            <span class="text-neutral-500">— more available below</span>
                        @endif
                    </p> --}}
                    {{ $paginatedImages->onEachSide(1)->links('vendor.livewire.pagination') }} {{-- code by IT --}}
                    {{-- {{ $paginatedImages->links('vendor.livewire.pagination') }} --}} {{-- code by chromedia --}}
                @endif
            </div>
        </div>
    </div>
</div>
{{-- code by IT --}}

{{-- code by chromedia --}}
{{--
<div class="w-full text-center">
    <div class="mt-4" wire:loading>
        <x-spinner.icon :size="10"/>
    </div>
    <div wire:loading.remove>
        <div class="grid grid-cols-[repeat(auto-fit,195px)] gap-auto" total-image-count="{{ $paginatedImages->total() }}">
            @foreach ($paginatedImages as $image)
                @php
                    $isFolder = $image['category'] == 'FOLDER';
                    $imageId = $image['id'];
                    $name = $isFolder ? $image['classGroup'] : $image['firstname'] . ' ' . $image['lastname'];
                    $folderName = $isFolder ? '' : $image['classGroup'];
                    $landscape = !$image['isPortrait'];
                    $key = "img_{{$imageId}}";
                    $isUploaded = $image['isUploaded'] ?? false;
                    $externalSubjectId = !$isFolder ? $image['externalSubjectId'] : null;
                @endphp
                <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isUploaded :$externalSubjectId :key="$key" lazy="on-load"/>
            @endforeach
        </div>
        <div class="mt-4 mb-4 flex justify-center">
            <div>
                @if (count($paginatedImages) == 0)
                    Your MSP photos are currently being processed and will appear here shortly.
                @else
                    {{ $paginatedImages->onEachSide(1)->links('vendor.livewire.pagination') }}
                @endif
            </div>
        </div>
    </div>
</div>
--}}
{{-- code by chromedia --}}