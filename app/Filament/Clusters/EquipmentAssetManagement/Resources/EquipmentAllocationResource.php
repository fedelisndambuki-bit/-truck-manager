<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAllocationResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentAllocationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = EquipmentAllocation::class;

    protected static ?string $slug = 'equipment-assignments';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Equipment Assignments';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->label('Equipment')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required()->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Assignment')->schema([
                    Forms\Components\Select::make('operator_employee_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('supervisor_employee_id')->label('Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('department'),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('shift')->placeholder('e.g. Day Shift'),
                    Forms\Components\DatePicker::make('allocated_from')->label('Start Date')->default(now()),
                    Forms\Components\DatePicker::make('allocated_to')->label('End Date'),
                    Forms\Components\DatePicker::make('actual_return_date'),
                ])->columns(3),

                Forms\Components\Section::make('Quarry Operations')->schema([
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry / Site')->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('production_area'),
                    Forms\Components\Select::make('crusher_id')->label('Crusher')->relationship('crusher', 'crusher_code')->searchable()->preload(),
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')->relationship('stockpile', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('loading_bay'),
                ])->columns(3),

                Forms\Components\Section::make('Approval')->schema([
                    Forms\Components\Select::make('approval_status')->options([
                        'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    ])->default('Pending')->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['equipment', 'operator', 'extractionSite']))
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                Tables\Columns\TextColumn::make('shift')->placeholder('—'),
                Tables\Columns\TextColumn::make('allocated_from')->date()->label('Start'),
                Tables\Columns\TextColumn::make('allocated_to')->date()->label('End')->placeholder('—'),
                Tables\Columns\TextColumn::make('approval_status')->badge()->color(fn (string $state): string => match ($state) {
                    'Approved' => 'success', 'Rejected' => 'danger', default => 'warning',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('allocated_from', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentAllocations::route('/'),
            'create' => Pages\CreateEquipmentAllocation::route('/create'),
            'edit' => Pages\EditEquipmentAllocation::route('/{record}/edit'),
        ];
    }
}
