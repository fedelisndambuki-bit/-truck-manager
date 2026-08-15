<?php

namespace App\Filament\SuperAdmin\Resources\LandingTestimonialResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingTestimonial extends EditRecord
{
    protected static string $resource = LandingTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
