<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Payouts',
            'subtitle' => 'Review and approve pending payouts to drivers and vendors.',
        ]);
    }
}
