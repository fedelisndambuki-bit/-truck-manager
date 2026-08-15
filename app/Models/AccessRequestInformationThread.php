<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequestInformationThread extends Model
{
    protected $guarded = [];

    const UPDATED_AT = null;

    const AUTHOR_SUPERADMIN = 'superadmin';
    const AUTHOR_APPLICANT = 'applicant';

    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(AccessRequest::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
