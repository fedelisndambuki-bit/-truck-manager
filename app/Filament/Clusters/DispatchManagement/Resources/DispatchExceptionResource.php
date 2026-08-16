<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\DispatchExceptionResource\Pages;
use App\Models\WeighbridgeException;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Exceptions" — a 2nd Resource over the same WeighbridgeException table
 * Smart Weighbridge already manages (mirrors the "same model, second
 * cluster-scoped Resource" pattern already used for Stockpile/
 * ProductionDowntime), scoped/labeled for the delivery-journey exception
 * types added in this module (traffic, weather, accident, customer not
 * available, etc.) rather than weighbridge-floor exceptions (overload,
 * scale failure, etc.). Reuses the same real WorkflowEngine approval gate
 * ('weighbridge-exception-approval') — no second parallel workflow.
 */
class DispatchExceptionResource extends Resource
{
    use HasModuleAccess;

    public const DELIVERY_EXCEPTION_TYPES = [
        'Delayed Dispatch', 'Customer Not Available', 'Traffic Delay', 'Weather Delay',
        'Accident', 'Vehicle Diversion', 'Failed Delivery',
    ];

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $model = WeighbridgeException::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Exceptions';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'delivery-exceptions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['dispatchOrder.customer'])->whereIn('exception_type', self::DELIVERY_EXCEPTION_TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(WeighbridgeException::STATUSES, WeighbridgeException::STATUSES);
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
                Forms\Components\Section::make('Delivery Exception')->schema([
                    Forms\Components\TextInput::make('exception_number')->label('Exception #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('dispatch_order_id')->label('Dispatch Order')
                        ->relationship('dispatchOrder', 'dispatch_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->dispatch_number ?? ('Order #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('exception_type')->options(array_combine(self::DELIVERY_EXCEPTION_TYPES, self::DELIVERY_EXCEPTION_TYPES))->required(),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')
                        ->relationship('reportedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_action')->columnSpanFull(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('exception_number')->label('Exception #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dispatchOrder.dispatch_number')->label('Dispatch #')->placeholder('—'),
                Tables\Columns\TextColumn::make('dispatchOrder.customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('exception_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('reason')->limit(40),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('exception_type')->options(array_combine(self::DELIVERY_EXCEPTION_TYPES, self::DELIVERY_EXCEPTION_TYPES)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (WeighbridgeException $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (WeighbridgeException $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('weighbridge-exception-approval', [
                            'title' => 'Delivery Exception — ' . $record->exception_number,
                            'metadata' => ['weighbridge_exception_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
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
            'index' => Pages\ListDispatchExceptions::route('/'),
            'create' => Pages\CreateDispatchException::route('/create'),
            'edit' => Pages\EditDispatchException::route('/{record}/edit'),
        ];
    }
}
