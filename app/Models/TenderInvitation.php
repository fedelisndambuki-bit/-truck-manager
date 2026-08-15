<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderInvitation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'closing_date' => 'date',
        'estimated_value' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (TenderInvitation $tender) {
            if (empty($tender->tender_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $tender->tender_number = 'TDR-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function invitedSubcontractors(): BelongsToMany
    {
        return $this->belongsToMany(Subcontractor::class, 'tender_invitation_subcontractor')
            ->withPivot(['invited_at', 'responded'])
            ->withTimestamps();
    }

    public function bidSubmissions(): HasMany
    {
        return $this->hasMany(BidSubmission::class);
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(TenderClarification::class);
    }

    public function awardedBid(): BelongsTo
    {
        return $this->belongsTo(BidSubmission::class, 'awarded_bid_id');
    }

    public function awardRecommendedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'award_recommended_by_id');
    }

    public function awardApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'award_approved_by_id');
    }
}
