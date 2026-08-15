<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCommitments extends ListRecords
{
    protected static string $resource = CommitmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-currency-dollar',
            'title' => 'Commitments',
            'subtitle' => 'Track committed costs against subcontracts and purchase orders.',
        ]);
    }
}
