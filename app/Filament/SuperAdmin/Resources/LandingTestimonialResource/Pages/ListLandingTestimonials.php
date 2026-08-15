<?php

namespace App\Filament\SuperAdmin\Resources\LandingTestimonialResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingTestimonials extends ListRecords
{
    protected static string $resource = LandingTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
