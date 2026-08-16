<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance;
use App\Filament\Clusters\Finance\Resources\FinancialPeriodResource\Pages;
use App\Models\FinancialPeriod;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FinancialPeriodResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'finance.periods';
    }

    protected static ?string $model = FinancialPeriod::class;

    protected static ?string $cluster = Finance::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Financial Periods';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['closedBy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('name')->required()->helperText('e.g. "2026-07" or "Q3 2026".'),
                    Forms\Components\DatePicker::make('period_start')->required(),
                    Forms\Components\DatePicker::make('period_end')->required()->afterOrEqual('period_start'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('period_start')->date()->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Open' => 'success',
                    'Closed' => 'gray',
                    'Locked' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('closedBy.name')->label('Closed By')->toggleable(),
                Tables\Columns\TextColumn::make('closed_at')->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Open' => 'Open', 'Closed' => 'Closed', 'Locked' => 'Locked',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (FinancialPeriod $record) => $record->status === 'Open')
                    ->requiresConfirmation()
                    ->action(fn (FinancialPeriod $record) => $record->close(Auth::user())),
                Tables\Actions\Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (FinancialPeriod $record) => $record->status === 'Closed')
                    ->requiresConfirmation()
                    ->action(fn (FinancialPeriod $record) => $record->reopen()),
                Tables\Actions\EditAction::make()->visible(fn (FinancialPeriod $record) => $record->status === 'Open'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period_start', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialPeriods::route('/'),
            'create' => Pages\CreateFinancialPeriod::route('/create'),
            'edit' => Pages\EditFinancialPeriod::route('/{record}/edit'),
        ];
    }
}
