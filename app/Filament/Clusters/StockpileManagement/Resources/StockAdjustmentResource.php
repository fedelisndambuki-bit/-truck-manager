<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;
use App\Models\StockpileAdjustment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockAdjustmentResource extends Resource
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

    protected static ?string $model = StockpileAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Stock Adjustments';

    protected static ?int $navigationSort = 8;

    public static function getStatusOptions(): array
    {
        return array_combine(StockpileAdjustment::STATUSES, StockpileAdjustment::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success',
            'Submitted' => 'warning',
            'Draft' => 'gray',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adjustment Details')->schema([
                    Forms\Components\TextInput::make('adjustment_number')->label('Adjustment #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('quantity')->label('Quantity (+/-)')->numeric()->suffix('t')->required()
                        ->helperText('Positive to increase stock, negative to decrease.'),
                    Forms\Components\Select::make('reason')->options(array_combine(StockpileAdjustment::REASONS, StockpileAdjustment::REASONS))->required(),
                    Forms\Components\Select::make('requested_by_id')->label('Requested By')->relationship('requestedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                    Forms\Components\FileUpload::make('supporting_document')
                        ->label('Supporting Document')
                        ->directory('stockpile-documents'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_number')->label('Adjustment #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')->numeric()->suffix(' t')
                    ->color(fn (StockpileAdjustment $record): string => $record->quantity < 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('reason')->badge(),
                Tables\Columns\TextColumn::make('requestedBy.full_name')->label('Requested By')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('reason')->options(array_combine(StockpileAdjustment::REASONS, StockpileAdjustment::REASONS)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (StockpileAdjustment $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (StockpileAdjustment $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('stock-adjustment-approval', [
                            'title' => 'Stock Adjustment — ' . $record->adjustment_number,
                            'metadata' => ['stockpile_adjustment_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (StockpileAdjustment $record) => $record->currentApproval() !== null)
                    ->url(fn (StockpileAdjustment $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
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
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
