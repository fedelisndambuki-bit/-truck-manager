<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Purchase Request', 'RFQ', 'Quotation', 'Purchase Order', 'Contract', 'Invoice',
        'Delivery Note', 'Inspection Report', 'Certificate', 'Supporting Document', 'Other',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
