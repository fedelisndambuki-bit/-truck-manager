<?php

namespace App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource\Pages;

use App\Filament\Clusters\Procurement\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRequestForQuotations extends ListRecords
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New RFQ'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-envelope-open',
            'title' => 'RFQ Management',
            'subtitle' => 'Issue requests for quotation to suppliers and track bidding status.',
        ]);
    }
}
