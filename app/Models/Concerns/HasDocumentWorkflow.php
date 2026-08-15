<?php

namespace App\Models\Concerns;

use App\Models\DigitalSignature;
use App\Models\DocumentApproval;
use App\Models\DocumentAuditLog;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared polymorphic engine used by every EDMS entity (documents, drawings,
 * RFIs, method statements, technical submittals, transmittals) — one
 * approval/version/signature/share/audit-log mechanism instead of six.
 */
trait HasDocumentWorkflow
{
    /**
     * The polymorphic children below have no DB-level foreign key (they key
     * on `*_type`/`*_id` columns, not a real constraint), so deleting the
     * parent would otherwise leave orphaned rows — clean them up explicitly.
     */
    protected static function bootHasDocumentWorkflow(): void
    {
        static::deleting(function ($model) {
            $model->approvals()->delete();
            $model->versions()->delete();
            $model->signatures()->delete();
            $model->shares()->delete();
            $model->auditLogs()->delete();
        });
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(DocumentApproval::class, 'approvable')->orderBy('step_order');
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(DocumentVersion::class, 'versionable')->latest('created_at');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(DigitalSignature::class, 'signable');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(DocumentShare::class, 'shareable');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(DocumentAuditLog::class, 'auditable')->latest('created_at');
    }

    public function currentVersionRecord(): ?DocumentVersion
    {
        return $this->versions()->where('is_current', true)->first();
    }

    public function pendingApproval(): ?DocumentApproval
    {
        return $this->approvals()->where('status', 'Pending')->orderBy('step_order')->first();
    }
}
