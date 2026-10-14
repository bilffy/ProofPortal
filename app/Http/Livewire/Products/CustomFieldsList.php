<?php

namespace App\Http\Livewire\Products;

use App\Http\Resources\UserResource;
use App\Models\CustomField;
use App\Models\FieldOption;
use Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomFieldsList extends Component
{
    use WithFileUploads;

    public $fields = [];

    public bool $showForm = false;
    public ?int $editingId = null;

    // Form state
    public string $label = '';
    public string $type = CustomField::TYPE_INPUT;
    public string $placeholder = '';
    public string $note = '';
    public bool $isRequired = true;
    /** @var array<int, array{option_name: string, option_value: string}> */
    public array $options = [];

    // "Image" type: admin-attached reference image shown on the field.
    public $image = null; // new upload, if any
    public ?string $existingImagePath = null;

    // Conditional visibility: only show this field on the order page when
    // another field ("dependsOnFieldId") is set to one of these option
    // values ("dependsOnValues"). Null/empty dependsOnFieldId = always show.
    public $dependsOnFieldId = null;
    /** @var array<int, string> */
    public array $dependsOnValues = [];

    public string $successMessage = '';

    protected function rules(): array
    {
        $rules = [
            'label' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(CustomField::TYPES)),
            'placeholder' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'isRequired' => 'boolean',
        ];

        if (in_array($this->type, CustomField::OPTION_TYPES, true)) {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*.option_name'] = 'required|string|max:255';
            $rules['options.*.option_value'] = 'required|string|max:255';
        }

        if ($this->type === CustomField::TYPE_IMAGE) {
            // Only required if there isn't already an image saved (i.e. a
            // brand new field, or an existing one that never got one).
            $imageRequired = $this->existingImagePath ? 'nullable' : 'required';
            $rules['image'] = $imageRequired . '|image|max:5120';
        }

        $rules['dependsOnFieldId'] = 'nullable|exists:custom_fields,id';
        if ($this->dependsOnFieldId) {
            $rules['dependsOnValues'] = 'required|array|min:1';
        }

        return $rules;
    }

    /**
     * Fields this field is allowed to depend on: option-based fields, not
     * itself, and not a field that already depends on this one (that would
     * be a trivial two-way cycle).
     */
    public function getAvailableParentFieldsProperty()
    {
        $query = CustomField::whereIn('type', CustomField::OPTION_TYPES)->orderBy('label');

        if ($this->editingId) {
            $query->where('id', '!=', $this->editingId)
                ->where(function ($q) {
                    $q->whereNull('depends_on_field_id')
                        ->orWhere('depends_on_field_id', '!=', $this->editingId);
                });
        }

        return $query->get();
    }

    public function getDependsOnFieldOptionsProperty()
    {
        if (!$this->dependsOnFieldId) {
            return collect();
        }

        return FieldOption::where('custom_field_id', $this->dependsOnFieldId)->get();
    }

    public function updatedDependsOnFieldId(): void
    {
        $this->dependsOnValues = [];
    }

    public function mount()
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        $this->loadFields();
    }

    public function loadFields(): void
    {
        $this->fields = CustomField::with(['fieldOptions', 'dependsOnField'])->orderBy('label')->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $field = CustomField::with('fieldOptions')->findOrFail($id);

        $this->editingId = $field->id;
        $this->label = $field->label;
        $this->type = $field->type ?? CustomField::TYPE_INPUT;
        $this->placeholder = (string) $field->placeholder;
        $this->note = (string) $field->note; // note below the field
        $this->isRequired = (bool) $field->is_required;
        $this->options = $field->fieldOptions->map(fn ($option) => [
            'option_name' => $option->option_name,
            'option_value' => $option->option_value,
        ])->toArray();
        $this->existingImagePath = $field->image; //get the image type
        $this->image = null;
        $this->dependsOnFieldId = $field->depends_on_field_id;
        $this->dependsOnValues = $field->depends_on_values ?? [];

        $this->showForm = true;
        $this->successMessage = '';
    }

    public function addOption(): void
    {
        $this->options[] = ['option_name' => '', 'option_value' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function updatedType(): void
    {
        if (in_array($this->type, CustomField::OPTION_TYPES, true) && empty($this->options)) {
            $this->addOption();
        }
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $field = $this->editingId
                ? CustomField::findOrFail($this->editingId)
                : new CustomField();

            $field->label = $this->label;
            $field->type = $this->type;
            $field->placeholder = $this->placeholder ?: null;
            $field->note = $this->note ?: null;
            $field->is_required = $this->isRequired;

            if ($this->type === CustomField::TYPE_IMAGE) {
                if ($this->image) {
                    $field->image = $this->image->store('custom-field-images', 'public');
                }
                // else: keep whatever image (if any) is already saved.
            } else {
                $field->image = null;
            }

            $field->depends_on_field_id = $this->dependsOnFieldId ?: null;
            $field->depends_on_values = $this->dependsOnFieldId ? array_values($this->dependsOnValues) : null;

            $field->save();

            // Options are fully replaced on every save rather than diffed -
            // simple and safe given these lists are always short, and any
            // order_field_values already saved keep their own text/value
            // copy regardless of what happens to the option definitions.
            $field->fieldOptions()->delete();
            if (in_array($this->type, CustomField::OPTION_TYPES, true)) {
                foreach ($this->options as $option) {
                    $field->fieldOptions()->create([
                        'option_name' => $option['option_name'],
                        'option_value' => $option['option_value'],
                    ]);
                }
            }
        });

        $this->successMessage = $this->editingId
            ? 'Custom field updated.'
            : 'Custom field created.';

        $this->resetForm();
        $this->showForm = false;
        $this->loadFields();
    }

    public function delete(int $id): void
    {
        $field = CustomField::findOrFail($id);
        $field->delete();

        $this->successMessage = 'Custom field deleted.';
        $this->loadFields();
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->type = CustomField::TYPE_INPUT;
        $this->placeholder = '';
        $this->note = '';
        $this->isRequired = true;
        $this->options = [];
        $this->image = null;
        $this->existingImagePath = null;
        $this->dependsOnFieldId = null;
        $this->dependsOnValues = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.products.custom-fields-list', [
            'typeOptions' => CustomField::TYPES,
        ])->layout('layouts.authenticated', [
            'user' => new UserResource(Auth::user()),
        ]);
    }
}
