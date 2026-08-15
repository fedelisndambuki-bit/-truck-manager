<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\CalibrationResource\Pages;
use App\Models\Calibration;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Calibration" — each new record's created hook resyncs the parent
 * LabEquipment's calibration_due_date/status (see Calibration::booted()),
 * which is what LabTestResource's overdue-equipment check reads from.
 */
class CalibrationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = LaboratoryQualityControl::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $model = Calibration::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Calibration';

    protected static ?int $navigationSort = 12;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Valid' => 'success',
            'Overdue' => 'warning',
            'Expired' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Calibration')->schema([
                    Forms\Components\TextInput::make('calibration_number')->label('Calibration #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('lab_equipment_id')->label('Equipment')
                        ->relationship('labEquipment', 'name')->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('calibration_date')->default(now())->required(),
                    Forms\Components\DatePicker::make('due_date')->required(),
                    Forms\Components\Select::make('performed_by_id')->label('Performed By')
                        ->relationship('performedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('certificate_path')->label('Certificate Reference'),
                    Forms\Components\Select::make('status')->options(array_combine(Calibration::STATUSES, Calibration::STATUSES))->default('Valid')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('calibration_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('calibration_number')->label('Calibration #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('labEquipment.name')->label('Equipment')->placeholder('—'),
                Tables\Columns\TextColumn::make('calibration_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('performedBy.full_name')->label('Performed By')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(Calibration::STATUSES, Calibration::STATUSES)),
                Tables\Filters\SelectFilter::make('lab_equipment_id')->label('Equipment')->relationship('labEquipment', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['calibration_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalibrations::route('/'),
            'create' => Pages\CreateCalibration::route('/create'),
            'edit' => Pages\EditCalibration::route('/{record}/edit'),
        ];
    }
}
