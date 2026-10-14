<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldOption extends Model
{
    use HasFactory;

    protected $table = 'field_options';

    protected $fillable = [
        'custom_field_id',
        'option_name',
        'option_value',
    ];

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
