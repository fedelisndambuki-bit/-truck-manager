<?php

namespace App\Filament\SuperAdmin\Resources\AccessRequestResource\Pages;

use App\Filament\SuperAdmin\Resources\AccessRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewAccessRequest extends ViewRecord
{
    protected static string $resource = AccessRequestResource::class;

    public function getFooter(): ?View
    {
        return view('filament.super-admin.components.access-request-thread', [
            'threads' => $this->record->informationThreads,
        ]);
    }
}
