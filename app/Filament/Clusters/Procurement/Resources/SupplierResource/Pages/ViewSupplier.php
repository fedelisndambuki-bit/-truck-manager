<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Profile')->schema([
                TextEntry::make('supplier_number')->label('Supplier #'),
                TextEntry::make('company_name'),
                TextEntry::make('category')->badge()->placeholder('—'),
                TextEntry::make('contact_person')->placeholder('—'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('email')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => SupplierResource::getStatusColor($state)),
            ])->columns(4),

            Section::make('Performance & Risk')->schema([
                TextEntry::make('performance_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('delivery_performance_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('quality_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('risk_rating')->badge()->color(fn (string $state): string => SupplierResource::getRiskColor($state)),
            ])->columns(4),

            Section::make('Financial & Compliance')->schema([
                TextEntry::make('credit_limit')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('payment_terms')->placeholder('—'),
                TextEntry::make('contract_start_date')->date()->placeholder('—'),
                TextEntry::make('contract_end_date')->date()->placeholder('—'),
                TextEntry::make('insurance_expiry')->date()->placeholder('—'),
            ])->columns(4),
        ]);
    }
}
