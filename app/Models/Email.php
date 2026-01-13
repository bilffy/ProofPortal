<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    protected $table = 'emails';

    use SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'generated_from_user_id',
        'alphacode',
        'ts_jobkey',
        'ts_schoolkey',
        'sentdate',
        'email_from',
        'email_to',
        'email_cc',
        'email_bcc',
        'email_content',
        'smtp_code',
        'smtp_message',
        'email_token',
        'template_id',
    ];
}
