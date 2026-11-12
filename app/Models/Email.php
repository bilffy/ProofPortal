<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    protected $table = 'emails';

    public const ORDER_RECIPIENT_PRINT = 'print';
    public const ORDER_RECIPIENT_REQUESTOR = 'requestor';

    use SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'generated_from_user_id',
        'alphacode',
        'school_id',
        'ts_jobkey',
        'ts_schoolkey',
        'sentdate',
        'email_from',
        'email_to',
        'email_to_userId',
        'email_cc',
        'email_bcc',
        'email_content',
        'smtp_code',
        'smtp_message',
        'email_token',
        'template_id',
        'status_id', 
        'order_id',
        'order_recipient',
        'deleted_at'
    ];

    /**
     * Sets recipient_firstname / recipient_lastname on each email of the page: the name of the
     * user the email was sent to (email_to_userId when set, otherwise the user with that
     * email address). Blank when no user has that address. One query for the whole page.
     *
     * @param  iterable<Email>  $emails
     */
    public static function attachRecipientNames(iterable $emails): void
    {
        // collect() on a paginator would turn it into its toArray() (page meta), not its items.
        $emails = $emails instanceof \Illuminate\Pagination\AbstractPaginator
            ? $emails->getCollection()
            : ($emails instanceof \Illuminate\Support\Collection ? $emails : collect(is_array($emails) ? $emails : iterator_to_array($emails)));

        $addresses = $emails->pluck('email_to')->filter()->map(fn ($e) => mb_strtolower(trim($e)))->unique()->values();
        $ids = $emails->pluck('email_to_userId')->filter()->unique()->values();

        $users = User::withoutGlobalScopes()
            ->where(fn ($q) => $q->whereIn('email', $addresses)->orWhereIn('id', $ids))
            ->get(['id', 'email', 'firstname', 'lastname']);

        $byId = $users->keyBy('id');
        $byEmail = $users->keyBy(fn ($u) => mb_strtolower(trim($u->email)));

        foreach ($emails as $email) {
            $user = ($email->email_to_userId ? $byId->get($email->email_to_userId) : null)
                ?? $byEmail->get(mb_strtolower(trim((string) $email->email_to)));

            $email->recipient_firstname = $user?->firstname;
            $email->recipient_lastname = $user?->lastname;
        }
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
