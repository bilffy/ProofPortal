<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        // 'base_price',
        'sku',
        'is_active',
        'show_artwork',
        'print_request_email',
        'print_to_franchise',
        'show_in_emails',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_artwork' => 'boolean',
        'show_in_emails' => 'boolean',
        'print_to_franchise' => 'boolean',
        'sort_order' => 'integer',
        // 'base_price' => 'decimal:4',
    ];

    // Assigned fields, in the drag-set display order (product_custom_field.sort_order).
    public function customFields()
    {
        return $this->belongsToMany(CustomField::class, 'product_custom_field')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Splits a "a@x.com, b@y.com" style value (commas, semicolons, spaces or
     * new lines) into a unique list of trimmed addresses. Invalid ones are
     * kept so validation can report them.
     *
     * @return string[]
     */
    public static function splitEmails(?string $value): array
    {
        $parts = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($parts));
    }

    /**
     * The valid addresses the print team's request email goes to.
     *
     * @return string[]
     */
    public function printRequestEmails(): array
    {
        return array_values(array_filter(
            self::splitEmails($this->print_request_email),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $suffix++;
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }
}
