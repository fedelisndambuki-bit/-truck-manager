<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailySiteDiaryResource\Pages;
use App\Models\DailySiteDiary;
use App\Models\Site;
use App\Services\DailySiteDiaryAutoPopulateService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DailySiteDiaryResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = DailySiteDiary::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Daily Entries';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Daily Site Diary';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('General Info')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Placeholder::make('diary_number_display')
                            ->label('Diary Number')
                            ->content(fn (?DailySiteDiary $record) => $record?->diary_number ?? 'Auto-generated on save'),
                        Forms\Components\Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('site_id')
                            ->label('Site')
                            ->relationship('site', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set, Get $get) => static::autoPopulate($state, $get('date'), $set)),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set, Get $get) => static::autoPopulate($get('site_id'), $state, $set)),
                        Forms\Components\TextInput::make('phase')->label('Construction Phase'),
                        Forms\Components\Select::make('shift')
                            ->options(['Day' => 'Day', 'Night' => 'Night', 'Overtime' => 'Overtime'])
                            ->default('Day')
                            ->required(),
                        Forms\Components\Select::make('logged_by_id')
                            ->label('Supervisor')
                            ->relationship('loggedBy', 'full_name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Weather')
                    ->icon('heroicon-o-cloud')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('fetchWeather')
                                ->label('Fetch Weather')
                                ->icon('heroicon-o-arrow-path')
                                ->color('info')
                                ->action(fn (Get $get, Set $set) => static::fetchWeatherAction($get('site_id'), $set)),
                        ]),
                        Forms\Components\TextInput::make('conditions'),
                        Forms\Components\TextInput::make('temperature_high')->label('Temp High (°C)')->numeric(),
                        Forms\Components\TextInput::make('temperature_low')->label('Temp Low (°C)')->numeric(),
                        Forms\Components\TextInput::make('rainfall_mm')->label('Rainfall (mm)')->numeric(),
                        Forms\Components\TextInput::make('wind_speed_kph')->label('Wind Speed (kph)')->numeric(),
                        Forms\Components\TextInput::make('humidity_pct')->label('Humidity (%)')->numeric(),
                        Forms\Components\Select::make('weather_impact')
                            ->options(['None' => 'None', 'Minor' => 'Minor', 'Moderate' => 'Moderate', 'Severe' => 'Severe']),
                        Forms\Components\Hidden::make('weather_source')->default('manual'),
                        Forms\Components\Textarea::make('weather')->label('Comments')->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Workforce')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\TextInput::make('workers_on_site')->label('Present')->numeric()->default(0),
                        Forms\Components\TextInput::make('workers_absent')->label('Absent')->numeric()->default(0),
                        Forms\Components\TextInput::make('workers_late')->label('Late')->numeric()->default(0),
                        Forms\Components\TextInput::make('workers_overtime')->label('Overtime')->numeric()->default(0),
                    ])->columns(4),

                Forms\Components\Wizard\Step::make('Equipment')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Forms\Components\TextInput::make('equipment_active_count')->label('Equipment Active')->numeric()->default(0),
                        Forms\Components\TextInput::make('equipment_idle_count')->label('Equipment Idle')->numeric()->default(0),
                        Forms\Components\TextInput::make('equipment_breakdown_count')->label('Breakdowns')->numeric()->default(0),
                        Forms\Components\TextInput::make('equipment_utilization_pct')->label('Utilization %')->numeric(),
                    ])->columns(4),

                Forms\Components\Wizard\Step::make('Materials')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make('material_deliveries_count')->label('Deliveries')->numeric()->default(0),
                        Forms\Components\TextInput::make('material_shortages_count')->label('Shortages')->numeric()->default(0),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Subcontractors')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('subcontractors_on_site_count')->label('Subcontractors on Site')->numeric()->default(0),
                        Forms\Components\Textarea::make('subcontractor_progress_notes')->label('Progress Notes')->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Activities')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Repeater::make('activities')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('task')->required(),
                                Forms\Components\TextInput::make('area'),
                                Forms\Components\TimePicker::make('start_time'),
                                Forms\Components\TimePicker::make('finish_time'),
                                Forms\Components\TextInput::make('completion_pct')->label('Completion %')->numeric()->default(0),
                                Forms\Components\Textarea::make('resources_used'),
                                Forms\Components\Textarea::make('comments'),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ]),

                Forms\Components\Wizard\Step::make('Delays')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Forms\Components\Repeater::make('delays')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'Weather' => 'Weather', 'Equipment' => 'Equipment', 'Material' => 'Material',
                                        'Labour' => 'Labour', 'Other' => 'Other',
                                    ])
                                    ->default('Other')
                                    ->required(),
                                Forms\Components\Textarea::make('reason')->required(),
                                Forms\Components\TextInput::make('duration_hours')->label('Duration (hrs)')->numeric()->default(0),
                                Forms\Components\Select::make('impact')
                                    ->options(['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical'])
                                    ->default('Low')
                                    ->required(),
                                Forms\Components\TextInput::make('responsible_party'),
                                Forms\Components\Textarea::make('recovery_plan'),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ]),

                Forms\Components\Wizard\Step::make('Visitors')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Repeater::make('diaryVisitors')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('visitor_name')->label('Visitor')->required(),
                                Forms\Components\TextInput::make('company'),
                                Forms\Components\TextInput::make('purpose'),
                                Forms\Components\TimePicker::make('check_in'),
                                Forms\Components\TimePicker::make('check_out'),
                                Forms\Components\Select::make('host_staff_id')
                                    ->label('Host')
                                    ->relationship('host', 'full_name')
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(0),
                    ]),
            ])->columnSpanFull()->skippable(),

            Forms\Components\Section::make('Site Photos')
                ->schema([
                    Forms\Components\Repeater::make('photos')
                        ->relationship('photos')
                        ->schema([
                            Forms\Components\Hidden::make('category')->default('Site Photos'),
                            Forms\Components\TextInput::make('title')->required(),
                            Forms\Components\Select::make('photo_category')
                                ->options([
                                    'Progress' => 'Progress', 'Equipment' => 'Equipment', 'Material' => 'Material',
                                    'Drone' => 'Drone', 'Incident' => 'Incident', 'Inspection' => 'Inspection',
                                    'Blueprint Markup' => 'Blueprint Markup',
                                ]),
                            Forms\Components\FileUpload::make('file_path')
                                ->label('Photo')
                                ->disk('public')
                                ->directory('diary-photos')
                                ->image()
                                ->openable(),
                            Forms\Components\TextInput::make('latitude')->numeric(),
                            Forms\Components\TextInput::make('longitude')->numeric(),
                            Forms\Components\DateTimePicker::make('taken_at'),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->defaultItems(0),
                ])
                ->collapsible()
                ->collapsed(),

            Forms\Components\Section::make('Supervisor Notes')
                ->schema([
                    Forms\Components\Textarea::make('general_observations'),
                    Forms\Components\Textarea::make('challenges'),
                    Forms\Components\Textarea::make('risks'),
                    Forms\Components\Textarea::make('recommendations'),
                    Forms\Components\Textarea::make('next_day_plan')->label('Next Day Plan'),
                    Forms\Components\Textarea::make('additional_comments'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    protected static function autoPopulate(?int $siteId, ?string $date, Set $set): void
    {
        if (! $siteId || ! $date) {
            return;
        }

        $service = app(DailySiteDiaryAutoPopulateService::class);

        $summaries = array_merge(
            $service->workforceSummary($siteId, $date),
            $service->equipmentSummary($siteId, $date),
            $service->materialSummary($siteId, $date),
            $service->subcontractorSummary($siteId, $date),
        );

        foreach ($summaries as $key => $value) {
            $set($key, $value);
        }

        $site = Site::find($siteId);

        if ($site) {
            $weather = $service->weather($site);

            if ($weather) {
                foreach ($weather as $key => $value) {
                    $set($key, $value);
                }
            }
        }
    }

    protected static function fetchWeatherAction(?int $siteId, Set $set): void
    {
        if (! $siteId) {
            Notification::make()->warning()->title('Select a site first')->send();

            return;
        }

        $site = Site::find($siteId);
        $weather = $site ? app(DailySiteDiaryAutoPopulateService::class)->weather($site) : null;

        if ($weather) {
            foreach ($weather as $key => $value) {
                $set($key, $value);
            }
            Notification::make()->success()->title('Weather updated')->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Could not fetch weather')
                ->body('Check that the site has GPS coordinates set, or enter weather manually.')
                ->send();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('diary_number')->label('Diary #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('loggedBy.full_name')->label('Supervisor'),
                Tables\Columns\TextColumn::make('conditions')->label('Weather'),
                Tables\Columns\TextColumn::make('workers_on_site')->label('Workers'),
                Tables\Columns\TextColumn::make('equipment_active_count')->label('Equipment'),
                Tables\Columns\TextColumn::make('material_deliveries_count')->label('Deliveries'),
                Tables\Columns\TextColumn::make('subcontractors_on_site_count')->label('Subs'),
                Tables\Columns\TextColumn::make('progress_pct')->label('Progress %')->suffix('%'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Submitted' => 'warning',
                        'Reviewed' => 'info',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Locked' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Reviewed' => 'Reviewed',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Locked' => 'Locked',
                ]),
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.approve'))
                        ->action(function ($records) {
                            $records->each->approve();
                            Notification::make()->success()->title('Diaries approved')->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailySiteDiaries::route('/'),
            'create' => Pages\CreateDailySiteDiary::route('/create'),
            'view' => Pages\ViewDailySiteDiary::route('/{record}'),
            'edit' => Pages\EditDailySiteDiary::route('/{record}/edit'),
        ];
    }
}
