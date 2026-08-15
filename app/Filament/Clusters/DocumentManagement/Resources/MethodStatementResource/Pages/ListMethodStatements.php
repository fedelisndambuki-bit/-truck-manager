<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMethodStatements extends ListRecords
{
    protected static string $resource = MethodStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Method Statements',
            'subtitle' => 'Manage approved work method statements for site activities.',
        ]);
    }
}
