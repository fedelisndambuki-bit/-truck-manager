<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentAttachments extends ListRecords
{
    protected static string $resource = EquipmentAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-puzzle-piece',
            'title' => 'Equipment Attachments',
            'subtitle' => 'Manage attachments and add-ons associated with equipment.',
        ]);
    }
}
