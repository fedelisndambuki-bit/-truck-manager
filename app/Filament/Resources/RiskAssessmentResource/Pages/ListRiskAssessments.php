<?php

namespace App\Filament\Resources\RiskAssessmentResource\Pages;

use App\Filament\Resources\RiskAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRiskAssessments extends ListRecords
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Risk Assessments',
            'subtitle' => 'Conduct and manage risk assessments across operations.',
        ]);
    }
}
