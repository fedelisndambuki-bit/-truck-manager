<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BinLocation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'capacity' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::created(function (BinLocation $bin) {
            if ($bin->code && empty($bin->qr_code)) {
                $bin->qr_code = QrCode::size(150)->generate($bin->code);
                $bin->saveQuietly();
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    public function binStock(): HasMany
    {
        return $this->hasMany(BinStock::class);
    }

    public function getTotalStockAttribute(): float
    {
        return (float) $this->binStock()->sum('quantity');
    }
}
