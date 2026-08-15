<?php

namespace App\Filament\Resources\BidSubmissionResource\Pages;

use App\Filament\Resources\BidSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBidSubmissions extends ListRecords
{
    protected static string $resource = BidSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Bid'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-scale',
            'title' => 'Bid Submissions',
            'subtitle' => 'Track and evaluate bid submissions received for tenders.',
        ]);
    }
}
