<?php

namespace App\Models;

class NotificationSetting extends BaseModel
{
    protected $fillable = [
        'email_notifications',
        'sms_alerts',
        'trip_completion_alerts',
        'settlement_generated_alerts',
        'payment_status_alerts',
    ];
}
