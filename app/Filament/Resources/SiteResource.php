<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Attendance;
use App\Models\EquipmentAllocation;
use App\Models\MaterialDelivery;
use App\Models\Project;
use App\Models\SafetyIncident;
use App\Models\Site;
use App\Models\SiteTeamMember;
use App\Models\Staff;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SiteResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 3;

    public static function getPhaseOptions(): array
    {
        return [
            'Foundation' => 'Foundation',
            'Earthworks' => 'Earthworks',
            'Structural Works' => 'Structural Works',
            'Roofing' => 'Roofing',
            'Electrical' => 'Electrical',
            'Mechanical' => 'Mechanical',
            'Finishing' => 'Finishing',
            'Handover' => 'Handover',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Planning' => 'Planning',
            'Active' => 'Active',
            'Suspended' => 'Suspended',
            'Completed' => 'Completed',
            'Closed' => 'Closed',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            'Low' => 'Low',
            'Medium' => 'Medium',
            'High' => 'High',
            'Critical' => 'Critical',
        ];
    }

    public static function getRiskLevelOptions(): array
    {
        return [
            'Low' => 'Low',
            'Medium' => 'Medium',
            'High' => 'High',
            'Critical' => 'Critical',
        ];
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Planning' => 'gray',
            'Active' => 'success',
            'Suspended' => 'danger',
            'Completed' => 'info',
            'Closed' => 'gray',
            default => 'gray',
        };
    }

    public static function getPriorityColor(string $state): string
    {
        return match ($state) {
            'Low' => 'success',
            'Medium' => 'info',
            'High' => 'warning',
            'Critical' => 'danger',
            default => 'gray',
        };
    }

    public static function getTeamSlots(): array
    {
        return [
            'Site Manager', 'Assistant Site Manager', 'Site Engineer', 'Supervisor',
            'Safety Officer', 'Store Manager', 'Security Lead', 'Quantity Surveyor', 'Procurement Officer',
        ];
    }

    public static function basicInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->label('Site Name')->required(),
            Forms\Components\TextInput::make('site_code')->label('Site Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
            Forms\Components\Select::make('project_id')
                ->label('Parent Project')
                ->relationship('project', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('current_phase')->label('Construction Phase')->options(self::getPhaseOptions()),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium'),
            Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Planning')->required(),
        ];
    }

    public static function locationSchema(): array
    {
        return [
            Forms\Components\TextInput::make('country'),
            Forms\Components\TextInput::make('county'),
            Forms\Components\TextInput::make('city'),
            Forms\Components\TextInput::make('location')->label('Physical Address')->columnSpanFull(),
            Forms\Components\TextInput::make('nearest_landmark'),
            Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
            Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
            Forms\Components\TextInput::make('site_size')->numeric(),
            Forms\Components\Select::make('site_size_unit')->options(['Acres' => 'Acres', 'Hectares' => 'Hectares']),
            Forms\Components\TextInput::make('elevation')->numeric()->suffix('m'),
        ];
    }

    public static function teamSchema(): array
    {
        return collect(self::getTeamSlots())
            ->map(fn (string $slot) => Forms\Components\Select::make('team.' . str($slot)->slug())
                ->label($slot)
                ->options(fn () => Staff::query()->pluck('full_name', 'id'))
                ->searchable()
                ->preload()
                ->dehydrated(false))
            ->concat([Forms\Components\Textarea::make('emergency_contacts')->columnSpanFull()])
            ->toArray();
    }

    public static function getTeamFormStateForProject(Site $site): array
    {
        $team = [];

        foreach ($site->teamMembers as $member) {
            $team[(string) str($member->role_slot)->slug()] = $member->employee_id;
        }

        return ['team' => $team];
    }

    public static function syncTeamFromFormState(Site $site, array $team): void
    {
        foreach (self::getTeamSlots() as $slot) {
            $employeeId = $team[(string) str($slot)->slug()] ?? null;

            SiteTeamMember::query()
                ->where('site_id', $site->id)
                ->where('role_slot', $slot)
                ->delete();

            if ($employeeId) {
                SiteTeamMember::create([
                    'company_id' => $site->company_id,
                    'site_id' => $site->id,
                    'employee_id' => $employeeId,
                    'role_slot' => $slot,
                ]);
            }
        }
    }

    public static function operationsSchema(): array
    {
        return [
            Forms\Components\TextInput::make('working_days')->placeholder('e.g. Mon-Sat'),
            Forms\Components\TextInput::make('working_hours')->placeholder('e.g. 07:00-18:00'),
            Forms\Components\TextInput::make('shift_pattern')->placeholder('e.g. Single Shift'),
            Forms\Components\Toggle::make('night_shift_enabled')->label('Night Shift Enabled'),
            Forms\Components\TextInput::make('max_workforce')->numeric(),
            Forms\Components\TextInput::make('default_warehouse'),
            Forms\Components\TextInput::make('default_equipment_pool'),
            Forms\Components\Toggle::make('weather_monitoring_enabled')->label('Weather Monitoring'),
            Forms\Components\TextInput::make('internet_availability')->placeholder('e.g. Fibre / 4G / None'),
            Forms\Components\TextInput::make('power_source')->placeholder('e.g. Grid / Generator'),
            Forms\Components\TextInput::make('water_source')->placeholder('e.g. Borehole / Mains'),
        ];
    }

    public static function safetySchema(): array
    {
        return [
            Forms\Components\TextInput::make('emergency_assembly_point'),
            Forms\Components\TextInput::make('nearest_hospital'),
            Forms\Components\Toggle::make('fire_equipment_available')->label('Fire Equipment Available'),
            Forms\Components\TextInput::make('first_aid_station'),
            Forms\Components\Select::make('safety_risk_level')->label('Safety Risk Level')->options(self::getRiskLevelOptions())->default('Low'),
            Forms\Components\Textarea::make('ppe_requirements')->label('PPE Requirements'),
            Forms\Components\Textarea::make('restricted_zones'),
            Forms\Components\Textarea::make('visitor_policy'),
        ];
    }

    public static function documentsSchema(): array
    {
        $categories = [
            'Site Layout', 'Permits', 'Site Photos', 'Inspection Reports',
            'Safety Certificates', 'Environmental Reports', 'Blueprints', 'Contracts',
        ];

        return collect($categories)
            ->map(fn (string $category) => Forms\Components\FileUpload::make('documents.' . str($category)->slug())
                ->label($category)
                ->disk('public')
                ->directory('construction-documents')
                ->multiple()
                ->dehydrated(false))
            ->toArray();
    }

    public static function syncDocumentsFromFormState(Site $site, array $documents): void
    {
        $categories = [
            'site-layout' => 'Site Layout',
            'permits' => 'Permits',
            'site-photos' => 'Site Photos',
            'inspection-reports' => 'Inspection Reports',
            'safety-certificates' => 'Safety Certificates',
            'environmental-reports' => 'Environmental Reports',
            'blueprints' => 'Blueprints',
            'contracts' => 'Contracts',
        ];

        foreach ($documents as $slug => $paths) {
            $category = $categories[$slug] ?? 'Site Layout';

            foreach ((array) $paths as $path) {
                \App\Models\Document::create([
                    'company_id' => $site->company_id,
                    'project_id' => $site->project_id,
                    'site_id' => $site->id,
                    'category' => $category,
                    'title' => basename($path),
                    'file_path' => $path,
                    'disk' => 'public',
                    'uploaded_by_id' => Auth::id(),
                ]);
            }
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Site')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Info')->schema(self::basicInfoSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Location')->schema(self::locationSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Team')->schema(self::teamSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Operations')->schema(self::operationsSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Safety')->schema(self::safetySchema())->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['project.client', 'teamMembers.employee']))
            ->columns([
                Tables\Columns\TextColumn::make('site_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Site Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->toggleable(),
                Tables\Columns\TextColumn::make('project.client.company_name')->label('Client')->toggleable(),
                Tables\Columns\TextColumn::make('county')->toggleable(),
                Tables\Columns\TextColumn::make('location')->label('Location')->toggleable(),
                Tables\Columns\TextColumn::make('gps')
                    ->label('GPS')
                    ->getStateUsing(fn (Site $record) => $record->gps_lat && $record->gps_lng ? "{$record->gps_lat}, {$record->gps_lng}" : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('site_manager')
                    ->label('Site Manager')
                    ->getStateUsing(fn (Site $record) => $record->teamSlot('Site Manager')?->full_name ?? $record->siteManager?->full_name ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supervisor')
                    ->label('Supervisor')
                    ->getStateUsing(fn (Site $record) => $record->teamSlot('Supervisor')?->full_name ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('workers_today')
                    ->label('Workers Today')
                    ->getStateUsing(fn (Site $record) => Attendance::query()->where('site_id', $record->id)->whereDate('date', today())->where('status', 'Present')->count())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('equipment_assigned')
                    ->label('Equipment')
                    ->getStateUsing(fn (Site $record) => EquipmentAllocation::query()->where('site_id', $record->id)->count())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('progress_percent')->label('Progress')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('current_phase')->label('Phase')->toggleable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? self::getPriorityColor($state) : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('start_date')->date()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expected_completion_date')->date()->label('Expected Completion')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('safety_risk_level')->label('Risk Level')->options(self::getRiskLevelOptions()),
                Tables\Filters\SelectFilter::make('county')->options(fn () => Site::query()->whereNotNull('county')->distinct()->pluck('county', 'county')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('exportSelectedCsv')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('projects.export') || Auth::user()?->can('sites.export'))
                        ->action(fn (Collection $records) => static::streamSitesCsv($records))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportAllCsv')
                    ->label('Export (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('sites.export'))
                    ->action(fn () => static::streamSitesCsv(
                        Site::query()->when(Auth::user()?->company_id, fn ($q, $id) => $q->where('company_id', $id))->get()
                    )),
                Tables\Actions\Action::make('importCsv')
                    ->label('Import Sites')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('sites.create'))
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('CSV File')
                            ->disk('public')
                            ->directory('site-imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $count = static::importSitesFromCsv($data['file']);

                        Notification::make()
                            ->title($count . ' site(s) imported')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateReport')
                    ->label('Generate Report')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('gray')
                    ->url(fn () => \App\Filament\Pages\ConstructionReports::getUrl()),
                Tables\Actions\CreateAction::make(),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function streamSitesCsv(Collection $sites)
    {
        $headers = ['Site Code', 'Name', 'Project', 'County', 'Location', 'Status', 'Priority', 'Progress %', 'Start Date', 'Expected Completion'];

        return response()->streamDownload(function () use ($sites, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($sites as $site) {
                fputcsv($handle, [
                    $site->site_code,
                    $site->name,
                    $site->project?->name,
                    $site->county,
                    $site->location,
                    $site->status,
                    $site->priority,
                    $site->progress_percent,
                    $site->start_date?->format('Y-m-d'),
                    $site->expected_completion_date?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'sites-' . now()->format('Y-m-d') . '.csv');
    }

    public static function importSitesFromCsv(string $filePath): int
    {
        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);
        $handle = fopen($fullPath, 'r');

        if (! $handle) {
            return 0;
        }

        $header = fgetcsv($handle);
        $count = 0;
        $companyId = Auth::user()?->company_id;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, count($header), null);
            $data = array_combine($header, $row);

            if (empty($data['Name'] ?? null)) {
                continue;
            }

            $project = null;
            if (! empty($data['Project'])) {
                $project = Project::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('name', $data['Project'])
                    ->first();
            }

            Site::create([
                'company_id' => $companyId,
                'project_id' => $project?->id,
                'name' => $data['Name'],
                'county' => $data['County'] ?? null,
                'location' => $data['Location'] ?? null,
                'status' => $data['Status'] ?? 'Planning',
                'priority' => $data['Priority'] ?? null,
                'progress_percent' => $data['Progress %'] ?? 0,
                'start_date' => $data['Start Date'] ?? null,
                'expected_completion_date' => $data['Expected Completion'] ?? null,
            ]);

            $count++;
        }

        fclose($handle);

        return $count;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
