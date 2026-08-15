<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'definition' => 'array',
        'node_types_used' => 'array',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (WorkflowDefinition $definition) {
            if (empty($definition->internal_key)) {
                $definition->internal_key = str($definition->name)->slug()->toString() . '-' . str()->random(6);
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowDefinitionVersion::class)->latest('version');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function isTemplate(): bool
    {
        return $this->company_id === null;
    }

    public function nodes(): array
    {
        return $this->definition['nodes'] ?? [];
    }

    public function edges(): array
    {
        return $this->definition['edges'] ?? [];
    }

    public function triggers(): array
    {
        return $this->definition['triggers'] ?? [];
    }

    public function findNode(string $nodeKey): ?array
    {
        foreach ($this->nodes() as $node) {
            if (($node['id'] ?? null) === $nodeKey) {
                return $node;
            }
        }

        return null;
    }

    public function startNode(): ?array
    {
        foreach ($this->nodes() as $node) {
            if (($node['type'] ?? null) === 'start') {
                return $node;
            }
        }

        return null;
    }

    public function edgesFrom(string $nodeKey): array
    {
        return array_values(array_filter($this->edges(), fn ($edge) => ($edge['from'] ?? null) === $nodeKey));
    }

    public function recomputeNodeTypesUsed(): void
    {
        $this->node_types_used = collect($this->nodes())->pluck('type')->unique()->values()->all();
    }
}
