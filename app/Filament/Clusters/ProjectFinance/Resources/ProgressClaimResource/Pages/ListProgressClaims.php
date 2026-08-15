<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProgressClaims extends ListRecords
{
    protected static string $resource = ProgressClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Progress Claims',
            'subtitle' => 'Submit and certify progress claims for completed project work.',
        ]);
    }
}
