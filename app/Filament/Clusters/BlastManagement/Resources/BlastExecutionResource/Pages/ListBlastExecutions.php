<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastExecutionResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastExecutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastExecutions extends ListRecords
{
    protected static string $resource = BlastExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-bolt',
            'title' => 'Blast Executions',
            'subtitle' => 'Track the execution and outcomes of scheduled blasts.',
        ]);
    }
}
