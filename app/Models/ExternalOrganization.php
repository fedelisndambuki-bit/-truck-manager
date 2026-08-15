<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalOrganization extends BaseModel
{
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function portalInvitations(): HasMany
    {
        return $this->hasMany(PortalInvitation::class);
    }
}
