<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Attendance;
use App\Models\DailySiteDiary;
use App\Models\Document;
use App\Models\EquipmentAllocation;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Labourer;
use App\Models\MaterialDelivery;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\QualityInspection;
use App\Models\SafetyIncident;
use App\Models\SiteInspection;
use App\Models\Subcontractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.view-project';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\EditAction::make(),

            Actions\Action::make('assignTeam')
                ->label('Assign Team')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('manage', $record))
                ->fillForm(fn () => ProjectResource::getTeamFormStateForProject($record))
                ->form(ProjectResource::teamSchema())
                ->action(function (array $data) use ($record) {
                    ProjectResource::syncTeamFromFormState($record, $data['team'] ?? []);
                    Notification::make()->title('Project team updated')->success()->send();
                }),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('create', \App\Models\Project::class))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $clone = $record->replicate(['project_code']);
                    $clone->name = $record->name . ' (Copy)';
                    $clone->status = 'Planning';
                    $clone->progress_percent = 0;
                    $clone->actual_completion_date = null;
                    $clone->is_archived = false;
                    $clone->save();

                    foreach ($record->sites as $site) {
                        $siteCopy = $site->replicate();
                        $siteCopy->project_id = $clone->id;
                        $siteCopy->save();
                    }

                    Notification::make()->title('Project duplicated as ' . $clone->project_code)->success()->send();

                    $this->redirect(ProjectResource::getUrl('edit', ['record' => $clone]));
                }),

            Actions\Action::make('pause')
                ->label('Pause')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn () => ! in_array($record->status, ['Completed', 'Cancelled', 'On Hold'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('manage', $record)))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['status' => 'On Hold']);
                    Notification::make()->title('Project paused')->success()->send();
                }),

            Actions\Action::make('close')
                ->label('Close')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => ! in_array($record->status, ['Completed', 'Cancelled'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('approve', $record)))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update([
                        'status' => 'Completed',
                        'progress_percent' => 100,
                        'actual_completion_date' => $record->actual_completion_date ?? now(),
                    ]);
                    Notification::make()->title('Project closed')->success()->send();
                }),

            Actions\Action::make('archive')
                ->label(fn () => $record->is_archived ? 'Unarchive' : 'Archive')
                ->icon('heroicon-o-archive-box')
                ->color('danger')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('archive', $record))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['is_archived' => ! $record->is_archived]);
                    Notification::make()->title($record->is_archived ? 'Project archived' : 'Project unarchived')->success()->send();
                }),

            Actions\Action::make('printSummary')
                ->label('Print Summary')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->streamProjectPdf()),
        ];
    }

    protected function streamProjectPdf()
    {
        $record = $this->record;

        $pdf = Pdf::loadView('reports.project-summary', [
            'title' => 'Project Summary — ' . $record->name,
            'date' => now()->format('d M Y'),
            'project' => $record,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'project-summary-' . $record->project_code . '.pdf');
    }

    public function getSites()
    {
        return $this->record->sites()->with('siteManager')->get();
    }

    protected function siteIds(): array
    {
        return $this->record->sites()->pluck('id')->toArray();
    }

    public function getEquipmentAllocations()
    {
        return EquipmentAllocation::query()->whereIn('site_id', $this->siteIds())->with(['equipment', 'site'])->get();
    }

    public function getLabourers()
    {
        return Labourer::query()->whereIn('site_id', $this->siteIds())->with(['employee', 'site'])->get();
    }

    public function getMaterialDeliveries()
    {
        return MaterialDelivery::query()->whereIn('site_id', $this->siteIds())->with(['material', 'site'])->latest('delivered_at')->get();
    }

    public function getSubcontractors()
    {
        return Subcontractor::query()->whereIn('site_id', $this->siteIds())->get();
    }

    public function getDocuments()
    {
        return Document::query()->where('project_id', $this->record->id)->latest()->get();
    }

    public function getSafetyIncidents()
    {
        return SafetyIncident::query()->whereIn('site_id', $this->siteIds())->with('site')->latest('date')->get();
    }

    public function getQualityInspections()
    {
        return QualityInspection::query()->whereIn('site_id', $this->siteIds())->with('site')->latest('date')->get();
    }

    public function getSiteInspections()
    {
        return SiteInspection::query()->whereIn('site_id', $this->siteIds())->with('site')->latest('date')->get();
    }

    public function getDailyDiaries()
    {
        return DailySiteDiary::query()->whereIn('site_id', $this->siteIds())->with('site')->latest('date')->get();
    }

    public function getMilestones()
    {
        return Milestone::query()->where('project_id', $this->record->id)->orderBy('sort_order')->get();
    }

    public function getFinanceSummary(): array
    {
        $record = $this->record;

        $revenue = Payment::query()->where('project_id', $record->id)->where('status', 'Paid')->sum('amount');
        $spent = Expense::query()->where('project_id', $record->id)->sum('amount');
        $outstanding = Invoice::query()->where('project_id', $record->id)->whereIn('status', ['sent', 'overdue'])->sum('total_amount');
        $budget = (float) ($record->budget ?? 0);

        return [
            'budget' => $budget,
            'spent' => (float) $spent,
            'budget_used_percent' => $budget > 0 ? min(100, round(((float) $spent / $budget) * 100)) : 0,
            'revenue' => (float) $revenue,
            'outstanding' => (float) $outstanding,
            'profit' => (float) $revenue - (float) $spent,
        ];
    }

    public function addMilestoneAction(): Actions\Action
    {
        return Actions\Action::make('addMilestone')
            ->label('Add Milestone')
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\DatePicker::make('planned_date'),
                Forms\Components\DatePicker::make('actual_date'),
                Forms\Components\Select::make('status')->options([
                    'Pending' => 'Pending',
                    'In Progress' => 'In Progress',
                    'Completed' => 'Completed',
                    'Delayed' => 'Delayed',
                ])->default('Pending')->required(),
                Forms\Components\Toggle::make('is_critical_path')->label('On Critical Path'),
                Forms\Components\Select::make('depends_on_milestone_id')
                    ->label('Depends On')
                    ->options(fn () => $this->getMilestones()->pluck('name', 'id')),
            ])
            ->action(function (array $data) {
                Milestone::create([
                    ...$data,
                    'company_id' => $this->record->company_id,
                    'project_id' => $this->record->id,
                    'sort_order' => $this->getMilestones()->count(),
                ]);
                Notification::make()->title('Milestone added')->success()->send();
            });
    }
}
