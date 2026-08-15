<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeightValidationResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeightValidationResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWeightValidation extends ListRecords
{
    protected static string $resource = WeightValidationResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-adjustments-vertical',
            'title' => 'Weight Validation',
            'subtitle' => 'Validate recorded weights against expected tolerances.',
        ]);
    }
}
