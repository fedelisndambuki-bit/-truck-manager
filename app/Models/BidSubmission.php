<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidSubmission extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'submission_date' => 'date',
        'bid_amount' => 'decimal:2',
    ];

    public function tenderInvitation(): BelongsTo
    {
        return $this->belongsTo(TenderInvitation::class);
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    /**
     * Award this bid: marks it Awarded, rejects every other bid on the same
     * tender, and closes out the TenderInvitation.
     */
    public function award(): void
    {
        static::where('tender_invitation_id', $this->tender_invitation_id)
            ->where('id', '!=', $this->id)
            ->update(['status' => 'Rejected']);

        $this->update(['status' => 'Awarded']);

        $this->tenderInvitation->update([
            'status' => 'Awarded',
            'awarded_bid_id' => $this->id,
        ]);
    }
}
