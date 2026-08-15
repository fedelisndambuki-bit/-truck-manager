<?php

namespace App\Services\Construction;

use App\Models\Drawing;
use App\Models\DocumentApproval;
use App\Models\DocumentAuditLog;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\EdmsDocument;
use App\Models\Rfi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Central engine for the shared EDMS workflow mechanics (versioning, approval
 * chains, audit logging) and the Dashboard/Reports aggregates — mirrors
 * ProjectFinanceService's shape so every Resource/Page/Widget shares one
 * implementation instead of re-deriving this logic per entity type.
 */
class DocumentManagementService
{
    /**
     * Record a new version of any versionable entity (EdmsDocument, Drawing, ...),
     * superseding whichever version was previously current.
     */
    public function createVersion(Model $versionable, array $data): DocumentVersion
    {
        $versionable->versions()->where('is_current', true)->update(['is_current' => false]);

        $revisionNumber = ((int) $versionable->versions()->max('revision_number')) + 1;
        $versionNumber = $data['version_number'] ?? $this->nextVersionNumber($versionable);

        $version = $versionable->versions()->create([
            'company_id' => $versionable->company_id,
            'version_number' => $versionNumber,
            'revision_number' => $revisionNumber,
            'file_path' => $data['file_path'] ?? null,
            'disk' => $data['disk'] ?? 'public',
            'file_size' => $data['file_size'] ?? null,
            'file_type' => $data['file_type'] ?? null,
            'change_notes' => $data['change_notes'] ?? null,
            'uploaded_by_id' => Auth::id(),
            'is_current' => true,
        ]);

        $versionable->forceFill([
            'current_version' => $versionNumber,
            'revision_number' => $revisionNumber,
        ])->save();

        $this->logAudit($versionable, 'edited', ['version' => $versionNumber]);

        return $version;
    }

    protected function nextVersionNumber(Model $versionable): string
    {
        // The very first version keeps the record's default (e.g. "1.0") —
        // only subsequent versions bump it.
        if (! $versionable->versions()->exists()) {
            return (string) ($versionable->current_version ?? '1.0');
        }

        $current = (float) ($versionable->current_version ?? '1.0');

        return number_format($current + 0.1, 1);
    }

    /**
     * Create an ordered approval chain for any approvable entity, e.g.
     * ['Site Engineer', 'Project Manager', 'Construction Manager'].
     */
    public function startApprovalWorkflow(Model $approvable, array $approverChain): void
    {
        $approvable->approvals()->delete();

        foreach (array_values($approverChain) as $index => $role) {
            $approvable->approvals()->create([
                'company_id' => $approvable->company_id,
                'step_order' => $index + 1,
                'approver_role' => $role,
                'status' => 'Pending',
            ]);
        }

        $approvable->forceFill(['status' => 'Submitted'])->save();

        $firstStep = $approvable->approvals()->orderBy('step_order')->first();
        $firstStep?->notifyApprovalRequired();
    }

    public function logAudit(Model $auditable, string $action, ?array $metadata = null): DocumentAuditLog
    {
        return $auditable->auditLogs()->create([
            'company_id' => $auditable->company_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => request()?->ip(),
            'metadata' => $metadata,
        ]);
    }

    public function shareDocument(Model $shareable, array $data): DocumentShare
    {
        $share = $shareable->shares()->create([
            'company_id' => $shareable->company_id,
            'shared_with_type' => $data['shared_with_type'],
            'shared_with_name' => $data['shared_with_name'] ?? null,
            'shared_with_email' => $data['shared_with_email'] ?? null,
            'access_level' => $data['access_level'] ?? 'view',
            'shared_by_id' => Auth::id(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $this->logAudit($shareable, 'shared', ['shared_with' => $data['shared_with_name'] ?? $data['shared_with_type']]);

        return $share;
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function totalDocuments(?int $companyId): int
    {
        return EdmsDocument::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
    }

    public function newDocumentsToday(?int $companyId): int
    {
        return EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->count();
    }

    public function pendingApprovals(?int $companyId): int
    {
        return DocumentApproval::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Pending')
            ->count();
    }

    public function documentsUnderReview(?int $companyId): int
    {
        return EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Under Review')
            ->count();
    }

    public function rfisOpen(?int $companyId): int
    {
        return Rfi::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Open')
            ->count();
    }

    public function drawingRevisions(?int $companyId): int
    {
        return DocumentVersion::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('versionable_type', Drawing::class)
            ->count();
    }

    public function expiredDocuments(?int $companyId): int
    {
        return EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->count();
    }

    public function contractsExpiring(?int $companyId, int $withinDays = 30): int
    {
        return EdmsDocument::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('category', 'Contracts')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [today(), today()->addDays($withinDays)])
            ->count();
    }

    public function documentsSharedToday(?int $companyId): int
    {
        return DocumentShare::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->count();
    }

    public function storageUsedBytes(?int $companyId): int
    {
        $documents = (int) EdmsDocument::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('file_size');
        $versions = (int) DocumentVersion::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('file_size');

        return $documents + $versions;
    }

    public function storageUsedForHumans(?int $companyId): string
    {
        $bytes = $this->storageUsedBytes($companyId);

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1) . ' ' . $units[$power];
    }
}
