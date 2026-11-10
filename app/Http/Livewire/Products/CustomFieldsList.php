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
    public string $prefillSource = ''; // custom_fields.prefill_source
    public string $defaultValue = ''; // text-like types: starting value
    public bool $isDisabled = false;  // text-like types: customer can't change it
    /** @var array<int, array{option_name: string, option_value: string}> */
    public array $options = [];

    // "Image" type: admin-attached reference image shown on the field.
    public $image = null; // new upload, if any
    public ?string $existingImagePath = null;

    // Conditional visibility: only show this field on the order page when
    // ANY of these conditions is met - another field has one of the listed
    // option values selected. Empty list = always show.
    /** @var array<int, array{field_id: int|string|null, values: array<int, string>}> */
    public array $dependsOn = [];

    // Field types that can have a default value and be locked.
    private const TEXT_LIKE_TYPES = [CustomField::TYPE_INPUT, CustomField::TYPE_NUMBER, CustomField::TYPE_TEL];

    public string $successMessage = '';

    protected function rules(): array
    {
        $rules = [
            'label' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(CustomField::TYPES)),
            'placeholder' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'isRequired' => 'boolean',
            'prefillSource' => 'nullable|in:' . implode(',', array_keys(CustomField::PREFILL_SOURCES)),
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

        if (in_array($this->type, self::TEXT_LIKE_TYPES, true)) {
            $rules['defaultValue'] = ($this->isDisabled ? 'required' : 'nullable') . '|string|max:255';
            $rules['isDisabled'] = 'boolean';
        }

        $rules['dependsOn'] = 'array';
        $rules['dependsOn.*.field_id'] = 'required|distinct|exists:custom_fields,id';
        $rules['dependsOn.*.values'] = 'required|array|min:1';

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'defaultValue.required' => 'Enter the value this disabled field should show.',
            'dependsOn.*.field_id.required' => 'Choose a field.',
            'dependsOn.*.field_id.distinct' => 'This field is already used in another condition.',
            'dependsOn.*.values.required' => 'Tick at least one option.',
            'dependsOn.*.values.min' => 'Tick at least one option.',
        ];
    }

    /**
     * Fields this field is allowed to depend on: option-based fields, not
     * itself, and not a field that already depends on this one (that would
     * be a trivial two-way cycle).
     */
    public function getAvailableParentFieldsProperty()
    {
        $fields = CustomField::whereIn('type', CustomField::OPTION_TYPES)->orderBy('label')->get();

        if ($this->editingId) {
            $fields = $fields->filter(
                fn ($field) => $field->id !== $this->editingId
                    && !collect($field->dependencyRules())->contains('field_id', $this->editingId)
            )->values();
        }

        return $fields;
    }

    /**
     * Options of every field used in a condition, keyed by field id, for the
     * tick boxes under each condition.
     */
    public function getParentOptionsProperty()
    {
        $ids = collect($this->dependsOn)->pluck('field_id')->filter()->map(fn ($id) => (int) $id)->all();

        return FieldOption::whereIn('custom_field_id', $ids)->get()->groupBy('custom_field_id');
    }

    public function addCondition(): void
    {
        $this->dependsOn[] = ['field_id' => '', 'values' => []];
    }

    public function removeCondition(int $index): void
    {
        unset($this->dependsOn[$index]);
        $this->dependsOn = array_values($this->dependsOn);
    }

    // Picking a different field in a condition clears its ticked options.
    public function updatedDependsOn($value, $key): void
    {
        if (str_ends_with((string) $key, '.field_id')) {
            $index = (int) explode('.', (string) $key)[0];
            if (isset($this->dependsOn[$index])) {
                $this->dependsOn[$index]['values'] = [];
            }
        }
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
        $this->fields = CustomField::with('fieldOptions')->orderBy('label')->get();
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
        $this->prefillSource = (string) $field->prefill_source;
        $this->defaultValue = (string) $field->default_value;
        $this->isDisabled = (bool) $field->is_disabled;
        $this->options = $field->fieldOptions->map(fn ($option) => [
            'option_name' => $option->option_name,
            'option_value' => $option->option_value,
        ])->toArray();
        $this->existingImagePath = $field->image; //get the image type
        $this->image = null;
        $this->dependsOn = $field->dependencyRules();

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
            $textLike = in_array($this->type, self::TEXT_LIKE_TYPES, true);
            $field->default_value = $textLike && $this->defaultValue !== '' ? $this->defaultValue : null;
            $field->is_disabled = $textLike && $this->isDisabled;
            $field->prefill_source = in_array($this->type, [CustomField::TYPE_IMAGE], true) ? null : ($this->prefillSource ?: null);

            if ($this->type === CustomField::TYPE_IMAGE) {
                if ($this->image) {
                    $field->image = $this->image->store('custom-field-images', 'public');
                }
                // else: keep whatever image (if any) is already saved.
            } else {
                $field->image = null;
            }

            $conditions = [];
            foreach ($this->dependsOn as $condition) {
                $conditions[] = [
                    'field_id' => (int) $condition['field_id'],
                    'values' => array_values(array_map('strval', $condition['values'])),
                ];
            }
            $field->depends_on = $conditions ?: null;

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
        $this->prefillSource = '';
        $this->defaultValue = '';
        $this->isDisabled = false;
        $this->options = [];
        $this->image = null;
        $this->existingImagePath = null;
        $this->dependsOn = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.products.custom-fields-list', [
            'typeOptions' => CustomField::TYPES,
            'prefillOptions' => CustomField::PREFILL_SOURCES,
            'fieldLabels' => CustomField::pluck('label', 'id'),
        ])->layout('layouts.authenticated', [
            'user' => new UserResource(Auth::user()),
        ]);
    }
}
