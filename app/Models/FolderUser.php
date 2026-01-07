<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FolderUser extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ts_folder_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'ts_folder_id', 'ts_folder_id');
    }
}
