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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
