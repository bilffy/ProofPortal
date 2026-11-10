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

    /** @var array<int|string, array<int, string>> field id => option values this product shows */
    public array $allowedOptions = [];

    /**
     * @param array<int, int|string> $assignedIds Field ids already assigned to
     *  the product, in display order (empty for a new product).
     * @param array<int|string, array<int, string>> $allowedOptions Option limits
     *  already saved for the product (field id => option values).
     */
    public function mount(array $assignedIds = [], array $allowedOptions = []): void
    {
        $this->allowedOptions = $allowedOptions;

        $assignedIds = array_values(array_map('intval', $assignedIds));

        $assigned = CustomField::with('fieldOptions')->whereIn('id', $assignedIds)->get()->keyBy('id');

        // Keep the given order; silently skip ids whose field no longer exists.
        $this->assignedFields = collect($assignedIds)
            ->map(fn ($id) => $assigned->get($id))
            ->filter()
            ->values();

        $this->availableFields = CustomField::with('fieldOptions')->whereNotIn('id', $assignedIds)->orderBy('label')->get();
    }

    public function render()
    {
        return view('livewire.products.product-fields');
    }
}
