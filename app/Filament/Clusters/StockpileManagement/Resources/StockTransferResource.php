<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockTransferResource\Pages;
use App\Models\ProductionStockTransfer;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockTransferResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = StockpileManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'stockpile.view';
    }

    protected static ?string $model = ProductionStockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Stock Transfers';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['material', 'sourceStockpile', 'destinationStockpile']);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(ProductionStockTransfer::STATUSES, ProductionStockTransfer::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Completed' => 'success',
            'Approved' => 'info',
            'Submitted' => 'warning',
            'Pending' => 'gray',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function getReceivedStatusColor(string $state): string
    {
        return match ($state) {
            'Received' => 'success',
            default => 'warning',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Details')->schema([
                    Forms\Components\TextInput::make('transfer_number')->label('Transfer #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\Select::make('source_stockpile_id')->label('Source Stockpile')
                        ->relationship('sourceStockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('destination_stockpile_id')->label('Destination Stockpile')
                        ->relationship('destinationStockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('vehicle_registration')->label('Transport Vehicle'),
                    Forms\Components\TextInput::make('driver_name')->label('Driver'),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Pending')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->label('Transfer #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('quantity')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('sourceStockpile.label')->label('From')->placeholder('—'),
                Tables\Columns\TextColumn::make('destinationStockpile.label')->label('To')->placeholder('—'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                Tables\Columns\TextColumn::make('driver_name')->label('Driver')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('received_status')->label('Received')->badge()->color(fn (string $state): string => self::getReceivedStatusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('received_status')->label('Received Status')->options(array_combine(ProductionStockTransfer::RECEIVED_STATUSES, ProductionStockTransfer::RECEIVED_STATUSES)),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionStockTransfer $record) => $record->status === 'Pending' && ! $record->isApprovalPending())
                    ->action(function (ProductionStockTransfer $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('stock-transfer-approval', [
                            'title' => 'Stock Transfer — ' . $record->transfer_number,
                            'metadata' => ['production_stock_transfer_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (ProductionStockTransfer $record) => $record->currentApproval() !== null)
                    ->url(fn (ProductionStockTransfer $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('markReceived')
                    ->label('Mark Received')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionStockTransfer $record) => $record->status === 'Approved' && $record->received_status === 'Pending')
                    ->action(function (ProductionStockTransfer $record) {
                        $record->update([
                            'received_status' => 'Received',
                            'received_at' => now(),
                        ]);
                        Notification::make()->title('Transfer marked as received at destination')->success()->send();
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Complete Transfer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionStockTransfer $record) => $record->status === 'Approved' && $record->received_status === 'Received')
                    ->action(function (ProductionStockTransfer $record) {
                        try {
                            $record->complete();
                            Notification::make()->title('Transfer completed — stock moved')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
