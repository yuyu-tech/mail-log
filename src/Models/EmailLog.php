<?php

namespace Yuyu\MailLog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Yuyu\MailLog\Models\EmailVisitorHistory;

class EmailLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_log';

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return config('mailLog.database') ?? $this->connection;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'filtered_to' => 'array',
        'filtered_cc' => 'array',
        'filtered_bcc' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'status',
        'error',
    ];

    /**
     * Get visitors for the mail.
     */
    public function visitors()
    {
        return $this->hasMany(EmailVisitorHistory::class);
    }
}
