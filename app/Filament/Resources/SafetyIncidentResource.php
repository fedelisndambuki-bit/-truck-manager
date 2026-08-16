<?php

namespace App\Filament\Resources;

use App\Enums\CompanyIndustry;
use App\Filament\Resources\SafetyIncidentResource\Pages;
use App\Models\SafetyIncident;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SafetyIncidentResource extends Resource
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

    protected static ?string $model = SafetyIncident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Incidents';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 20;

    public static function getEmploymentTypeOptions(): array
    {
        return ['Staff' => 'Staff', 'Subcontractor' => 'Subcontractor', 'Visitor' => 'Visitor', 'Public' => 'Public'];
    }

    public static function getCategoryOptions(): array
    {
        return array_combine(SafetyIncident::CATEGORIES, SafetyIncident::CATEGORIES);
    }

    public static function getSeverityOptions(): array
    {
        return ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical'];
    }

    public static function getInjuryClassificationOptions(): array
    {
        return [
            'No Injury' => 'No Injury',
            'First Aid Case' => 'First Aid Case',
            'Medical Treatment Case' => 'Medical Treatment Case',
            'Lost Time Injury' => 'Lost Time Injury',
            'Property Damage Only' => 'Property Damage Only',
            'Fatality' => 'Fatality',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Draft' => 'Draft', 'Reported' => 'Reported', 'Under Investigation' => 'Under Investigation',
            'Corrective Action' => 'Corrective Action', 'Closed' => 'Closed',
        ];
    }

    public static function isQuarryCompany(): bool
    {
        return Auth::user()?->company?->industry === CompanyIndustry::Quarry->value;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['site', 'extractionSite', 'reportedBy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Incident Details')->schema([
                    Forms\Components\TextInput::make('incident_number')->label('Incident #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                    Forms\Components\TimePicker::make('time')->seconds(false),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload()
                        ->visible(fn () => ! self::isQuarryCompany())
                        ->required(fn () => ! self::isQuarryCompany()),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry Site')->relationship('extractionSite', 'name')->searchable()->preload()
                        ->visible(fn () => self::isQuarryCompany())
                        ->required(fn () => self::isQuarryCompany()),
                    Forms\Components\TextInput::make('location')->label('Specific Location')->placeholder('e.g. Block A, 3rd Floor'),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')->relationship('reportedBy', 'full_name')->searchable()->preload(),
                ])->columns(3),

                Forms\Components\Section::make('Person Involved')->schema([
                    Forms\Components\TextInput::make('injured_person_name')->label('Injured / Involved Person'),
                    Forms\Components\Select::make('employment_type')->options(self::getEmploymentTypeOptions()),
                    Forms\Components\Select::make('subcontractor_id')->label('Subcontractor')->relationship('subcontractor', 'company_name')->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('employment_type') === 'Subcontractor'),
                    Forms\Components\Textarea::make('witnesses')->label('Witnesses')->placeholder('Names / contact details')->columnSpanFull(),
                ])->columns(3),

                Forms\Components\Section::make('Classification')->schema([
                    Forms\Components\Select::make('incident_category')->label('Category')->options(self::getCategoryOptions())->searchable(),
                    Forms\Components\Select::make('severity')->options(self::getSeverityOptions())->default('Low')->required(),
                    Forms\Components\Select::make('injury_classification')->options(self::getInjuryClassificationOptions()),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Reported')->required(),
                ])->columns(4),

                Forms\Components\Section::make('What Happened')->schema([
                    Forms\Components\Textarea::make('description')->label('Description')->columnSpanFull()->rows(3),
                    Forms\Components\Textarea::make('immediate_response')->label('Immediate Response Taken')->columnSpanFull()->rows(2),
                ]),

                Forms\Components\Section::make('Medical Treatment')->schema([
                    Forms\Components\Select::make('medical_treatment')->options([
                        'None' => 'None', 'First Aid' => 'First Aid', 'Medical Treatment' => 'Medical Treatment', 'Hospitalization' => 'Hospitalization',
                    ])->default('None')->live(),
                    Forms\Components\TextInput::make('hospital_name')->label('Hospital')
                        ->visible(fn (Forms\Get $get) => in_array($get('medical_treatment'), ['Medical Treatment', 'Hospitalization'])),
                ])->columns(2),

                Forms\Components\Section::make('Investigation')->schema([
                    Forms\Components\Select::make('investigation_status')->options([
                        'Not Started' => 'Not Started', 'In Progress' => 'In Progress', 'Completed' => 'Completed',
                    ])->default('Not Started'),
                    Forms\Components\Textarea::make('root_cause')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_actions_summary')->label('Corrective Actions Summary')->columnSpanFull()
                        ->helperText('Detailed corrective actions with owners/due dates are tracked under Corrective Actions.'),
                    Forms\Components\Textarea::make('final_outcome')->columnSpanFull(),
                ])->columns(1)->collapsible(),

                Forms\Components\Section::make('Photos, Videos & Attachments')->schema([
                    Forms\Components\Repeater::make('attachments')->relationship('attachments')->schema([
                        Forms\Components\Select::make('type')->options(['Photo' => 'Photo', 'Video' => 'Video', 'Document' => 'Document'])->default('Photo')->required(),
                        Forms\Components\TextInput::make('caption'),
                        Forms\Components\FileUpload::make('file_path')->label('File')->disk('public')->directory('incident-attachments')->openable()->downloadable()->required(),
                    ])->columns(3)->defaultItems(0)->collapsible()->columnSpanFull(),
                ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incident_number')->label('Incident #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')
                    ->state(fn (SafetyIncident $record): ?string => $record->site?->name ?? $record->extractionSite?->name)
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('incident_category')->label('Category')->toggleable(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Low' => 'success', 'Medium' => 'warning', 'High', 'Critical' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('injury_classification')->label('Injury Type')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray', 'Reported' => 'danger', 'Under Investigation' => 'warning',
                        'Corrective Action' => 'info', 'Closed' => 'success', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reportedBy.full_name')->label('Reported By')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('severity')->options(self::getSeverityOptions()),
                Tables\Filters\SelectFilter::make('incident_category')->label('Category')->options(self::getCategoryOptions()),
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
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
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSafetyIncidents::route('/'),
            'create' => Pages\CreateSafetyIncident::route('/create'),
            'edit' => Pages\EditSafetyIncident::route('/{record}/edit'),
        ];
    }
}
