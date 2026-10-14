<?php

namespace App\Http\Livewire\Products;

use App\Models\CustomField;
use Livewire\Component;

/**
 * The drag-and-drop field picker inside the Add / Edit Product form.
 *
 * It only ever renders the starting state it is given - dragging is handled
 * entirely in the browser, and the result is handed to the parent
 * (ProductsList::$assignedFieldIds) without a server round trip. Nothing is
 * written to the database until the parent's Create Product / Save Changes.
 */
class ProductFields extends Component
{
    public $assignedFields = [];
    public $availableFields = [];

    /**
     * @param array<int, int|string> $assignedIds Field ids already assigned to
     *  the product, in display order (empty for a new product).
     */
    public function mount(array $assignedIds = []): void
    {
        $assignedIds = array_values(array_map('intval', $assignedIds));

        $assigned = CustomField::whereIn('id', $assignedIds)->get()->keyBy('id');

        // Keep the given order; silently skip ids whose field no longer exists.
        $this->assignedFields = collect($assignedIds)
            ->map(fn ($id) => $assigned->get($id))
            ->filter()
            ->values();

        $this->availableFields = CustomField::whereNotIn('id', $assignedIds)->orderBy('label')->get();
    }

    public function render()
    {
        return view('livewire.products.product-fields');
    }
}
