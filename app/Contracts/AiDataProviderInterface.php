<?php

namespace App\Contracts;

/**
 * Standardized bridge between one business module and the AI context layer.
 * The AI never queries Eloquent models directly — every read goes through a
 * provider like this, which is the one place tenant scoping (explicit
 * company_id filtering, since MultiTenantScope is absent outside an
 * authenticated web/job context) and permission gating are guaranteed to
 * apply, regardless of which entry point (chat panel, async API, tool-call
 * drill-down) triggered the read.
 */
interface AiDataProviderInterface
{
    /**
     * Stable identifier used by the router/registry/tool-call dispatch,
     * e.g. 'fleet', 'fuel', 'workshop'.
     */
    public function key(): string;

    /**
     * Human-readable label for logging/audit (modules_accessed).
     */
    public function label(): string;

    /**
     * Spatie permission/module slug required to read this provider's data,
     * matching the exact convention HasModuleAccess already uses
     * ($user->can($slug)). Null means no extra gate beyond industry match.
     */
    public function requiredPermission(): ?string;

    /**
     * Whether this provider is relevant for a company on the given
     * industry. Null industry (e.g. Super Admin, no company) is treated
     * per-provider — most providers should return false for null unless
     * they're industry-agnostic (CRM, Finance, Procurement).
     */
    public function appliesToIndustry(?string $industry): bool;

    /**
     * Compact aggregate context for this module — the eager, "always
     * computed by Tanaki, never by the LLM" summary handed to the model.
     * $period is ['from' => Carbon, 'to' => Carbon, 'label' => string] or
     * null, in which case the provider applies its own sensible default
     * (usually "current state" rather than a date-ranged aggregate).
     *
     * @param array{from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon, label: string}|null $period
     */
    public function summary(int $companyId, ?array $period = null): array;

    /**
     * OpenAI-compatible tool/function definitions this provider exposes for
     * on-demand drill-down, e.g.:
     * [['type' => 'function', 'function' => ['name' => ..., 'description' => ..., 'parameters' => [...]]]]
     */
    public function tools(): array;

    /**
     * Dispatch one of this provider's own tool names (from tools() above)
     * with model-supplied arguments. Must re-apply the same company scoping
     * as summary() — never trust the caller beyond $companyId.
     */
    public function callTool(string $name, array $arguments, int $companyId): array;
}
