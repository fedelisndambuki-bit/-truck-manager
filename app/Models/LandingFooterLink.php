<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingFooterLink extends Model
{
    protected $guarded = [];

    const GROUPS = [
        'platform' => 'Platform',
        'business' => 'Business',
        'resources' => 'Resources',
        'company' => 'Company',
    ];
}
