<?php

namespace App\Filament\SuperAdmin\Resources\LandingFooterResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingFooterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingFooters extends ListRecords
{
    protected static string $resource = LandingFooterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
