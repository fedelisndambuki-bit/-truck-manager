<?php

namespace App\Filament\SuperAdmin\Resources\LandingFooterLinkResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingFooterLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingFooterLinks extends ListRecords
{
    protected static string $resource = LandingFooterLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
