<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CorrectiveActionResource\Pages;
use App\Models\CorrectiveAction;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CorrectiveActionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = CorrectiveAction::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Corrective Actions';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 90;

    public static function getPriorityOptions(): array
    {
        return ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical'];
    }

    public static function getStatusOptions(): array
    {
        return ['Open' => 'Open', 'In Progress' => 'In Progress', 'Completed' => 'Completed', 'Verified' => 'Verified', 'Closed' => 'Closed'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Corrective Action')->schema([
                    Forms\Components\TextInput::make('action_number')->label('Action #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Textarea::make('description')->required()->columnSpanFull()->rows(2),
                    Forms\Components\Select::make('responsible_staff_id')->label('Responsible Person')->relationship('responsibleStaff', 'full_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\DatePicker::make('due_date')->required(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Open')->required(),
                    Forms\Components\TextInput::make('progress_percent')->label('Progress %')->numeric()->minValue(0)->maxValue(100)->default(0)->suffix('%'),
                ])->columns(3),

                Forms\Components\Section::make('Linked To')->schema([
                    Forms\Components\Select::make('safety_incident_id')->label('Incident')->relationship('safetyIncident', 'incident_number')->searchable()->preload(),
                    Forms\Components\Select::make('hazard_id')->label('Hazard')->relationship('hazard', 'hazard_number')->searchable()->preload(),
                    Forms\Components\Select::make('near_miss_id')->label('Near Miss')->relationship('nearMiss', 'near_miss_number')->searchable()->preload(),
                    Forms\Components\Select::make('safety_inspection_id')->label('Inspection')->relationship('safetyInspection', 'inspection_number')->searchable()->preload(),
                ])->columns(4)->collapsible(),

                Forms\Components\Section::make('Evidence & Verification')->schema([
                    Forms\Components\Textarea::make('evidence')->columnSpanFull()->rows(2),
                    Forms\Components\FileUpload::make('evidence_file')->label('Evidence File')->disk('public')->directory('corrective-action-evidence')->openable()->downloadable(),
                ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action_number')->label('Action #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(40),
                Tables\Columns\TextColumn::make('responsibleStaff.full_name')->label('Responsible')->toggleable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', 'Critical' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->weight(fn ($record) => $record->isOverdue() ? 'bold' : null),
                Tables\Columns\TextColumn::make('progress_percent')->label('Progress')->suffix('%'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Open' => 'danger', 'In Progress' => 'warning', 'Completed' => 'info',
                    'Verified' => 'info', 'Closed' => 'success', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn ($query) => $query->whereDate('due_date', '<', now())->whereNotIn('status', ['Completed', 'Verified', 'Closed'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('due_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCorrectiveActions::route('/'),
            'create' => Pages\CreateCorrectiveAction::route('/create'),
            'edit' => Pages\EditCorrectiveAction::route('/{record}/edit'),
        ];
    }
}
