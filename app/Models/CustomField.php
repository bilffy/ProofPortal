<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $table = 'custom_fields';

    public const TYPE_INPUT = 'input';
    public const TYPE_NUMBER = 'number';
    public const TYPE_RADIO = 'radio';
    public const TYPE_DROPDOWN = 'dropdown';
    public const TYPE_IMAGE = 'image';
    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPES = [
        self::TYPE_INPUT => 'Text Input',
        self::TYPE_NUMBER => 'Number',
        self::TYPE_RADIO => 'Radio',
        self::TYPE_DROPDOWN => 'Dropdown',
        self::TYPE_IMAGE => 'Image',
        self::TYPE_CHECKBOX => 'Checkbox',
    ];

    // An option on a radio/dropdown field literally labelled "Other" gets a
    // free-text "please specify" input on the order page instead of just
    // being a fixed choice.
    public const OTHER_OPTION_LABEL = 'other';

    // Types that need a field_options list (radio buttons / dropdown / checkbox choices).
    public const OPTION_TYPES = [self::TYPE_RADIO, self::TYPE_DROPDOWN, self::TYPE_CHECKBOX];

    protected $fillable = [
        'label',
        'type',
        'image',
        'depends_on_field_id',
        'depends_on_values',
        'placeholder',
        'note',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'depends_on_values' => 'array',
    ];

    public function fieldOptions()
    {
        return $this->hasMany(FieldOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_custom_field')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function orderFieldValues()
    {
        return $this->hasMany(OrderFieldValue::class);
    }

    public function dependsOnField()
    {
        return $this->belongsTo(self::class, 'depends_on_field_id');
    }

    // Other fields that only show up depending on this one's value.
    public function dependents()
    {
        return $this->hasMany(self::class, 'depends_on_field_id');
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }

    /**
     * The option_value of this field's "Other" option, if it has one -
     * matched by option_name so admins just label an option "Other".
     */
    public function otherOptionValue(): ?string
    {
        if (!$this->hasOptions()) {
            return null;
        }

        $option = $this->fieldOptions->first(
            fn ($option) => trim(strtolower((string) $option->option_name)) === self::OTHER_OPTION_LABEL
        );

        return $option?->option_value;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image)
            : null;
    }

    public function hasDependency(): bool
    {
        return $this->depends_on_field_id !== null;
    }

    /**
     * Whether this field should be shown, given the currently submitted/
     * selected values of all the fields on the same product
     * (custom_field_id => value).
     *
     * $availableFieldIds is the set of custom_field_ids actually assigned
     * to the product currently being ordered/viewed. If the field this one
     * depends on isn't assigned to that product at all, the dependency
     * doesn't apply here - there's nothing to gate on - so the field shows
     * unconditionally. This lets the same field (e.g. "Lamination Type")
     * be gated behind another field ("Lamination") on products that have
     * both assigned, while showing up unconditionally on products that
     * only have this field assigned and never had the parent to begin with.
     *
     * @param array<int, int|string> $fieldValues
     * @param array<int, int>|null $availableFieldIds
     */
    public function isVisibleGiven(array $fieldValues, ?array $availableFieldIds = null): bool
    {
        if (!$this->hasDependency()) {
            return true;
        }

        if ($availableFieldIds !== null && !in_array($this->depends_on_field_id, $availableFieldIds, true)) {
            return true;
        }

        $currentValue = $fieldValues[$this->depends_on_field_id] ?? null;

        if ($currentValue === null || $currentValue === '') {
            return false;
        }

        // A checkbox parent can have more than one option selected at once
        // (Livewire binds a group of same-path checkboxes as an array), so
        // check whether any of the selected ones match one of the required
        // values, rather than comparing the whole thing as one scalar.
        if (is_array($currentValue)) {
            return count(array_intersect($currentValue, $this->depends_on_values ?? [])) > 0;
        }

        return in_array($currentValue, $this->depends_on_values ?? [], true);
    }
}
