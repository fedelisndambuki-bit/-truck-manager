<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverPodResource\Pages;
use App\Models\DriverPod;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use App\Traits\HasModuleAccess;

class DriverPodResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'pod-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = DriverPod::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Driver Submissions';

    protected static ?string $navigationLabel = 'POD Reviews';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('POD Details')
                    ->schema([
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? 'Unknown Driver')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('trip_id')
                            ->relationship('trip', 'id')
                            ->label('Trip ID')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('receiver_name'),
                        Forms\Components\DateTimePicker::make('delivered_at'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'failed' => 'Failed',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Decline Reason')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Location & Notes')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_details'),
                        Forms\Components\TextInput::make('dropoff_details'),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Attachments')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('POD Document')
                            ->disk('public')
                            ->directory('driver/pods')
                            ->image()
                            ->columnSpanFull()
                            ->helperText('Click the magnifying glass icon above to open the zoomable preview.')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('zoom')
                                    ->label('Zoom Image')
                                    ->icon('heroicon-m-magnifying-glass-plus')
                                    ->modalContent(fn ($record) => view('filament.resources.driver-pod-resource.preview', ['record' => $record]))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Close')
                                    ->modalWidth('7xl')
                                    ->visible(fn ($record) => $record && $record->image_path)
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('POD Document')
                    ->disk('public')
                    ->height(60)
                    ->square()
                    ->extraImgAttributes(['class' => 'cursor-pointer hover:scale-105 transition-transform'])
                    ->action(function (DriverPod $record, $livewire) {
                        $livewire->mountTableAction('image_preview', $record);
                    }),

                Tables\Columns\TextColumn::make('driver.user.name')
                    ->label('Driver')
                    ->description(fn ($record) => $record->driver?->license_number ?? 'No License')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trip.id')
                    ->label('Trip Details')
                    ->formatStateUsing(fn ($state, $record) => "Trip #{$state}")
                    ->description(fn ($record) => "{$record->pickup_details} → {$record->dropoff_details}")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('receiver_name')
                    ->label('Receiver')
                    ->description(fn ($record) => $record->delivered_at?->format('M d, Y H:i') ?? 'N/A')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('failure_reason')
                    ->label('Decline Reason')
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('image_preview')
                    ->modalHeading('Proof of Delivery Preview')
                    ->modalContent(fn (DriverPod $record) => view('filament.resources.driver-pod-resource.preview', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    ->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\Action::make('approve')
                    ->label('Approve POD')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve POD Submission')
                    ->modalDescription('Are you sure you want to approve this delivery document?')
                    ->action(function (DriverPod $record): void {
                        $record->update([
                            'status' => 'approved',
                            'failure_reason' => null,
                        ]);
                    })
                    ->visible(fn ($record) => $record->status !== 'approved'),

                Tables\Actions\Action::make('fail')
                    ->label('Decline POD')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Reason for Declining')
                            ->placeholder('e.g. Image blurry, missing signature...')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (DriverPod $record, array $data): void {
                        $record->update([
                            'status' => 'failed',
                            'failure_reason' => $data['failure_reason'],
                        ]);
                    })
                    ->visible(fn ($record) => $record->status !== 'failed'),
                
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-m-trash'),
            ])
            ->actionsColumnLabel('Action')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverPods::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            $query->whereHas('driver', fn (Builder $q) => $q->where('company_id', $user->company_id));
        }

        return $query;
    }
}
