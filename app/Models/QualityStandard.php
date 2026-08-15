<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityStandard extends BaseModel
{
    protected $guarded = [];

    public function testMethods(): HasMany
    {
        return $this->hasMany(TestMethod::class);
    }
}
