<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-wallet',
            'title' => 'Wallets',
            'subtitle' => 'View company wallet balances across revenue, expense, and driver pools.',
        ]);
    }
}
