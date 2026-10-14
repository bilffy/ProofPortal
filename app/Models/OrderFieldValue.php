<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFieldValue extends Model
{
    use HasFactory;

    protected $table = 'order_field_values';

    protected $fillable = [
        'order_id',
        'custom_field_id',
        'value',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
