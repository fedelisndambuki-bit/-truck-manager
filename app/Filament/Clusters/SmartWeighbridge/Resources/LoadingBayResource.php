<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource\Pages;
use App\Models\LoadingBay;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoadingBayResource extends Resource
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

    protected static ?string $model = LoadingBay::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Loading Bays';

    protected static ?int $navigationSort = 6;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Available' => 'success',
            'Busy' => 'warning',
            'Maintenance' => 'gray',
            'Closed' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Loading Bay Details')->schema([
                    Forms\Components\TextInput::make('bay_code')->label('Bay Number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->label('Bay Name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')
                        ->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('stockpile_id')->label('Assigned Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('loader_equipment_id')->label('Assigned Loader')
                        ->relationship('loaderEquipment', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('supervisor_id')->label('Supervisor')
                        ->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('max_queue')->label('Maximum Queue')->numeric()->default(5),
                    Forms\Components\Select::make('status')->options(array_combine(LoadingBay::STATUSES, LoadingBay::STATUSES))->default('Available')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bay_code')->label('Bay #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Assigned Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('loaderEquipment.name')->label('Assigned Loader')->placeholder('—'),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('currentTruck')
                    ->label('Current Truck')
                    ->state(fn (LoadingBay $record) => $record->currentTruck()?->vehicle_registration ?? '—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LoadingBay::STATUSES, LoadingBay::STATUSES)),
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
            'index' => Pages\ListLoadingBays::route('/'),
            'create' => Pages\CreateLoadingBay::route('/create'),
            'edit' => Pages\EditLoadingBay::route('/{record}/edit'),
        ];
    }
}
