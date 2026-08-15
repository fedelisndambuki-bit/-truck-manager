<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Customers';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                Client::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->withSum(['payments' => fn ($q) => $q->where('status', 'Paid')], 'amount')
                    ->orderByDesc('payments_sum_amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Total Paid')
                    ->money(fn () => session('currency', 'KES'))
                    ->placeholder('0'),
            ])
            ->paginated(false);
    }
}
