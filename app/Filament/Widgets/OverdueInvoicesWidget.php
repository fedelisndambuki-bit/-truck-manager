<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Overdue Invoices';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        $companyId = auth()->user()?->company_id;

        return $table
            ->query(
                Invoice::query()
                    ->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId))
                    ->where('due_date', '<', now())
                    ->whereNotIn('status', ['paid'])
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money(session('currency', 'KES'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'   => 'gray',
                        'sent'    => 'info',
                        'overdue' => 'danger',
                        default   => 'warning',
                    }),
            ])
            ->paginated([5, 10])
            ->striped();
    }
}
