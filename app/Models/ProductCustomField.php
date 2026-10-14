<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-oriented model for the product_custom_field pivot table (composite
 * primary key: product_id + custom_field_id, no surrogate id column).
 * Writes go through Product::customFields()->sync([...]) instead of saving
 * instances of this model directly, since Eloquent's single-column
 * getKeyName()/getKey() machinery doesn't cleanly support a composite key.
 */
class ProductCustomField extends Model
{
    protected $table = 'product_custom_field';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'custom_field_id',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
