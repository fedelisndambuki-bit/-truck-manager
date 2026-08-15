<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterial extends ViewRecord
{
    protected static string $resource = MaterialResource::class;

    protected static string $view = 'filament.resources.material-resource.pages.view-material';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getStockLevels()
    {
        return $this->record->stockLevels()->with(['warehouse', 'project', 'site', 'supplier'])->get();
    }

    public function getPurchaseHistory()
    {
        return $this->record->purchaseOrderItems()->with('purchaseOrder.supplier')->latest('created_at')->get();
    }

    public function getConsumptionHistory()
    {
        return $this->record->consumptions()->with(['project', 'site'])->latest('date')->limit(20)->get();
    }

    public function getTransferHistory()
    {
        return $this->record->transfers()->with(['sourceWarehouse', 'destinationWarehouse'])->latest('created_at')->get();
    }

    public function getReturnHistory()
    {
        return $this->record->returns()->with('warehouse')->latest('created_at')->get();
    }

    public function getWastageHistory()
    {
        return $this->record->wastages()->latest('created_at')->get();
    }

    public function getDocuments()
    {
        return $this->record->documents()->latest()->get();
    }
}
