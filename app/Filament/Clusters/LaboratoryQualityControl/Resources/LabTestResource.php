<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource\Pages;
use App\Models\LabEquipment;
use App\Models\LabTest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Laboratory Tests" — records measured vs expected values and computes
 * Pass/Fail/Conditional via LabTest::computeResult(). Overdue-calibration
 * equipment cannot be selected for a new test (real check against
 * LabEquipment::isCalibrationOverdue(), not a fabricated rule).
 */
class LabTestResource extends Resource
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

    protected static ?string $model = LabTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Laboratory Tests';

    protected static ?int $navigationSort = 3;

    public static function getResultColor(?string $state): string
    {
        return match ($state) {
            'Pass' => 'success',
            'Fail' => 'danger',
            'Conditional' => 'warning',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['sample', 'testMethod', 'technician']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Test')->schema([
                    Forms\Components\TextInput::make('test_number')->label('Test #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('lab_sample_id')->label('Sample')
                        ->relationship('sample', 'sample_number')->searchable()->preload()->required(),
                    Forms\Components\Select::make('test_method_id')->label('Test Method')
                        ->relationship('testMethod', 'method_name')->searchable()->preload(),
                    Forms\Components\Select::make('technician_id')->label('Technician')
                        ->relationship('technician', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('lab_equipment_id')->label('Equipment')
                        ->relationship('labEquipment', 'name')
                        ->options(fn () => LabEquipment::query()->get()->mapWithKeys(
                            fn (LabEquipment $e) => [$e->id => $e->name . ($e->isCalibrationOverdue() ? ' — CALIBRATION OVERDUE' : '')]
                        ))
                        ->rule(function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $equipment = LabEquipment::find($value);

                                if ($equipment?->isCalibrationOverdue()) {
                                    $fail("{$equipment->name}'s calibration is overdue — it cannot be used for a new test until recalibrated.");
                                }
                            };
                        })
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(array_combine(LabTest::STATUSES, LabTest::STATUSES))->default('Pending')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Timing')->schema([
                    Forms\Components\DateTimePicker::make('start_time')->default(now()),
                    Forms\Components\DateTimePicker::make('end_time'),
                ])->columns(2),

                Forms\Components\Section::make('Values')->schema([
                    Forms\Components\KeyValue::make('measured_values')->label('Measured Values')->columnSpanFull(),
                    Forms\Components\KeyValue::make('expected_values')->label('Expected Values')->columnSpanFull(),
                    Forms\Components\TextInput::make('tolerance')->numeric(),
                    Forms\Components\Select::make('result')->options(array_combine(LabTest::RESULTS, LabTest::RESULTS)),
                ])->columns(2),

                Forms\Components\Section::make('Notes')->schema([
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('test_number')->label('Test #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sample.sample_number')->label('Sample')->placeholder('—'),
                Tables\Columns\TextColumn::make('testMethod.method_name')->label('Method')->placeholder('—'),
                Tables\Columns\TextColumn::make('technician.full_name')->label('Technician')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('result')->badge()->color(fn (?string $state): string => self::getResultColor($state))->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LabTest::STATUSES, LabTest::STATUSES)),
                Tables\Filters\SelectFilter::make('result')->options(array_combine(LabTest::RESULTS, LabTest::RESULTS)),
            ])
            ->actions([
                Tables\Actions\Action::make('completeTest')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LabTest $record) => $record->status !== 'Completed')
                    ->requiresConfirmation()
                    ->action(function (LabTest $record) {
                        $record->complete();
                        Notification::make()->title("Test completed — result: {$record->fresh()->result}")->success()->send();
                    }),
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
        return ['test_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabTests::route('/'),
            'create' => Pages\CreateLabTest::route('/create'),
            'edit' => Pages\EditLabTest::route('/{record}/edit'),
        ];
    }
}
