<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource\Pages;
use App\Models\StockpilePhysicalCount;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PhysicalStockCountResource extends Resource
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

    protected static ?string $model = StockpilePhysicalCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Physical Counts';

    protected static ?int $navigationSort = 9;

    public static function getStatusOptions(): array
    {
        return array_combine(StockpilePhysicalCount::STATUSES, StockpilePhysicalCount::STATUSES);
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
                Forms\Components\Section::make('Physical Count Details')->schema([
                    Forms\Components\TextInput::make('count_number')->label('Count #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required()->live(),
                    Forms\Components\TextInput::make('system_quantity')->label('System Quantity')->numeric()->suffix('t')->disabled()->dehydrated(false)
                        ->helperText('Captured automatically from the stockpile at the time this count is created.'),
                    Forms\Components\TextInput::make('physical_quantity')->label('Physical Quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\Select::make('survey_method')->options(array_combine(StockpilePhysicalCount::SURVEY_METHODS, StockpilePhysicalCount::SURVEY_METHODS))->required(),
                    Forms\Components\DatePicker::make('count_date')->default(now())->required(),
                    Forms\Components\Select::make('counted_by_id')->label('Counted By')->relationship('countedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('count_number')->label('Count #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('system_quantity')->label('System Qty')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('physical_quantity')->label('Physical Qty')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('variance')
                    ->label('Variance')
                    ->state(fn (StockpilePhysicalCount $record) => $record->variance() . ' t')
                    ->color(fn (StockpilePhysicalCount $record): string => $record->variance() < 0 ? 'danger' : ($record->variance() > 0 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('variance_percent')
                    ->label('Variance %')
                    ->state(fn (StockpilePhysicalCount $record) => $record->variancePercent() . '%'),
                Tables\Columns\TextColumn::make('survey_method')->label('Method')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('survey_method')->options(array_combine(StockpilePhysicalCount::SURVEY_METHODS, StockpilePhysicalCount::SURVEY_METHODS)),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (StockpilePhysicalCount $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (StockpilePhysicalCount $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('physical-count-approval', [
                            'title' => 'Physical Stock Count — ' . $record->count_number,
                            'metadata' => ['stockpile_physical_count_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (StockpilePhysicalCount $record) => $record->currentApproval() !== null)
                    ->url(fn (StockpilePhysicalCount $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
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
            'index' => Pages\ListPhysicalStockCounts::route('/'),
            'create' => Pages\CreatePhysicalStockCount::route('/create'),
            'edit' => Pages\EditPhysicalStockCount::route('/{record}/edit'),
        ];
    }
}
