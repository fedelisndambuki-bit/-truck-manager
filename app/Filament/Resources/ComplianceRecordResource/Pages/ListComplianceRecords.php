<?php

namespace App\Filament\Resources\ComplianceRecordResource\Pages;

use App\Filament\Resources\ComplianceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListComplianceRecords extends ListRecords
{
    protected static string $resource = ComplianceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-scale',
            'title' => 'Compliance Records',
            'subtitle' => 'Track regulatory compliance records and certification statuses across your fleet.',
        ]);
    }
}
