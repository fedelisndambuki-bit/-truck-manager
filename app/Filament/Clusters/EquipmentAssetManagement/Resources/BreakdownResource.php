<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource\Pages;
use App\Models\Breakdown;
use App\Models\Equipment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BreakdownResource extends Resource
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

    protected static ?string $model = Breakdown::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Breakdowns';

    protected static ?int $navigationSort = 10;

    public static function getSeverityOptions(): array
    {
        return array_combine(Breakdown::SEVERITIES, Breakdown::SEVERITIES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(Breakdown::STATUSES, Breakdown::STATUSES);
    }

    public static function getSeverityColor(string $state): string
    {
        return match ($state) {
            'Low' => 'gray', 'Medium' => 'info', 'High' => 'warning', 'Critical' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Breakdown Report')->schema([
                    Forms\Components\TextInput::make('breakdown_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('gps_coordinates'),
                    Forms\Components\DateTimePicker::make('occurred_at')->default(now())->required(),
                    Forms\Components\Select::make('severity')->options(self::getSeverityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Reported')->required()->disabled()->dehydrated(fn (string $context) => $context === 'edit'),
                ])->columns(3),

                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\Textarea::make('root_cause')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photos')->image()->multiple()->disk('public')->directory('breakdowns')->columnSpanFull(),
                    Forms\Components\FileUpload::make('videos')->multiple()->disk('public')->directory('breakdowns-videos')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('breakdown_number')->label('No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('severity')->badge()->color(fn (string $state): string => self::getSeverityColor($state)),
                Tables\Columns\TextColumn::make('workOrder.technician.full_name')->label('Assigned To')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'Reported' => 'danger', 'Assigned' => 'warning', 'Resolved' => 'success', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')->options(self::getSeverityOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreakdowns::route('/'),
            'create' => Pages\CreateBreakdown::route('/create'),
            'edit' => Pages\EditBreakdown::route('/{record}/edit'),
        ];
    }
}
