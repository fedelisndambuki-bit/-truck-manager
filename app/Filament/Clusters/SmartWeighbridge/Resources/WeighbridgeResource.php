<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource\Pages;
use App\Models\Weighbridge;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WeighbridgeResource extends Resource
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

    protected static ?string $model = Weighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Weighbridge Operations';

    protected static ?int $navigationSort = 4;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Online' => 'success',
            'Maintenance' => 'warning',
            'Offline' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Weighbridge Details')->schema([
                    Forms\Components\TextInput::make('code')->label('Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')
                        ->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('capacity_tonnes')->label('Capacity')->numeric()->suffix('t'),
                    Forms\Components\Select::make('scale_type')->options(array_combine(Weighbridge::SCALE_TYPES, Weighbridge::SCALE_TYPES))->default('Dual Direction')->required(),
                    Forms\Components\Select::make('status')->options(array_combine(Weighbridge::STATUSES, Weighbridge::STATUSES))->default('Online')->required(),
                    Forms\Components\DatePicker::make('calibration_due_date'),
                    Forms\Components\DatePicker::make('certification_expiry'),
                    Forms\Components\Select::make('current_operator_id')->label('Current Operator')
                        ->relationship('currentOperator', 'full_name')->searchable()->preload(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                Tables\Columns\TextColumn::make('scale_type')->label('Type'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('currentOperator.full_name')->label('Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_queue')
                    ->label('Current Queue')
                    ->state(fn (Weighbridge $record) => $record->currentQueueCount()),
                Tables\Columns\TextColumn::make('avg_processing_time')
                    ->label('Avg. Processing Time')
                    ->state(fn (Weighbridge $record) => $record->averageProcessingTimeMinutes() . ' min'),
                Tables\Columns\TextColumn::make('calibration_due_date')->date()->placeholder('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(Weighbridge::STATUSES, Weighbridge::STATUSES)),
            ])
            ->actions([
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
            'index' => Pages\ListWeighbridges::route('/'),
            'create' => Pages\CreateWeighbridge::route('/create'),
            'edit' => Pages\EditWeighbridge::route('/{record}/edit'),
        ];
    }
}
