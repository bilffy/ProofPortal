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
    public const TYPE_TEL = 'tel';
    public const TYPE_RADIO = 'radio';
    public const TYPE_DROPDOWN = 'dropdown';
    public const TYPE_IMAGE = 'image';
    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPES = [
        self::TYPE_INPUT => 'Text Input',
        self::TYPE_NUMBER => 'Number',
        self::TYPE_TEL => 'Phone Number',
        self::TYPE_RADIO => 'Radio',
        self::TYPE_DROPDOWN => 'Dropdown',
        self::TYPE_IMAGE => 'Image',
        self::TYPE_CHECKBOX => 'Checkbox',
    ];

    // Logged-in user details a field can be pre-filled with on the order page
    // (the customer can still edit them). The first_name, last_name, email and
    // phone sources also address the order emails.
    public const PREFILL_SOURCES = [
        'first_name' => 'User first name',
        'last_name' => 'User last name',
        'email' => 'User email address',
        'phone' => 'User phone number',
        'school_name' => 'User school name',
        'school_postcode' => 'User school postcode',
        'role' => 'User role',
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
        'depends_on',
        'placeholder',
        'default_value',
        'is_disabled',
        'note',
        'is_required',
        'prefill_source',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'depends_on' => 'array',
        'is_disabled' => 'boolean',
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

    /**
     * The "show only when" conditions, as [['field_id' => int, 'values' => string[]], ...].
     * The field shows when any one of them is met.
     *
     * @return array<int, array{field_id: int, values: string[]}>
     */
    public function dependencyRules(): array
    {
        $rules = [];

        foreach ((array) $this->depends_on as $rule) {
            $fieldId = (int) ($rule['field_id'] ?? 0);
            $values = array_values(array_map('strval', (array) ($rule['values'] ?? [])));

            if ($fieldId > 0 && $values !== []) {
                $rules[] = ['field_id' => $fieldId, 'values' => $values];
            }
        }

        return $rules;
    }

    /**
     * The value this field starts with for the given user, or null. A
     * choice field only gets it when it matches one of its options.
     */
    public function prefillValueFor(User $user): ?string
    {
        $school = in_array($this->prefill_source, ['school_name', 'school_postcode'], true) ? $user->getSchool() : null;

        $value = match ($this->prefill_source) {
            'first_name' => $user->firstname,
            'last_name' => $user->lastname,
            'email' => $user->email,
            'phone' => $user->contact,
            'school_name' => $school?->name,
            'school_postcode' => $school?->postcode,
            'role' => $user->getRole(),
            default => null,
        };

        $value = $value === null ? null : trim((string) $value);

        if ($value === null || $value === '') {
            return null;
        }

        if ($this->hasOptions() && !$this->fieldOptions->contains('option_value', $value)) {
            return null;
        }

        return $value;
    }

    /** Types that can carry a default value and be locked (free-typed fields). */
    public function isTextLike(): bool
    {
        return in_array($this->type, [self::TYPE_INPUT, self::TYPE_NUMBER, self::TYPE_TEL], true);
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
        return $this->dependencyRules() !== [];
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
        $rules = $this->dependencyRules();

        // Conditions on a field that isn't assigned to this product don't apply.
        if ($availableFieldIds !== null) {
            $rules = array_values(array_filter(
                $rules,
                fn ($rule) => in_array($rule['field_id'], $availableFieldIds, true)
            ));
        }

        if ($rules === []) {
            return true;
        }

        // Shown when ANY condition is met.
        foreach ($rules as $rule) {
            $currentValue = $fieldValues[$rule['field_id']] ?? null;

            if ($currentValue === null || $currentValue === '') {
                continue;
            }

            // A checkbox parent can have more than one option selected at once
            // (Livewire binds a group of same-path checkboxes as an array), so
            // check whether any of the selected ones match one of the required
            // values, rather than comparing the whole thing as one scalar.
            $selected = array_map('strval', (array) $currentValue);

            if (count(array_intersect($selected, $rule['values'])) > 0) {
                return true;
            }
        }

        return false;
    }
}
