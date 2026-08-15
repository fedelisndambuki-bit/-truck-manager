<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialConsumptionResource\Pages;
use App\Models\MaterialConsumption;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialConsumptionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.issue';
    }

    protected static ?string $model = MaterialConsumption::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Material Consumption';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Consumption Entry')->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Drawn From Warehouse')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('allocation_id')
                        ->label('Against Allocation')
                        ->relationship('allocation', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->material?->name . ' — ' . $record->project?->name)
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('activity')->label('Activity'),
                    Forms\Components\TextInput::make('quantity_used')->numeric()->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\Select::make('issued_by_id')
                        ->label('Issued By')
                        ->relationship('issuedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('received_by_id')
                        ->label('Received By')
                        ->relationship('receivedBy', 'full_name')
                        ->searchable()->preload(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('activity')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('quantity_used')->label('Quantity')->numeric(2),
                Tables\Columns\TextColumn::make('cost')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('issuedBy.full_name')->label('Issued By')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('material_id')->label('Material')->relationship('material', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialConsumptions::route('/'),
            'create' => Pages\CreateMaterialConsumption::route('/create'),
            'edit' => Pages\EditMaterialConsumption::route('/{record}/edit'),
        ];
    }
}
