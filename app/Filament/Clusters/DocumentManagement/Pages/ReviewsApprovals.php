<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\DocumentApproval;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ReviewsApprovals extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Reviews & Approvals';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Reviews & Approvals';

    protected static string $view = 'filament.clusters.document-management.pages.reviews-approvals';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getRows(): array
    {
        return DocumentApproval::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Pending')
            ->with('approvable')
            ->orderBy('step_order')
            ->get()
            ->map(function (DocumentApproval $approval) {
                $entity = $approval->approvable;

                return [
                    'approval' => $approval,
                    'entity_label' => class_basename($approval->approvable_type),
                    'entity_title' => $entity?->title ?? $entity?->subject ?? $entity?->transmittal_number ?? '—',
                    'entity_number' => $entity?->document_number ?? $entity?->drawing_number ?? $entity?->rfi_number
                        ?? $entity?->reference_number ?? $entity?->submittal_number ?? $entity?->transmittal_number ?? '—',
                ];
            })
            ->all();
    }

    public function approve(int $approvalId): void
    {
        $approval = DocumentApproval::findOrFail($approvalId);
        $approval->approve();

        Notification::make()->title('Step approved')->success()->send();
    }

    public function reject(int $approvalId): void
    {
        $approval = DocumentApproval::findOrFail($approvalId);
        $approval->reject();

        Notification::make()->title('Rejected')->danger()->send();
    }
}
