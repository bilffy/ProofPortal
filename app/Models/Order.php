<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'product_id',
        'order_number',
        'status_id',
        'artwork_required_id',
        'artwork_due_date',
        'artwork_brief',
        'additional_comments',
    ];

    protected $casts = [
        'artwork_due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function fieldValues()
    {
        return $this->hasMany(OrderFieldValue::class);
    }

    /**
     * Name, email and phone the customer typed into fields pre-filled from
     * their account (custom_fields.prefill_source). They address the order
     * emails and override the account's own details.
     *
     * @return array<string, string> keyed by prefill_source
     */
    public function contactOverrides(): array
    {
        $overrides = [];

        foreach ($this->fieldValues as $fieldValue) {
            $source = $fieldValue->customField?->prefill_source;

            if (in_array($source, ['first_name', 'last_name', 'email', 'phone', 'school_name'], true)
                && trim((string) $fieldValue->value) !== '') {
                $overrides[$source] = trim((string) $fieldValue->value);
            }
        }

        return $overrides;
    }

    public function artworkFiles()
    {
        return $this->hasMany(OrderArtworkFile::class);
    }

    public function artworkRequired()
    {
        return $this->belongsTo(ArtworkRequired::class, 'artwork_required_id');
    }

    public function emails()
    {
        return $this->hasMany(Email::class, 'order_id');
    }

    public static function generateOrderNumber(): string
    {
        $year = now()->year;
        $prefix = 'ORD-' . $year . '-';

        $lastNumber = static::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('order_number');

        $nextSequence = 1;
        if ($lastNumber && preg_match('/(\d+)$/', $lastNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
