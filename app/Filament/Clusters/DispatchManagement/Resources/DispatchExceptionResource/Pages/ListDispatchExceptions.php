<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DispatchExceptionResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DispatchExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDispatchExceptions extends ListRecords
{
    protected static string $resource = DispatchExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Dispatch Exceptions',
            'subtitle' => 'Review and resolve issues flagged during dispatch operations.',
        ]);
    }
}
