<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtworkRequired extends Model
{
    use HasFactory;

    protected $table = 'artworks_required';

    protected $fillable = [
        'details',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'artwork_required_id');
    }
}
