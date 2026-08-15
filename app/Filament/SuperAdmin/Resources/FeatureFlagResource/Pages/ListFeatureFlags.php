<?php

namespace App\Filament\SuperAdmin\Resources\FeatureFlagResource\Pages;

use App\Filament\SuperAdmin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;

    protected static string $view = 'filament.super-admin.resources.feature-flag-resource.pages.list-feature-flags';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
