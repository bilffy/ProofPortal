<div>
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Products</h3>
        @unless ($showForm)
            <x-button.primary wire:click="createNew">Add Product</x-button.primary>
        @endunless
    </div>

    @if ($successMessage)
        <div class="rounded-xl border border-success-300 bg-success-100 px-4 py-3 text-sm text-success mb-4" role="status">
            {{ $successMessage }}
        </div>
    @endif

    @if ($showForm)
        <div class="relative mb-8 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
            <h5 class="text-lg font-semibold mb-4">{{ $isDraft ? 'New Product' : 'Edit Product' }}</h5>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" wire:model="name" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    @error('name') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                {{--

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-neutral text-xs">(auto-generated if left blank)</span></label>
                    <input type="text" wire:model="slug" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    @error('slug') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" wire:model="sku" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    @error('sku') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
                    <input type="number" step="0.01" min="0" wire:model="basePrice" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    @error('basePrice') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>
                
                --}}

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="3" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                    <input type="number" step="1" min="0" wire:model="sortOrder" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    <p class="mt-1 text-xs text-neutral">Lower numbers show first on the /order page.</p>
                    @error('sortOrder') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    @if ($existingImagePath && !$image)
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" class="h-16 w-16 object-cover rounded mb-2 border border-neutral" alt="Current product image" />
                    @endif
                    <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm" />
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" class="h-16 w-16 object-cover rounded mt-2 border border-neutral" alt="New product image preview" />
                    @endif
                    @error('image') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary" id="isActive" />
                    <label for="isActive" class="text-sm font-medium text-gray-700">Active (visible on the ordering page)</label>
                </div>
            </div>

            {{-- Drag-and-drop custom fields: between Active and the Save buttons,
                 for both Add and Edit. Assignments are held in
                 $assignedFieldIds and only written by Create Product / Save Changes. --}}
            <div class="mt-6 pt-6 border-t border-gray-200">
                <livewire:products.product-fields :assigned-ids="$assignedFieldIds" :key="'product-fields-' . ($editingId ?? 'new')" />
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button.primary wire:click="save">{{ $isDraft ? 'Create Product' : 'Save Changes' }}</x-button.primary>
                <x-button.secondary wire:click="cancel">Cancel</x-button.secondary>
            </div>
        </div>
    @endif

    @unless ($showForm)
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th scope="col" class="px-4 py-3 w-16"></th>
                    <th scope="col" class="px-4 py-3 w-70">Name</th>
                    <th scope="col" class="px-4 py-3 text-center w-35">Sort Order</th>
                    <th scope="col" class="px-4 py-3 text-center w-24">Active</th>
                    <th scope="col" class="px-4 py-3 text-center w-[220px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-300 bg-white">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3">
                            @if ($product->image)
                                <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($product->image) }}" class="h-10 w-10 object-cover rounded border border-neutral" alt="{{ $product->name }}" />
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-center">{{ $product->sort_order }}</td>
                        <!-- <td class="px-4 py-3">{{ $product->sku ?: '—' }}</td> -->
                        <!-- <td class="px-4 py-3">{{ $product->base_price !== null ? number_format((float) $product->base_price, 2) : '—' }}</td> -->
                        <td class="px-4 py-3 text-center">{{ $product->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" wire:click="edit({{ $product->id }})" class="inline-flex items-center justify-center rounded-md border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary-100">Edit</button>
                                <button type="button" wire:click="delete({{ $product->id }})" wire:confirm="Delete this product? This also removes its field assignments and can't be undone." class="inline-flex items-center justify-center rounded-md border border-alert px-3 py-1.5 text-xs font-semibold text-alert hover:bg-alert-100">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-4">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endunless
</div>
