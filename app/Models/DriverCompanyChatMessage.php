<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverCompanyChatMessage extends Model
{
    protected $fillable = [
        'company_id',
        'driver_id',
        'sender_type',
        'sender_user_id',
        'message',
        'voice_note_path',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'read_at',
        'edited_at',
        'deleted_for_everyone_at',
        'deleted_for_driver_at',
        'deleted_for_company_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_for_everyone_at' => 'datetime',
        'deleted_for_driver_at' => 'datetime',
        'deleted_for_company_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
