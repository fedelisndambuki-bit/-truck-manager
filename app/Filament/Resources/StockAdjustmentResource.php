<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\StockAdjustment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.adjust';
    }

    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Stock Adjustments';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 17;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['material', 'warehouse']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adjustment Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('adjustment_type')->options(['Positive' => 'Positive', 'Negative' => 'Negative'])->default('Positive')->required(),
                    Forms\Components\Select::make('reason')->options([
                        'Damaged' => 'Damaged', 'Theft' => 'Theft', 'Correction' => 'Correction', 'Other' => 'Other',
                    ])->default('Correction')->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\Select::make('approved_by_id')
                        ->label('Approved By')
                        ->relationship('approvedBy', 'full_name')
                        ->searchable()->preload(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse'),
                Tables\Columns\TextColumn::make('adjustment_type')->badge()->color(fn (string $state) => $state === 'Positive' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('reason')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('source')->badge()->color(fn (string $state) => $state === 'PhysicalCount' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Approved' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('adjustment_type')->options(['Positive' => 'Positive', 'Negative' => 'Negative']),
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Approved' => 'Approved']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (StockAdjustment $record) {
                        $record->approve();
                        Notification::make()->title('Adjustment approved and applied')->success()->send();
                    })
                    ->visible(fn (StockAdjustment $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.adjust'))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
