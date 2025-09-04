<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilenameFormat extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filename_formats';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'format',
        'format_key',
        'visibility',
        'visibility_options',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'visibility' => 'array',
        'visibility_options' => 'array',
    ];
}
