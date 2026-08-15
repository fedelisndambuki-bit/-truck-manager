<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource\Pages;
use App\Models\WeighbridgeException;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WeighbridgeExceptionResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = SmartWeighbridge::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = WeighbridgeException::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Exceptions';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'exceptions';

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
                Forms\Components\Section::make('Exception Details')->schema([
                    Forms\Components\TextInput::make('exception_number')->label('Exception #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('dispatch_order_id')->label('Dispatch Order')
                        ->relationship('dispatchOrder', 'dispatch_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->dispatch_number ?? ('Order #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('exception_type')->options(array_combine(WeighbridgeException::EXCEPTION_TYPES, WeighbridgeException::EXCEPTION_TYPES))->required(),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')
                        ->relationship('reportedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_action')->columnSpanFull(),
                    Forms\Components\Textarea::make('preventive_action')->columnSpanFull(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Exception Details')->schema([
                TextEntry::make('exception_number')->label('Exception #'),
                TextEntry::make('dispatchOrder.dispatch_number')->label('Dispatch #')->placeholder('—'),
                TextEntry::make('exception_type')->badge(),
                TextEntry::make('reason')->columnSpanFull(),
                TextEntry::make('comments')->placeholder('—')->columnSpanFull(),
                TextEntry::make('corrective_action')->placeholder('—')->columnSpanFull(),
                TextEntry::make('preventive_action')->placeholder('—')->columnSpanFull(),
                TextEntry::make('reportedBy.full_name')->label('Reported By')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('exception_number')->label('Exception #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dispatchOrder.dispatch_number')->label('Dispatch #')->placeholder('—'),
                Tables\Columns\TextColumn::make('exception_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('reason')->limit(40),
                Tables\Columns\TextColumn::make('reportedBy.full_name')->label('Reported By')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('exception_type')->options(array_combine(WeighbridgeException::EXCEPTION_TYPES, WeighbridgeException::EXCEPTION_TYPES)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (WeighbridgeException $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (WeighbridgeException $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('weighbridge-exception-approval', [
                            'title' => 'Weighbridge Exception — ' . $record->exception_number,
                            'metadata' => ['weighbridge_exception_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (WeighbridgeException $record) => $record->currentApproval() !== null)
                    ->url(fn (WeighbridgeException $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
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
            'index' => Pages\ListWeighbridgeExceptions::route('/'),
            'create' => Pages\CreateWeighbridgeException::route('/create'),
            'edit' => Pages\EditWeighbridgeException::route('/{record}/edit'),
        ];
    }
}
