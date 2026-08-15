<?php

namespace App\Filament\SuperAdmin\Resources\FeatureFlagResource\Pages;

use App\Filament\SuperAdmin\Resources\FeatureFlagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;
}
