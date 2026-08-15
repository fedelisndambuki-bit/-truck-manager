<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\LoadingInstructionResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingInstructionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLoadingInstructions extends ListRecords
{
    protected static string $resource = LoadingInstructionResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Loading Instructions',
            'subtitle' => 'Issue and track loading instructions for dispatched vehicles.',
        ]);
    }
}
