<?php

namespace App\Models;

class WebhookEvent extends BaseFinancialModel
{
    protected $table = 'fin_webhook_events';
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
