<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockTransferResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Stock Transfers';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Details')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                ])->columns(2),
                Forms\Components\Section::make('Source')->schema([
                    Forms\Components\Select::make('source_warehouse_id')
                        ->label('Warehouse')
                        ->relationship('sourceWarehouse', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('source_project_id')
                        ->label('Project')
                        ->relationship('sourceProject', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('source_site_id')
                        ->label('Site')
                        ->relationship('sourceSite', 'name')
                        ->searchable()->preload(),
                ])->columns(3),
                Forms\Components\Section::make('Destination')->schema([
                    Forms\Components\Select::make('destination_warehouse_id')
                        ->label('Warehouse')
                        ->relationship('destinationWarehouse', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('destination_project_id')
                        ->label('Project')
                        ->relationship('destinationProject', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('destination_site_id')
                        ->label('Site')
                        ->relationship('destinationSite', 'name')
                        ->searchable()->preload(),
                ])->columns(3),
                Forms\Components\Section::make('Transport & Approval')->schema([
                    Forms\Components\Select::make('approved_by_id')
                        ->label('Approved By')
                        ->relationship('approvedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Textarea::make('transport_details'),
                    Forms\Components\Select::make('status')->options([
                        'Pending' => 'Pending', 'In Transit' => 'In Transit', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    ])->default('Pending')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->label('Transfer #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable(),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('sourceWarehouse.name')->label('From'),
                Tables\Columns\TextColumn::make('destinationWarehouse.name')->label('To'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success', 'Rejected' => 'danger', 'In Transit' => 'info', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'In Transit' => 'In Transit', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This moves stock from the source warehouse to the destination warehouse.')
                    ->action(function (StockTransfer $record) {
                        try {
                            $record->approve();
                            Notification::make()->title('Transfer approved and stock moved')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot approve')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (StockTransfer $record) => $record->status !== 'Approved'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.transfer'))),
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
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
