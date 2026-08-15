<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages\DesignWorkflow;
use App\Models\WorkflowDefinition;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class ApprovalTemplates extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.manage';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Approval Templates';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Approval Templates';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.approval-templates';

    public function getTemplates()
    {
        return WorkflowDefinition::withoutGlobalScope('company')
            ->whereNull('company_id')
            ->where('status', 'Published')
            ->orderBy('name')
            ->get();
    }

    public function useTemplate(int $templateId): void
    {
        $template = WorkflowDefinition::withoutGlobalScope('company')->whereNull('company_id')->findOrFail($templateId);

        $clone = WorkflowDefinition::create([
            'company_id' => Auth::user()?->company_id,
            'name' => $template->name . ' (Copy)',
            'description' => $template->description,
            'module' => $template->module,
            'status' => 'Draft',
            'version' => 1,
            'definition' => $template->definition,
            'node_types_used' => $template->node_types_used,
            'created_by_id' => Auth::id(),
        ]);

        Notification::make()->title('Template cloned — customize and publish it')->success()->send();

        $this->redirect(DesignWorkflow::getUrl(['record' => $clone]));
    }
}
