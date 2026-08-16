<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingInstructionResource\Pages;
use App\Models\LoadingInstruction;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LoadingInstructionResource extends Resource
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

    protected static ?string $model = LoadingInstruction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Loading Instructions';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Completed' => 'success',
            'Loading' => 'info',
            'Accepted' => 'primary',
            'Waiting' => 'warning',
            'Paused' => 'gray',
            'Rejected', 'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('loading_number')->label('Loading #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dispatchOrder.dispatch_number')->label('Dispatch #')->placeholder('—'),
                Tables\Columns\TextColumn::make('dispatchOrder.vehicle_registration')->label('Truck')->placeholder('—'),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('loadingBay.name')->label('Loading Bay')->placeholder('—'),
                Tables\Columns\TextColumn::make('loaderOperator.full_name')->label('Loader Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('target_net_weight')->label('Target')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LoadingInstruction::STATUSES, LoadingInstruction::STATUSES)),
            ])
            ->actions([
                Tables\Actions\Action::make('viewQr')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->modalHeading(fn (LoadingInstruction $record) => 'QR Code — ' . $record->loading_number)
                    ->modalContent(fn (LoadingInstruction $record) => new HtmlString('<div class="flex justify-center p-4">' . ($record->qr_code ?? '') . '</div>'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('accept')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->visible(fn (LoadingInstruction $record) => $record->status === 'Waiting')
                    ->action(function (LoadingInstruction $record) {
                        $record->accept();
                        Notification::make()->title('Job accepted')->success()->send();
                    }),
                Tables\Actions\Action::make('startLoading')
                    ->label('Start Loading')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (LoadingInstruction $record) => $record->status === 'Accepted')
                    ->action(function (LoadingInstruction $record) {
                        $record->startLoading();
                        Notification::make()->title('Loading started')->success()->send();
                    }),
                Tables\Actions\Action::make('pause')
                    ->icon('heroicon-o-pause')
                    ->color('gray')
                    ->visible(fn (LoadingInstruction $record) => $record->status === 'Loading')
                    ->action(function (LoadingInstruction $record) {
                        $record->pause();
                        Notification::make()->title('Loading paused')->success()->send();
                    }),
                Tables\Actions\Action::make('resume')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (LoadingInstruction $record) => $record->status === 'Paused')
                    ->action(function (LoadingInstruction $record) {
                        $record->resume();
                        Notification::make()->title('Loading resumed')->success()->send();
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Complete Loading')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LoadingInstruction $record) => in_array($record->status, ['Loading', 'Paused']))
                    ->form([
                        Forms\Components\TextInput::make('estimated_quantity_loaded')->label('Estimated Quantity Loaded')->numeric()->suffix('t'),
                    ])
                    ->action(function (LoadingInstruction $record, array $data) {
                        $record->complete($data['estimated_quantity_loaded'] ?? null);
                        Notification::make()->title('Loading completed — truck moved to Awaiting Gross Weight')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LoadingInstruction $record) => in_array($record->status, ['Waiting', 'Accepted']))
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (LoadingInstruction $record, array $data) {
                        $record->reject($data['reason']);
                        Notification::make()->title('Job rejected')->danger()->send();
                    }),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['dispatchOrder', 'stockpile', 'loadingBay', 'loaderOperator']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoadingInstructions::route('/'),
        ];
    }
}
