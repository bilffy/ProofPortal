<div>
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Product Fields</h3>
        @unless ($showForm)
            <x-button.primary wire:click="createNew">Add Field</x-button.primary>
        @endunless
    </div>

    @if ($successMessage)
        <div class="rounded-xl border border-success-300 bg-success-100 px-4 py-3 text-sm text-success mb-4" role="status">
            {{ $successMessage }}
        </div>
    @endif

    @if ($showForm)
        <div class="relative mb-8 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
            <h5 class="text-lg font-semibold mb-4">{{ $editingId ? 'Edit Field' : 'New Field' }}</h5>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <input type="text" wire:model="label" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                    @error('label') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="type" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm">
                        @foreach ($typeOptions as $value => $labelText)
                            <option value="{{ $value }}">{{ $labelText }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Placeholder</label>
                    <input type="text" wire:model="placeholder" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note / Help Text</label>
                    <input type="text" wire:model="note" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                </div>

                <div class="flex items-center gap-2 md:col-span-2">
                    <input type="checkbox" wire:model="isRequired" class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary" id="isRequired" />
                    <label for="isRequired" class="text-sm font-medium text-gray-700">Required field</label>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-md border border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">Show this field only when...</label>
                <select wire:model.live="dependsOnFieldId" class="bg-white border border-neutral rounded-md block w-full p-2.5 text-sm">
                    <option value="">Always show this field</option>
                    @foreach ($this->availableParentFields as $parentField)
                        <option value="{{ $parentField->id }}">{{ $parentField->label }} is...</option>
                    @endforeach
                </select>
                @error('dependsOnFieldId') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror

                @if ($dependsOnFieldId)
                    <div class="flex flex-wrap gap-4 mt-3">
                        @foreach ($this->dependsOnFieldOptions as $option)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="dependsOnValues" value="{{ $option->option_value }}" class="h-4 w-4 rounded border-neutral text-primary focus:ring-primary" />
                                {{ $option->option_name }}
                            </label>
                        @endforeach
                    </div>
                    @error('dependsOnValues') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                @endif
            </div>

            @if ($type === \App\Models\CustomField::TYPE_IMAGE)
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Image</label>
                    @if ($existingImagePath && !$image)
                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" class="h-20 w-20 object-cover rounded mb-2 border border-neutral" alt="Current field image" />
                    @endif
                    <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm" />
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 object-cover rounded mt-2 border border-neutral" alt="New field image preview" />
                    @endif
                    <p class="mt-1 text-xs text-gray-500">Shown to customers on the order page next to this field.</p>
                    @error('image') <p class="mt-1 text-sm text-alert">{{ $message }}</p> @enderror
                </div>
            @endif

            @if (in_array($type, \App\Models\CustomField::OPTION_TYPES, true))
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Options</label>
                        <x-button.secondary wire:click="addOption" type="button">Add Option</x-button.secondary>
                    </div>
                    @error('options') <p class="mb-2 text-sm text-alert">{{ $message }}</p> @enderror
                    <p class="mb-2 text-xs text-gray-500">Label an option "Other" to have it show a "please specify" text box on the order page when the customer picks it.</p>

                    <div class="space-y-2">
                        @foreach ($options as $index => $option)
                            <div class="flex items-center gap-2">
                                <input type="text" placeholder="Option label (e.g. Small)" wire:model="options.{{ $index }}.option_name" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                                <input type="text" placeholder="Option value (e.g. S)" wire:model="options.{{ $index }}.option_value" class="bg-gray-50 border border-neutral rounded-md block w-full p-2.5 text-sm" />
                                <button type="button" wire:click="removeOption({{ $index }})" class="inline-flex items-center justify-center rounded-md border border-alert px-3 py-2.5 text-xs font-semibold text-alert hover:bg-alert-100">Remove</button>
                            </div>
                            @error('options.' . $index . '.option_name') <p class="text-sm text-alert">{{ $message }}</p> @enderror
                            @error('options.' . $index . '.option_value') <p class="text-sm text-alert">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 flex items-center gap-3">
                <x-button.primary wire:click="save">{{ $editingId ? 'Save Changes' : 'Create Field' }}</x-button.primary>
                <x-button.secondary wire:click="cancel">Cancel</x-button.secondary>
            </div>
        </div>
    @endif

    @unless ($showForm)
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr>
                    <th scope="col" class="px-4 py-3">Label</th>
                    <th scope="col" class="px-4 py-3">Type</th>
                    <th scope="col" class="px-4 py-3 text-center">Required</th>
                    <th scope="col" class="px-4 py-3 text-center">Options / Image</th>
                    <th scope="col" class="px-4 py-3 text-center w-[160px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-300 bg-white">
                @forelse ($fields as $field)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $field->label }}
                            @if ($field->hasDependency())
                                <p class="text-xs text-gray-400">Only if {{ $field->dependsOnField->label ?? '—' }} is set</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $typeOptions[$field->type] ?? $field->type }}</td>
                        <td class="px-4 py-3 text-center">{{ $field->is_required ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($field->type === \App\Models\CustomField::TYPE_IMAGE)
                                @if ($field->image_url)
                                    <img src="{{ $field->image_url }}" class="h-10 w-10 object-cover rounded border border-neutral mx-auto" alt="{{ $field->label }}" />
                                @else
                                    —
                                @endif
                            @else
                                {{ $field->fieldOptions->count() ?: '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" wire:click="edit({{ $field->id }})" class="inline-flex items-center justify-center rounded-md border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary-100">Edit</button>
                                <button type="button" wire:click="delete({{ $field->id }})" wire:confirm="Delete this field? This also removes it from any products it's assigned to." class="inline-flex items-center justify-center rounded-md border border-alert px-3 py-1.5 text-xs font-semibold text-alert hover:bg-alert-100">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-4">No product fields yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endunless
</div>
