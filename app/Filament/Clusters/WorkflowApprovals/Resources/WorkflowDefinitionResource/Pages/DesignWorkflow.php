<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource;
use App\Models\Department;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionVersion;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowNotificationRecipients;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class DesignWorkflow extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = WorkflowDefinitionResource::class;

    protected static string $view = 'filament.clusters.workflow-approvals.resources.workflow-definition-resource.pages.design-workflow';

    public WorkflowDefinition $record;

    public ?array $data = [];

    public array $graph = ['nodes' => [], 'edges' => []];

    public function mount(WorkflowDefinition $record): void
    {
        $this->record = $record;

        $this->form->fill([
            'name' => $record->name,
            'description' => $record->description,
            'module' => $record->module,
            'entity_type' => $record->entity_type,
            'department_id' => $record->department_id,
            'industry' => $record->industry,
            'effective_date' => $record->effective_date,
            'expiry_date' => $record->expiry_date,
        ]);

        $this->graph = $record->definition ?: ['nodes' => [], 'edges' => []];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(6)->schema([
                Forms\Components\TextInput::make('name')->required()->columnSpan(2),
                Forms\Components\Select::make('module')->options([
                    'Core' => 'Core', 'Construction' => 'Construction', 'Fleet' => 'Fleet',
                    'HR' => 'HR', 'Finance' => 'Finance', 'Quarry' => 'Quarry',
                ])->required(),
                Forms\Components\Select::make('department_id')->label('Department')
                    ->options(fn () => Department::query()->pluck('name', 'id')),
                Forms\Components\Select::make('industry')->label('Industry')->options([
                    'construction' => 'Construction', 'trucking' => 'Trucking', 'quarry' => 'Quarry',
                ])->placeholder('All industries'),
                Forms\Components\TextInput::make('entity_type')->label('Applies To (model)'),
                Forms\Components\Textarea::make('description')->columnSpanFull()->rows(1),
                Forms\Components\DatePicker::make('effective_date'),
                Forms\Components\DatePicker::make('expiry_date'),
            ]),
        ])->statePath('data');
    }

    public static function nodeTypeCatalog(): array
    {
        return [
            'start' => ['label' => 'Start', 'inputs' => 0, 'outputs' => 1, 'color' => '#10b981'],
            'approval_step' => ['label' => 'Approval Step', 'inputs' => 1, 'outputs' => 1, 'color' => '#3b82f6'],
            'decision' => ['label' => 'Decision', 'inputs' => 1, 'outputs' => 4, 'color' => '#f59e0b'],
            'condition' => ['label' => 'Condition', 'inputs' => 1, 'outputs' => 4, 'color' => '#f59e0b'],
            'parallel' => ['label' => 'Parallel Approval', 'inputs' => 1, 'outputs' => 4, 'color' => '#8b5cf6'],
            'sequential' => ['label' => 'Sequential Approval', 'inputs' => 1, 'outputs' => 1, 'color' => '#6366f1'],
            'timer' => ['label' => 'Timer', 'inputs' => 1, 'outputs' => 1, 'color' => '#64748b'],
            'notification' => ['label' => 'Notification', 'inputs' => 1, 'outputs' => 1, 'color' => '#0ea5e9'],
            'document_verification' => ['label' => 'Document Verification', 'inputs' => 1, 'outputs' => 1, 'color' => '#14b8a6'],
            'signature' => ['label' => 'Digital Signature', 'inputs' => 1, 'outputs' => 1, 'color' => '#ec4899'],
            'end' => ['label' => 'End', 'inputs' => 1, 'outputs' => 0, 'color' => '#ef4444'],
        ];
    }

    public function getApproverRoleOptions(): array
    {
        return \Spatie\Permission\Models\Role::query()->pluck('name', 'name')->all();
    }

    public function getDepartmentOptions(): array
    {
        return Department::query()->pluck('name', 'id')->all();
    }

    public function getVersionHistory(): array
    {
        return WorkflowDefinitionVersion::query()
            ->where('workflow_definition_id', $this->record->id)
            ->with('publishedBy')
            ->orderByDesc('version')
            ->get()
            ->map(fn (WorkflowDefinitionVersion $v) => [
                'id' => $v->id,
                'version' => $v->version,
                'published_at' => optional($v->published_at)->format('d M Y H:i'),
                'published_by' => $v->publishedBy?->name ?? '—',
            ])
            ->all();
    }

    public function saveDraft(array $graph): void
    {
        $this->persist($graph, 'Draft');
    }

    public function publish(array $graph): void
    {
        $errors = $this->validateGraph($graph);

        if (! empty($errors)) {
            Notification::make()->title('Cannot publish')->body(implode(' ', $errors))->danger()->send();

            return;
        }

        $this->persist($graph, 'Published');
    }

    public function rollback(int $versionId): void
    {
        $version = WorkflowDefinitionVersion::query()
            ->where('workflow_definition_id', $this->record->id)
            ->findOrFail($versionId);

        $this->graph = $version->definition;
        $this->persist($version->definition, 'Published');

        Notification::make()->title("Rolled back to version {$version->version}")->success()->send();
    }

    protected function persist(array $graph, string $status): void
    {
        $formState = $this->form->getState();
        $nodeTypesUsed = collect($graph['nodes'] ?? [])->pluck('type')->unique()->values()->all();

        $this->record->update(array_merge($formState, [
            'status' => $status,
            'definition' => $graph,
            'node_types_used' => $nodeTypesUsed,
            'version' => $status === 'Published' ? $this->record->version + 1 : $this->record->version,
        ]));

        if ($status === 'Published') {
            WorkflowDefinitionVersion::create([
                'company_id' => $this->record->company_id,
                'workflow_definition_id' => $this->record->id,
                'version' => $this->record->version,
                'definition' => $graph,
                'published_at' => now(),
                'published_by_id' => Auth::id(),
            ]);

            Notification::make()->title('Workflow published — version ' . $this->record->version)->success()->send();

            $recipients = app(WorkflowNotificationRecipients::class)->forCompany($this->record->company_id);
            if ($recipients->isNotEmpty()) {
                NotificationFacade::send($recipients, new WorkflowNotification('workflow_published', [
                    'reference' => $this->record->name,
                ]));
            }
        } else {
            Notification::make()->title('Draft saved')->success()->send();
        }

        $this->graph = $graph;
    }

    protected function validateGraph(array $graph): array
    {
        $errors = [];
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        $starts = $nodes->where('type', 'start');
        $ends = $nodes->where('type', 'end');

        if ($starts->count() !== 1) {
            $errors[] = 'The workflow must have exactly one Start node.';
        }

        if ($ends->count() < 1) {
            $errors[] = 'The workflow must have at least one End node.';
        }

        if ($starts->count() === 1) {
            $startId = $starts->first()['id'];
            $reachable = [$startId => true];
            $queue = [$startId];

            while (! empty($queue)) {
                $current = array_pop($queue);

                foreach ($edges->where('from', $current) as $edge) {
                    if (! isset($reachable[$edge['to']])) {
                        $reachable[$edge['to']] = true;
                        $queue[] = $edge['to'];
                    }
                }
            }

            foreach ($nodes as $node) {
                if (! isset($reachable[$node['id']])) {
                    $errors[] = "The \"{$node['type']}\" node is not reachable from Start.";
                }
            }
        }

        foreach ($nodes->whereIn('type', ['decision', 'condition']) as $node) {
            if ($edges->where('from', $node['id'])->count() < 2) {
                $errors[] = "Decision/Condition nodes need at least 2 outgoing branches (\"{$node['type']}\" node has fewer).";
            }
        }

        return $errors;
    }
}
