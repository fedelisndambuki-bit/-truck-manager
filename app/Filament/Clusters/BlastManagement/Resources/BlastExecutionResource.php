<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastExecutionResource\Pages;
use App\Models\BlastExecution;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlastExecutionResource extends Resource
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

    protected static ?string $model = BlastExecution::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Blast Execution';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['blastPlan', 'responsibleEngineer', 'shotFirer']);
    }

    public static function getOutcomeOptions(): array
    {
        return array_combine(BlastExecution::OUTCOMES, BlastExecution::OUTCOMES);
    }

    public static function getOutcomeColor(string $state): string
    {
        return match ($state) {
            'Successful' => 'success',
            'Partial' => 'warning',
            'Misfire', 'Delayed' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Execution Details')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('actual_date')->default(now()),
                    Forms\Components\TimePicker::make('actual_time'),
                    Forms\Components\Select::make('responsible_engineer_id')->label('Responsible Engineer')->relationship('responsibleEngineer', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('shot_firer_id')->label('Shot Firer')->relationship('shotFirer', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('weather_conditions'),
                    Forms\Components\TextInput::make('explosives_used')->numeric()->suffix('kg'),
                    Forms\Components\TextInput::make('detonators_used')->numeric(),
                    Forms\Components\TextInput::make('blast_duration_seconds')->label('Blast Duration')->numeric()->suffix('sec'),
                    Forms\Components\Select::make('blast_outcome')->options(self::getOutcomeOptions()),
                    Forms\Components\Select::make('safety_incident_id')->label('Linked Safety Incident')->relationship('safetyIncident', 'incident_number')->searchable()->preload()
                        ->helperText('Optional — link a real Safety Incident if fly rock or a misfire occurred.'),
                    Forms\Components\Textarea::make('initiation_sequence')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photo_path')->label('Photo')->image()->disk('public')->directory('blast-execution'),
                    Forms\Components\FileUpload::make('video_path')->label('Video')->disk('public')->directory('blast-execution'),
                    Forms\Components\FileUpload::make('attachment_path')->label('Attachment')->disk('public')->directory('blast-execution'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('actual_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('responsibleEngineer.full_name')->label('Engineer')->placeholder('—'),
                Tables\Columns\TextColumn::make('shotFirer.full_name')->label('Shot Firer')->placeholder('—'),
                Tables\Columns\TextColumn::make('explosives_used')->numeric()->suffix(' kg')->placeholder('—'),
                Tables\Columns\TextColumn::make('blast_outcome')->badge()->color(fn (?string $state): string => $state ? self::getOutcomeColor($state) : 'gray')->placeholder('—'),
            ])
            ->defaultSort('actual_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('blast_outcome')->options(self::getOutcomeOptions()),
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
            'index' => Pages\ListBlastExecutions::route('/'),
            'create' => Pages\CreateBlastExecution::route('/create'),
            'edit' => Pages\EditBlastExecution::route('/{record}/edit'),
        ];
    }
}
