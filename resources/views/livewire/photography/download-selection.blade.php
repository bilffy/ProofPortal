<div class="absolute right-2 h-full flex align-middle justify-center items-center gap-4">
    @if (count($selectedImages) > 0)
        <x-button.primary hollow class="border-none" @click="$dispatch('{{$PhotographyHelper::EV_CLEAR_SELECTED_IMAGES}}')">Clear Selection</x-button.primary>
    @endif
    <x-button.primary wire:click="downloadImages">Download {{count($selectedImages) > 0 ? 'Selected' : 'All'}}</x-button.primary>
</div>
