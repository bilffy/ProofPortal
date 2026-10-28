<div class="relative overflow-x-auto">
    <div class="container3 p-4">
        {{--
            Previous implementation: embedded the Zoho Forms "ID Card Ordering
            via Portal" form directly. Kept here, commented out, per request
            rather than deleted outright. The matching component logic in
            app/Http/Livewire/Order/Order.php is commented out the same way.

            <iframe aria-label='MSP Portal Orders' frameborder="0" style="width: 100%; height: 80vh; border: none;"
                    src="https://forms.zohopublic.com.au/msp/form/IDCardOrderingviaPortal/formperma/znjNcllPz1C3h9cEX-B_jvCfwx-8z-7Vk94-K7_y-Ik?key={{ $schoolKey ?? '' }}&first={{ $user->firstname ?? '' }}&last={{ $user->lastname ?? '' }}&email={{ $user->email ?? '' }}">
            </iframe>
        --}}

        {{-- Same heading wrapper as /users (Manage Users): py-4 gives the h3 the
             same gap from the top of the page, and the same gap below it that
             mb-4 used to give. --}}

        <div class="py-4 flex" style="gap: 16px; align-items: center;">
            <h3 class="text-2xl" style="margin: 0; line-height: 1.2;">{{ $selectedProductId ? (collect($products)->firstWhere('id', $selectedProductId)?->name ?? 'Ordering') : 'Ordering' }}</h3>
            @if ($selectedProductId && collect($products)->firstWhere('id', $selectedProductId)?->name === 'Other')
                <p class="font-medium" style="color: #dc2626; margin: 0 !important; font-weight:700 !important;">* Contact MSP for more details.</p>
            @endif
        </div>

        @if ($successMessage)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-success-300 bg-success-100 px-4 py-3 text-sm text-success mb-4" role="status">
                <span>{{ $successMessage }}</span>
                <button type="button" wire:click="$set('successMessage', '')" class="text-success hover:opacity-70 text-xl leading-none" aria-label="Close">&times;</button>
            </div>
        @endif

        <div class="w-full">
            @if (!$selectedProductId)
                <div class="mb-4">
                    <!-- <label class="block text-sm font-medium text-gray-700 mb-3">Select a product</label> -->

                    @if (count($products))
                        @php
                            // Pill colours, following the rainbow ribbon from the brand artwork (left to right).
                            $tileColours = ['redorange', 'orange', 'amber', 'yellow', 'lime', 'teal', 'cyan', 'blue', 'deepblue', 'navy', 'red'];
                        @endphp
                        <div class="product-grid">
                            @foreach ($products as $product)
                                @php
                                    // Everything but the last word is bold, the last word is light
                                    // ("A4 PROSPECTUS" + "BOOKLETS"); a single word is all bold.
                                    $nameWords = preg_split('/\s+/', trim($product->name));
                                    $nameLight = count($nameWords) > 1 ? array_pop($nameWords) : '';
                                    $nameBold = implode(' ', $nameWords);
                                @endphp
                                <button type="button" wire:click="selectProduct({{ $product->id }})" class="product-tile tile-{{ $tileColours[$loop->index % count($tileColours)] }} {{ $loop->even ? 'tile-right' : 'tile-left' }}">
                                    <span class="product-tile-thumb">
                                        @if ($product->image)
                                            <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($product->image) }}" class="product-tile-image" loading="lazy" alt="{{ $product->name }}" />
                                        @else
                                            <i class="fa fa-image product-tile-placeholder"></i>
                                        @endif
                                    </span>
                                    <span class="product-tile-body">
                                        <span class="product-tile-title">
                                            <strong>{{ $nameBold }}</strong>
                                            @if ($nameLight !== '')<span>{{ $nameLight }}</span>@endif
                                        </span>
                                        <span class="product-tile-button">Order Now</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No products are available to order yet.</p> 
                    @endif

                    @error('selectedProductId') <p class="mt-2 text-sm text-alert">{{ $message }}</p> @enderror
                </div>
            @else
                {{-- @php($selectedProduct = collect($products)->firstWhere('id', $selectedProductId))

                <div class="mb-4 flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                    <div class="flex items-center gap-3">
                        @if ($selectedProduct && $selectedProduct->image)
                            <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($selectedProduct->image) }}" class="h-16 w-16 object-cover rounded border border-neutral" alt="{{ $selectedProduct->name }}" />
                        @endif
                        <span class="text-sm font-medium text-gray-700">{{ $selectedProduct->name ?? '' }}</span>
                    </div>
                    <button type="button" wire:click="changeProduct" class="text-sm text-primary hover:underline">&larr; Change product</button>
                </div> --}}

                @if (count($customFields))
                    @php($availableFieldIds = collect($customFields)->pluck('id')->all())
                    <div class="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
                        <h5 class="text-lg font-semibold mb-4">Print Specifications</h5>
                        <hr class="border-gray-400 mb-6">
                        <div class="grid grid-cols-1 gap-4 custom-fields-grid">
                        @foreach ($customFields as $field)
                            @continue(!$field->isVisibleGiven($fieldValues, $availableFieldIds))
                            <div wire:key="field-{{ $selectedProductId }}-{{ $field->id }}" @if ($field->type === \App\Models\CustomField::TYPE_IMAGE) style="grid-column: 1 / -1;" @endif>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $field->label }}
                                    @if ($field->is_required)
                                        <span class="text-alert">*</span>
                                    @endif
                                </label>

                                @if ($field->type === \App\Models\CustomField::TYPE_RADIO)
                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($field->fieldOptions as $option)
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="radio" wire:model.live="fieldValues.{{ $field->id }}" value="{{ $option->option_value }}" class="h-4 w-4 border-neutral text-primary focus:ring-primary" />
                                                {{ $option->option_name }}
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($field->type === \App\Models\CustomField::TYPE_DROPDOWN)
                                    <div wire:ignore>
                                        <select
                                            wire:model.live="fieldValues.{{ $field->id }}"
                                            class="bg-gray-50 border border-neutral text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-primary block w-full p-2.5"
                                            data-field-select
                                            data-placeholder="{{ $field->placeholder ?: 'Select an option' }}"
                                        >
                                            <option value="">{{ $field->placeholder ?: 'Select an option' }}</option>
                                            @foreach ($field->fieldOptions as $option)
                                                <option value="{{ $option->option_value }}">{{ $option->option_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif ($field->type === \App\Models\CustomField::TYPE_NUMBER)
                                    <input type="number" min="1" wire:model="fieldValues.{{ $field->id }}" placeholder="{{ $field->placeholder }}" class="bg-gray-50 border border-neutral text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-primary block w-full p-2.5" />
                                @elseif ($field->type === \App\Models\CustomField::TYPE_CHECKBOX)
                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($field->fieldOptions as $option)
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model.live="fieldValues.{{ $field->id }}" value="{{ $option->option_value }}" class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary" />
                                                {{ $option->option_name }}
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($field->type === \App\Models\CustomField::TYPE_IMAGE)
                                    @if ($field->image_url)
                                        <img src="{{ $field->image_url }}" class="rounded border border-neutral" style="display: block;" alt="{{ $field->label }}" />
                                    @else
                                        <p class="text-sm text-gray-500">No image provided for this field.</p>
                                    @endif
                                @else
                                    <input type="text" wire:model="fieldValues.{{ $field->id }}" placeholder="{{ $field->placeholder }}" class="bg-gray-50 border border-neutral text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-primary block w-full p-2.5" />
                                @endif

                                @if ($field->type === \App\Models\CustomField::TYPE_RADIO && $field->otherOptionValue() && ($fieldValues[$field->id] ?? null) === $field->otherOptionValue())
                                    <input type="text" wire:model="fieldOtherValues.{{ $field->id }}" placeholder="Please specify" style="margin-top: 1.75rem;" class="bg-gray-50 border border-neutral text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-primary block w-full p-2.5" />
                                    @error('fieldOtherValues.' . $field->id) <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                                @endif

                                @if ($field->note)
                                    <p class="mt-1 text-xs text-gray-500">{{ $field->note }}</p>
                                @endif
                                @error('fieldValues.' . $field->id) <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
                        <p class="text-sm text-gray-500">This product has no additional details to fill in.</p>
                    </div>
                @endif

                {{-- Artwork section - the same on every product's order form --}}
                <div class="artwork-section mt-6 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
                    <h5 class="text-lg font-semibold mb-4">Artwork</h5>
                    <hr class="border-gray-400 mb-6">
                    <div class="grid grid-cols-1 gap-4 custom-fields-grid">
                        <div class="artwork-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Artwork Required? <span class="text-alert">*</span>
                            </label>
                            <div class="artwork-radio-list">
                                @foreach ($artworkRequiredOptions as $option)
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="radio" wire:model="artworkRequiredId" value="{{ $option->id }}" class="h-4 w-4 border-neutral text-primary focus:ring-primary" />
                                        {{ $option->details }}
                                    </label>
                                @endforeach
                            </div>
                            @error('artworkRequiredId') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                        </div>

                        <div class="artwork-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Due Date <span class="text-alert">*</span>
                            </label>
                            <div wire:ignore>
                                <div class="relative">
                                    <input
                                        type="text"
                                        data-artwork-due-date
                                        placeholder="DD/MM/YYYY"
                                        autocomplete="off"
                                        value="{{ $artworkDueDate }}"
                                        class="bg-gray-50 border border-neutral text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-primary block w-full pr-10 p-2.5"
                                    />
                                    <div class="absolute inset-y-0 right-[16px] flex items-center pointer-events-none">
                                        <span class="fa fa-calendar"></span>
                                    </div>
                                </div>
                            </div>
                            @error('artworkDueDate') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-500">Please give us your printing deadline. This will ensure that the team will work with you to ensure the correct (or substitue) product/s that meet your requirements.</p>
                        </div>

                        <div class="artwork-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Comments</label>
                            <textarea wire:model="additionalComments" rows="4" class="artwork-wide bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm"></textarea>
                            @error('additionalComments') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                        </div>

                        <div class="artwork-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Artwork Brief <span class="text-alert">*</span>
                            </label>
                            <div wire:ignore class="artwork-wide">
                                <textarea data-artwork-brief>{!! $artworkBrief !!}</textarea>
                            </div>
                            @error('artworkBrief') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                            <p class="mt-1 mb-0 text-xs text-gray-500">*Please give us a brief overview of you and your clients&rsquo; expectations in regard to the artwork requirements.</p>
                            <p class="m-0 text-xs text-gray-500">The more information we receive from you, the less back and forth we will have, which in turn leads to a decrease in artwork costs to you.</p>
                            <p class="m-0 text-xs text-gray-500">Feel free to supply us with examples, images and any relevant information that you feel will assist in bringing your ideas to life.</p>
                        </div>

                        <div class="artwork-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload</label>

                            <label for="artwork-upload-input" class="artwork-dropzone" data-artwork-dropzone>
                                <span class="text-sm text-gray-600 mt-1">Drag &amp; drop files here, or click to browse</span>
                                <span class="text-xs text-gray-400 mt-1">Images, PDF, or Word documents up to 15MB per file</span>
                                <input
                                    type="file"
                                    id="artwork-upload-input"
                                    wire:model="newArtworkFiles"
                                    multiple
                                    accept="image/*,.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    class="hidden"
                                />
                            </label>

                            <div wire:loading wire:target="newArtworkFiles" class="text-xs text-gray-500 mt-2">Uploading&hellip;</div>

                            @error('artworkFiles') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror

                            @if (count($artworkFiles))
                                {{-- Images get a thumbnail preview; other types (PDF, Word) stay
                                     name chips. $i indexes $artworkFiles so removal hits the right
                                     file either way. Keep the line below in the one-line php form
                                     used earlier in this file - do not turn it into a multi-line
                                     php block, or Blade pairs it with the first one-line use. --}}
                                @php($isImage = fn ($file) => str_starts_with((string) $file->getMimeType(), 'image/') && $file->isPreviewable())

                                <div class="artwork-preview-grid mt-3">
                                    @foreach ($artworkFiles as $i => $file)
                                        @if ($isImage($file))
                                            <div class="artwork-preview" wire:key="artwork-preview-{{ $i }}-{{ $file->getFilename() }}">
                                                <img src="{{ $file->temporaryUrl() }}" alt="{{ $file->getClientOriginalName() }}" class="artwork-preview-img" />
                                                <button type="button" wire:click="removeArtworkFile({{ $i }})" class="artwork-preview-remove" aria-label="Remove file">&times;</button>
                                                <span class="artwork-preview-name" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <ul class="artwork-file-list mt-3">
                                    @foreach ($artworkFiles as $i => $file)
                                        @unless ($isImage($file))
                                            <li class="artwork-file-chip">
                                                <span class="truncate max-w-xs">{{ $file->getClientOriginalName() }}</span>
                                                <button type="button" wire:click="removeArtworkFile({{ $i }})" class="text-gray-400 hover:text-alert" aria-label="Remove file">&times;</button>
                                            </li>
                                        @endunless
                                        @error('artworkFiles.' . $i) <p class="mt-1 text-xs text-alert">{{ $message }}</p> @enderror
                                    @endforeach
                                </ul>
                            @endif

                            <p class="mt-1 text-xs text-gray-500">Upload images and documentation to create your artwork.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <x-button.secondary wire:click="cancel" wire:confirm="Cancel this order? Any details you've entered will be lost.">Cancel</x-button.secondary>
                    <x-button.primary wire:click="submit">Submit</x-button.primary>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<link href="{{ URL::asset('proofing-assets/vendors/css/flatpickr.min.css') }}" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet" />
<style>
    .artwork-radio-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .artwork-dropzone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1.25rem;
        border: 2px dashed #cbd5e1;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    /* 

    */
    /* .artwork-dropzone:hover,
    .artwork-dropzone.is-dragover {
        border-color: #b5d334;
        background-color: #f7faec;
    } */
    .artwork-file-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .artwork-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.75rem;
    }
    .artwork-preview-grid:empty {
        display: none;
    }
    .artwork-preview {
        position: relative;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        background-color: #f9fafb;
        overflow: hidden;
    }
    .artwork-preview-img {
        display: block;
        width: 100%;
        height: 110px;
        object-fit: cover;
    }
    .artwork-preview-name {
        display: block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .artwork-preview-remove {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        width: 1.5rem;
        height: 1.5rem;
        line-height: 1.5rem;
        text-align: center;
        font-size: 1rem;
        color: #ffffff;
        background-color: rgba(17, 24, 39, 0.65);
        border: none;
        border-radius: 999px;
        cursor: pointer;
    }
    .artwork-preview-remove:hover {
        background-color: rgba(220, 38, 38, 0.9);
    }
    .artwork-file-chip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        color: #374151;
    }
    .artwork-file-chip button {
        font-size: 1rem;
        line-height: 1;
        border: none;
        background: none;
        cursor: pointer;
    }
    /* summernote-lite ships its own layout; just make sure it doesn't look
       cramped next to the rest of the form's rounded/bordered inputs. */
    .note-editor.note-frame {
        border-color: #d1d5db;
        border-radius: 0.375rem;
    }

    /* Custom fields (Print Specifications): one field per row, with the
       controls kept to a comfortable width rather than stretching full width. */
    .custom-fields-grid input[type="text"],
    .custom-fields-grid input[type="number"],
    .custom-fields-grid select,
    .custom-fields-grid .select2-container,
    .custom-fields-grid > div > div[wire\:ignore] {
        max-width: 502px;
    }
    .custom-fields-grid .select2-container {
        width: 100% !important;
    }
    /* Long-form controls (brief editor, comments, upload) get a bit more room. */
    .custom-fields-grid > div > div.artwork-wide,
    .custom-fields-grid textarea.artwork-wide,
    .custom-fields-grid .artwork-dropzone {
        max-width: 800px;
    }
    .custom-fields-grid {
        row-gap: 1.75rem;
    }
    /* Product selection tiles: coloured pills, image in a white circle on the
       outer end, title + "Order Now" on the inner end (mirrored for the right
       column). Images are shown whole (object-fit: contain), never cropped. */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 480px));
        justify-content: center;
        gap: 20px;
    }
    .product-tile {
        --tile-bg: #00b4e6;
        display: flex;
        align-items: center;
        box-sizing: border-box;
        width: 100%;
        height: 190px;
        padding: 0 16px;
        background-color: var(--tile-bg);
        border: 0;
        color: #ffffff;
        cursor: pointer;
        transition: transform 0.18s ease, filter 0.18s ease;
    }
    .product-tile.tile-left {
        flex-direction: row;
        border-radius: 95px 0 0 95px;
        text-align: right;
    }
    .product-tile.tile-right {
        flex-direction: row-reverse;
        border-radius: 0 95px 95px 0;
        text-align: left;
    }
    .product-tile:hover {
        transform: translateY(-3px);
        filter: brightness(1.05);
    }
    .product-tile:active {
        transform: scale(0.99);
    }
    .product-tile:focus-visible {
        outline: 2px solid #005890;
        outline-offset: 2px;
    }
    .tile-redorange { --tile-bg: #ee4b27; }
    .tile-orange    { --tile-bg: #f6921e; }
    .tile-amber     { --tile-bg: #fbb61a; }
    .tile-yellow    { --tile-bg: #f2d600; }
    .tile-lime      { --tile-bg: #b5d334; }
    .tile-teal      { --tile-bg: #4cc0a6; }
    .tile-cyan      { --tile-bg: #00b4e6; }
    .tile-blue      { --tile-bg: #0090d4; }
    .tile-deepblue  { --tile-bg: #0072b5; }
    .tile-navy      { --tile-bg: #005890; }
    .tile-red       { --tile-bg: #e2231a; }
    .product-tile-thumb {
        position: relative;
        flex: 0 0 auto;
        width: 130px;
        height: 130px;
        overflow: hidden;
        border-radius: 50%;
        background: #ffffff;
    }
    .product-tile-image {
        position: absolute;
        inset: 8px;
        width: calc(100% - 16px);
        height: calc(100% - 16px);
        object-fit: contain;
        object-position: center;
        transition: transform 0.3s ease;
    }
    .product-tile:hover .product-tile-image {
        transform: scale(1.05);
    }
    .product-tile-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 3rem;
        color: #cbd5e1;
    }
    .product-tile-body {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 0 12px;
    }
    .product-tile-title {
        display: block;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.7);
        font-size: 18px;
        line-height: 1.15;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-tile-title strong,
    .product-tile-title span {
        font-weight: 700;
    }
    .product-tile-button {
        display: inline-block;
        padding: 4px 20px;
        border: 2px solid #ffffff;
        border-radius: 999px;
        background: #6d6e71;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.3;
    }
    .tile-left .product-tile-button { align-self: flex-end; }
    .tile-right .product-tile-button { align-self: flex-start; }
    @media (max-width: 900px) {
        .product-grid { grid-template-columns: minmax(0, 480px); }

    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
    window.addEventListener('order-scroll-top', () => {
        setTimeout(() => {
            window.scrollTo(0, 0);
            const root = document.scrollingElement || document.documentElement;
            root.scrollTop = 0;
            document.querySelectorAll('main, #content, .content, .overflow-y-auto').forEach(el => { el.scrollTop = 0; });
        }, 0);
    });
</script>
<script>
(function () {
    function findComponent(el) {
        var root = el.closest('[wire\\:id]');
        var componentId = root ? root.getAttribute('wire:id') : null;
        return componentId && window.Livewire ? window.Livewire.find(componentId) : null;
    }

    function initDueDatePickers() {
        if (typeof window.flatpickr !== 'function') {
            console.warn('[Artwork] flatpickr is not loaded on window - the Due Date field will stay a plain text input.');
            return;
        }
        document.querySelectorAll('[data-artwork-due-date]:not([data-fp-bound])').forEach(function (input) {
            input.setAttribute('data-fp-bound', '1');
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            window.flatpickr(input, {
                dateFormat: 'd/m/Y',
                minDate: tomorrow,
                allowInput: false,
                onChange: function (selectedDates, dateStr) {
                    var component = findComponent(input);
                    if (component) {
                        component.set('artworkDueDate', dateStr);
                    }
                },
            });
        });
    }

    function initArtworkBriefEditors() {
        if (typeof window.jQuery === 'undefined') {
            console.warn('[Artwork] jQuery is not loaded on window yet - the Artwork Brief field will stay a plain textarea.');
            return;
        }
        if (!window.jQuery.fn || !window.jQuery.fn.summernote) {
            console.warn('[Artwork] summernote is not registered on jQuery.fn - the CDN script may have failed to load, or loaded before jQuery was ready. The Artwork Brief field will stay a plain textarea.');
            return;
        }
        var $ = window.jQuery;

        document.querySelectorAll('[data-artwork-brief]:not([data-sn-bound])').forEach(function (textarea) {
            textarea.setAttribute('data-sn-bound', '1');
            var $textarea = $(textarea);
            var debounceTimer = null;

            $textarea.summernote({
                height: 200,
                // No images in the brief: this hides the "drop image here"
                // overlay and stops dropped files being inserted.
                disableDragAndDrop: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'hr']],
                    ['view', ['fullscreen', 'codeview']],
                ],
                callbacks: {
                    // Replaces summernote's default of embedding image files as
                    // base64 - so a dropped OR pasted image file is ignored.
                    onImageUpload: function () {},
                    onChange: function (contents) {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(function () {
                            var component = findComponent(textarea);
                            if (component) {
                                component.set('artworkBrief', contents);
                            }
                        }, 400);
                    },
                },
            });

            // Belt and braces: swallow any file drag/drop over the editor before
            // summernote (or the browser, which would otherwise insert or open
            // the file) sees it. Text drags inside the editor are unaffected.
            var editorEl = textarea.nextElementSibling;
            if (editorEl && editorEl.classList.contains('note-editor')) {
                ['dragover', 'drop'].forEach(function (evt) {
                    editorEl.addEventListener(evt, function (e) {
                        var types = e.dataTransfer && e.dataTransfer.types;
                        if (types && Array.prototype.indexOf.call(types, 'Files') !== -1) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    }, true);
                });
            }
        });
    }

    function initDropzones() {
        document.querySelectorAll('[data-artwork-dropzone]:not([data-dz-bound])').forEach(function (zone) {
            zone.setAttribute('data-dz-bound', '1');
            var input = zone.querySelector('input[type="file"]');
            if (!input) {
                return;
            }

            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.remove('is-dragover');
                });
            });

            zone.addEventListener('drop', function (e) {
                var files = e.dataTransfer && e.dataTransfer.files;
                if (!files || !files.length) {
                    return;
                }
                input.files = files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function initFieldSelects() {
        if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
            console.warn('[Order] select2 is not loaded - custom-field dropdowns will stay native selects.');
            return;
        }
        var $ = window.jQuery;
        document.querySelectorAll('select[data-field-select]').forEach(function (el) {
            var $el = $(el);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    width: '100%',
                    placeholder: el.getAttribute('data-placeholder') || 'Select an option',
                    allowClear: false,
                    minimumResultsForSearch: 8
                });
                $el.next('.select2-container').find('.select2-selection').addClass('border-neutral');
                // select2 only fires jQuery events; Livewire's wire:model listens for the native one.
                $el.on('select2:select', function () {
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
            // Keep the select2 display in step with the value Livewire has set.
            $el.trigger('change.select2');
        });
    }

    function boot() {
        initFieldSelects();
        initDueDatePickers();
        initArtworkBriefEditors();
        initDropzones();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    function bootSoon() {
        // Deferred a tick so this always runs after Livewire has finished
        // applying the DOM patch it just hooked into, not mid-patch.
        setTimeout(boot, 0);
    }

    document.addEventListener('livewire:navigated', bootSoon);

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morph.updated', bootSoon);
    } else {
        document.addEventListener('livewire:initialized', function () {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('morph.updated', bootSoon);
            } else {
                console.warn('[Artwork] window.Livewire.hook is not available - the Due Date, Artwork Brief, and Upload widgets will only initialize on full page loads, not after selecting a product.');
            }
        });
    }
})();
</script>
@endpush
