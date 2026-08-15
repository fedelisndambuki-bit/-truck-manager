<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cargo extends BaseModel
{
    // Explicitly tell Laravel to use the singular table name
    protected $table = 'cargo';

    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
