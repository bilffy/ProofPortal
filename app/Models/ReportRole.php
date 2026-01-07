<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportRole extends Model
{
    
    protected $fillable = [
        'report_id',
        'role_id',
    ];

    public function report()
    {
        return $this->belongsTo(User::class, 'report_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

}
