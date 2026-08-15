<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource\Pages;
use App\Models\BlastEnvironmentalReading;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlastEnvironmentalReadingResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = BlastEnvironmentalReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Environmental Monitoring';

    protected static ?int $navigationSort = 10;

    public static function getComplianceStatusOptions(): array
    {
        return array_combine(BlastEnvironmentalReading::COMPLIANCE_STATUSES, BlastEnvironmentalReading::COMPLIANCE_STATUSES);
    }

    public static function getComplianceStatusColor(string $state): string
    {
        return match ($state) {
            'Compliant' => 'success',
            'Under Review' => 'warning',
            'Exceeded' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Environmental Reading')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('ground_vibration')->label('Ground Vibration (PPV)')->numeric()->suffix('mm/s'),
                    Forms\Components\TextInput::make('air_overpressure')->numeric()->suffix('dB(L)'),
                    Forms\Components\TextInput::make('dust_level')->numeric()->suffix('µg/m³'),
                    Forms\Components\TextInput::make('noise_level')->numeric()->suffix('dB(A)'),
                    Forms\Components\TextInput::make('weather_conditions'),
                    Forms\Components\TextInput::make('monitoring_equipment'),
                    Forms\Components\DateTimePicker::make('recorded_at')->default(now()),
                    Forms\Components\Select::make('compliance_status')->options(self::getComplianceStatusOptions())->default('Compliant')->required(),
                    Forms\Components\Textarea::make('nearby_complaints')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ground_vibration')->label('Vibration')->numeric()->suffix(' mm/s')->placeholder('—'),
                Tables\Columns\TextColumn::make('air_overpressure')->label('Overpressure')->numeric()->suffix(' dB(L)')->placeholder('—'),
                Tables\Columns\TextColumn::make('dust_level')->numeric()->suffix(' µg/m³')->placeholder('—'),
                Tables\Columns\TextColumn::make('noise_level')->numeric()->suffix(' dB(A)')->placeholder('—'),
                Tables\Columns\TextColumn::make('recorded_at')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('compliance_status')->badge()->color(fn (string $state): string => self::getComplianceStatusColor($state)),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('compliance_status')->options(self::getComplianceStatusOptions()),
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
            'index' => Pages\ListBlastEnvironmentalReadings::route('/'),
            'create' => Pages\CreateBlastEnvironmentalReading::route('/create'),
            'edit' => Pages\EditBlastEnvironmentalReading::route('/{record}/edit'),
        ];
    }
}
