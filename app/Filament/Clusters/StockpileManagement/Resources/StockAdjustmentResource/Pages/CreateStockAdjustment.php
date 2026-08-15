<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use App\Models\StockpileDocument;
use Filament\Resources\Pages\CreateRecord;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected ?string $pendingDocumentPath = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingDocumentPath = $data['supporting_document'] ?? null;
        unset($data['supporting_document']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingDocumentPath) {
            StockpileDocument::create([
                'company_id' => $this->record->company_id,
                'documentable_type' => $this->record->getMorphClass(),
                'documentable_id' => $this->record->id,
                'category' => 'Adjustment Evidence',
                'file_path' => $this->pendingDocumentPath,
                'uploaded_by_id' => auth()->id(),
            ]);
        }
    }
}
