<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyIncidentAttachment extends Model
{
    protected $guarded = [];

    public function safetyIncident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
