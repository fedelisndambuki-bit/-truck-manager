<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource;
use App\Models\Stockpile;
use App\Services\Quarry\QualityHoldService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQualityHold extends CreateRecord
{
    protected static string $resource = QualityHoldResource::class;

    /**
     * Routes through QualityHoldService::place() instead of a bare
     * QualityHold::create() so manually-placed holds get the same real
     * Stockpile.quality_status mutation + StockMovement ledger row that
     * every other hold-creation entry point in the app now produces.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $stockpile = Stockpile::findOrFail($data['stockpile_id']);

        $hold = app(QualityHoldService::class)->place(
            $stockpile,
            $data['reason'],
            $data['held_by_id'] ?? null,
            $data['lab_sample_id'] ?? null,
            $data['notes'] ?? null
        );

        return $hold;
    }
}
