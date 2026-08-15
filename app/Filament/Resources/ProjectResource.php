<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Client;
use App\Models\Project;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 2;

    public static function getCategoryOptions(): array
    {
        return [
            'Residential' => 'Residential',
            'Commercial' => 'Commercial',
            'Industrial' => 'Industrial',
            'Road Construction' => 'Road Construction',
            'Bridge' => 'Bridge',
            'Hospital' => 'Hospital',
            'School' => 'School',
            'Apartment' => 'Apartment',
            'Warehouse' => 'Warehouse',
            'Government' => 'Government',
            'Mixed Development' => 'Mixed Development',
            'Other' => 'Other',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Planning' => 'Planning',
            'Tender Stage' => 'Tender Stage',
            'Active' => 'Active',
            'Delayed' => 'Delayed',
            'On Hold' => 'On Hold',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
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
            'Tender Stage' => 'gray',
            'Active' => 'success',
            'Delayed' => 'danger',
            'On Hold' => 'warning',
            'Completed' => 'info',
            'Cancelled' => 'danger',
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

    /**
     * Shared "Basic Info" form fields, reused by the Create wizard's step 1
     * and the Edit page's tabbed form so the two never drift apart.
     */
    public static function basicInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->label('Project Name')->required(),
            Forms\Components\TextInput::make('project_code')
                ->label('Project Code')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Auto-generated'),
            Forms\Components\Select::make('category')->options(self::getCategoryOptions())->searchable(),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium'),
            Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Planning')->required(),
            Forms\Components\TextInput::make('current_phase'),
        ];
    }

    public static function clientInfoSchema(): array
    {
        return [
            Forms\Components\Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'company_name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Forms\Components\TextInput::make('company_name')->required(),
                    Forms\Components\TextInput::make('contact_person')->required(),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('phone')->required(),
                    Forms\Components\TextInput::make('address'),
                    Forms\Components\TextInput::make('tax_number'),
                    Forms\Components\TextInput::make('emergency_contact'),
                ])
                ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action->modalHeading('Create Client')),
        ];
    }

    public static function locationSchema(): array
    {
        return [
            Forms\Components\TextInput::make('country'),
            Forms\Components\TextInput::make('county'),
            Forms\Components\TextInput::make('city'),
            Forms\Components\TextInput::make('site_address')->label('Physical Address')->columnSpanFull(),
            Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
            Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
            Forms\Components\TextInput::make('project_zone'),
        ];
    }

    public static function getTeamSlots(): array
    {
        return [
            'Project Director', 'Project Manager', 'Site Engineer', 'Quantity Surveyor',
            'Architect', 'Structural Engineer', 'Electrical Engineer', 'Mechanical Engineer',
            'Safety Officer', 'Procurement Officer', 'Finance Officer', 'Store Manager',
        ];
    }

    /**
     * Prefills the `team.<slug>` select values from a project's existing
     * ProjectTeamMember rows, for the "Assign Team" edit modal.
     */
    public static function getTeamFormStateForProject(Project $project): array
    {
        $team = [];

        foreach ($project->teamMembers as $member) {
            $team[(string) str($member->role_slot)->slug()] = $member->employee_id;
        }

        return ['team' => $team];
    }

    public static function teamSchema(): array
    {
        return collect(self::getTeamSlots())
            ->map(fn (string $slot) => Forms\Components\Select::make('team.' . str($slot)->slug())
                ->label($slot)
                ->options(fn () => \App\Models\Staff::query()->pluck('full_name', 'id'))
                ->searchable()
                ->preload()
                ->dehydrated(false))
            ->toArray();
    }

    /**
     * Persists the wizard/edit form's virtual `team.<slug>` selects into the
     * project_team_members pivot. Replaces any existing slot assignments.
     */
    public static function syncTeamFromFormState(Project $project, array $team): void
    {
        foreach (self::getTeamSlots() as $slot) {
            $employeeId = $team[(string) str($slot)->slug()] ?? null;

            \App\Models\ProjectTeamMember::query()
                ->where('project_id', $project->id)
                ->where('role_slot', $slot)
                ->delete();

            if ($employeeId) {
                \App\Models\ProjectTeamMember::create([
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'employee_id' => $employeeId,
                    'role_slot' => $slot,
                ]);
            }
        }
    }

    /**
     * Persists the wizard's virtual `documents.<slug>` file uploads into the
     * Document model, one row per uploaded file, categorized by slot.
     */
    public static function syncDocumentsFromFormState(Project $project, array $documents): void
    {
        $categories = [
            'contract' => 'Contract',
            'blueprint' => 'Blueprint',
            'architectural-drawings' => 'Architectural Drawings',
            'structural-drawings' => 'Structural Drawings',
            'boq' => 'BOQ',
            'permit' => 'Permit',
            'environmental-certificates' => 'Environmental Certificates',
            'insurance' => 'Insurance',
            'safety-plan' => 'Safety Plan',
            'supporting-documents' => 'Supporting Documents',
        ];

        foreach ($documents as $slug => $paths) {
            $category = $categories[$slug] ?? 'Contract';

            foreach ((array) $paths as $path) {
                \App\Models\Document::create([
                    'company_id' => $project->company_id,
                    'project_id' => $project->id,
                    'category' => $category,
                    'title' => basename($path),
                    'file_path' => $path,
                    'disk' => 'public',
                    'uploaded_by_id' => Auth::id(),
                ]);
            }
        }
    }

    public static function financialSchema(): array
    {
        return [
            Forms\Components\TextInput::make('contract_value')->numeric()->prefix(fn ($get) => $get('currency') ?? session('currency', 'KES')),
            Forms\Components\TextInput::make('budget')->numeric()->prefix(fn ($get) => $get('currency') ?? session('currency', 'KES')),
            Forms\Components\TextInput::make('estimated_cost')->numeric(),
            Forms\Components\TextInput::make('expected_revenue')->numeric(),
            Forms\Components\TextInput::make('retention_percent')->label('Retention %')->numeric()->suffix('%'),
            Forms\Components\TextInput::make('advance_payment')->numeric(),
            Forms\Components\TextInput::make('currency')->default(session('currency', 'KES')),
            Forms\Components\TextInput::make('payment_terms'),
            Forms\Components\TextInput::make('cost_centre'),
            Forms\Components\TextInput::make('funding_source'),
        ];
    }

    public static function timelineSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('tender_date'),
            Forms\Components\DatePicker::make('contract_signing_date'),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('end_date')->label('Expected Completion'),
            Forms\Components\DatePicker::make('actual_completion_date'),
            Forms\Components\DatePicker::make('warranty_end_date'),
            Forms\Components\TextInput::make('estimated_duration_days')->numeric()->suffix('days'),
            Forms\Components\TextInput::make('progress_percent')->label('Progress %')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(0),
        ];
    }

    public static function settingsSchema(): array
    {
        return [
            Forms\Components\TextInput::make('working_days')->placeholder('e.g. Mon-Sat'),
            Forms\Components\TextInput::make('working_hours')->placeholder('e.g. 08:00-17:00'),
            Forms\Components\TextInput::make('default_warehouse'),
            Forms\Components\TextInput::make('default_equipment_pool'),
            Forms\Components\TextInput::make('default_labour_pool'),
            Forms\Components\TextInput::make('approval_workflow')->placeholder('e.g. Single Approver'),
            Forms\Components\Select::make('risk_level')->options(self::getRiskLevelOptions())->default('Low'),
            Forms\Components\Toggle::make('weather_monitoring_enabled')->label('Weather Monitoring'),
        ];
    }

    public static function documentsSchema(): array
    {
        $categories = [
            'Contract', 'Blueprint', 'Architectural Drawings', 'Structural Drawings', 'BOQ',
            'Permit', 'Environmental Certificates', 'Insurance', 'Safety Plan', 'Supporting Documents',
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Project')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Info')->schema(self::basicInfoSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Client')->schema(self::clientInfoSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Location')->schema(self::locationSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Team')->schema(self::teamSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Financials')->schema(self::financialSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Timeline')->schema(self::timelineSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Settings')->schema(self::settingsSchema())->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['client', 'projectManager', 'teamMembers.employee']))
            ->columns([
                Tables\Columns\TextColumn::make('project_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Project Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->toggleable(),
                Tables\Columns\TextColumn::make('category')->toggleable(),
                Tables\Columns\TextColumn::make('site_address')->label('Location')->toggleable(),
                Tables\Columns\TextColumn::make('country')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('project_manager')
                    ->label('Project Manager')
                    ->getStateUsing(fn (Project $record) => $record->teamSlot('Project Manager')?->full_name ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('site_engineer')
                    ->label('Site Engineer')
                    ->getStateUsing(fn (Project $record) => $record->teamSlot('Site Engineer')?->full_name ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contract_value')->money()->toggleable()->sortable(),
                Tables\Columns\TextColumn::make('budget')->money()->toggleable()->sortable(),
                Tables\Columns\TextColumn::make('estimated_cost')->money()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                Tables\Columns\TextColumn::make('expected_revenue')->money()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                Tables\Columns\TextColumn::make('progress_percent')->label('Progress')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('current_phase')->label('Phase')->toggleable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? self::getPriorityColor($state) : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('risk_level')
                    ->label('Risk')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? self::getPriorityColor($state) : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('start_date')->date()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')->date()->label('Expected Completion')->toggleable(),
                Tables\Columns\TextColumn::make('actual_completion_date')->date()->label('Actual Completion')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Updated')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('risk_level')->label('Risk Level')->options(self::getRiskLevelOptions()),
                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label('Archived')
                    ->default(false)
                    ->placeholder('Active projects')
                    ->trueLabel('Archived only')
                    ->falseLabel('Active only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('exportSelectedCsv')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('projects.export'))
                        ->action(fn (Collection $records) => static::streamProjectsCsv($records))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportAllCsv')
                    ->label('Export (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('projects.export'))
                    ->action(fn () => static::streamProjectsCsv(
                        Project::query()->when(Auth::user()?->company_id, fn ($q, $id) => $q->where('company_id', $id))->get()
                    )),
                Tables\Actions\Action::make('importCsv')
                    ->label('Import Projects')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('projects.create'))
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('CSV File')
                            ->disk('public')
                            ->directory('project-imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $count = static::importProjectsFromCsv($data['file']);

                        Notification::make()
                            ->title($count . ' project(s) imported')
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

    public static function streamProjectsCsv(Collection $projects)
    {
        $headers = ['Project Code', 'Name', 'Client', 'Category', 'Status', 'Priority', 'Location', 'Budget', 'Contract Value', 'Progress %', 'Start Date', 'Expected Completion'];

        return response()->streamDownload(function () use ($projects, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($projects as $project) {
                fputcsv($handle, [
                    $project->project_code,
                    $project->name,
                    $project->client?->company_name,
                    $project->category,
                    $project->status,
                    $project->priority,
                    $project->site_address,
                    $project->budget,
                    $project->contract_value,
                    $project->progress_percent,
                    $project->start_date?->format('Y-m-d'),
                    $project->end_date?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'projects-' . now()->format('Y-m-d') . '.csv');
    }

    public static function importProjectsFromCsv(string $filePath): int
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

            $client = null;
            if (! empty($data['Client'])) {
                $client = Client::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('company_name', $data['Client'])
                    ->first();
            }

            Project::create([
                'company_id' => $companyId,
                'client_id' => $client?->id,
                'name' => $data['Name'],
                'category' => $data['Category'] ?? null,
                'status' => $data['Status'] ?? 'Planning',
                'priority' => $data['Priority'] ?? null,
                'site_address' => $data['Location'] ?? null,
                'budget' => $data['Budget'] ?? null,
                'contract_value' => $data['Contract Value'] ?? null,
                'progress_percent' => $data['Progress %'] ?? 0,
                'start_date' => $data['Start Date'] ?? null,
                'end_date' => $data['Expected Completion'] ?? null,
            ]);

            $count++;
        }

        fclose($handle);

        return $count;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
