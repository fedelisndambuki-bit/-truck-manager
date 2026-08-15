<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSitePhotos extends ListRecords
{
    protected static string $resource = SitePhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-camera',
            'title' => 'Site Photos',
            'subtitle' => 'Browse and manage photographic records of on-site project progress.',
        ]);
    }
}
