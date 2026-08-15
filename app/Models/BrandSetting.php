<?php

namespace App\Models;

class BrandSetting extends BaseModel
{
    protected $fillable = [
        'company_id',
        'company_name',
        'logo_path',
        'icon_path',
    ];
}

