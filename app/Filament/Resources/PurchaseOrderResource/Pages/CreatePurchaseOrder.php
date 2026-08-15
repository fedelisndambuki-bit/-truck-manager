<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreatePurchaseOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = PurchaseOrderResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Supplier & Terms')
                ->icon('heroicon-o-truck')
                ->schema(PurchaseOrderResource::supplierTermsSchema())
                ->columns(2),
            Step::make('Line Items')
                ->icon('heroicon-o-list-bullet')
                ->schema(PurchaseOrderResource::itemsSchema()),
            Step::make('Delivery & Tax')
                ->icon('heroicon-o-truck')
                ->schema(PurchaseOrderResource::deliveryTaxSchema())
                ->columns(2),
            Step::make('Approval')
                ->icon('heroicon-o-check-badge')
                ->schema(PurchaseOrderResource::approvalSchema()),
        ];
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }
}
