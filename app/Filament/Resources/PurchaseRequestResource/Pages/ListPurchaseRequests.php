<?php

namespace App\Filament\Resources\PurchaseRequestResource\Pages;

use App\Filament\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateFromLowStock')
                ->label('Generate from Low Stock')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Creates a purchase request for every active material currently at or below its reorder level that does not already have an open auto-generated request.')
                ->action(function () {
                    $count = PurchaseRequest::generateFromLowStock(Auth::user()->company_id);
                    Notification::make()->title($count . ' purchase request(s) generated')->success()->send();
                }),
            Actions\CreateAction::make()->label('New Request'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-plus',
            'title' => 'Purchase Requests',
            'subtitle' => 'Review and approve material purchase requests.',
        ]);
    }
}
