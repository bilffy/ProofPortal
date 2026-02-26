<div class="w-full text-center" x-data="{
    calculateAndSet() {
        let gridEl = this.$el.querySelector('.grid');
        if (!gridEl) return;
        
        let computed = window.getComputedStyle(gridEl);
        let colsStr = computed.getPropertyValue('grid-template-columns');
        let cols = colsStr ? colsStr.trim().split(/\s+/).length : 1;
        if (cols < 1) cols = 1;
        
        let targetPerPage = cols * 3;
        
        if (targetPerPage < 30) {
            // Find how many rows it takes to hold at least 30 images
            // and multiply by the exact columns to ensure a completely flat bottom row
            targetPerPage = Math.ceil(30 / cols) * cols;
        }
        
        if (this.$wire.perPage !== targetPerPage) {
            this.$wire.setPerPage(targetPerPage);
        }
    }
}" x-init="
    $nextTick(() => {
        setTimeout(() => calculateAndSet(), 150);
    });
    window.addEventListener('resize', () => { setTimeout(() => calculateAndSet(), 300); });
">
    <!-- <div class="mt-4" wire:loading>
        <x-spinner.icon :size="10"/>
    </div>
    <div wire:loading.remove> -->
    <div>
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
                {{-- <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isUploaded :$externalSubjectId :key="$key" lazy="on-load"/> --}} {{-- code by chromedia--}}
                {{-- code by IT--}}
                <livewire:photography.image-frame :$imageId :$name :$landscape :$folderName :$isUploaded :$externalSubjectId wire:key="grid-{{ $imageId }}" lazy="on-load"/>
                {{-- code by IT--}}
            @endforeach
        </div>
        <div class="mt-4 mb-4 flex justify-center">
            <div>
                @if (count($paginatedImages) == 0)
                    No images found
                @else
                    {{ $paginatedImages->onEachSide(1)->links('vendor.livewire.pagination') }} {{-- code by IT --}}
                    {{-- {{ $paginatedImages->links('vendor.livewire.pagination') }} --}} {{-- code by chromedia --}}
                @endif
            </div>
        </div>
    </div>
</div>
