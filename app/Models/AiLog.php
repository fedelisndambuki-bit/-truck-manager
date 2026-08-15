<?php

namespace App\Models;

use App\Traits\MultiTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    use HasFactory, MultiTenantScope;

    protected $fillable = [
        'company_id',
        'user_id',
        'question',
        'response',
        'fleet_data',
        'response_time_ms',
        'status',
        'error_message',
        'rating',
    ];

    protected $casts = [
        'fleet_data' => 'array',
        'response_time_ms' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
