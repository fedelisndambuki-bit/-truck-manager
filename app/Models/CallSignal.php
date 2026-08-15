<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallSignal extends Model
{
    protected $fillable = [
        'room',
        'sender',
        'type',
        'payload',
    ];
}
