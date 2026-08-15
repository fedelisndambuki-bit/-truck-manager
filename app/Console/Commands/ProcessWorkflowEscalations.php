<?php

namespace App\Console\Commands;

use App\Models\ApprovalStep;
use App\Models\Staff;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Console\Command;

class ProcessWorkflowEscalations extends Command
{
    protected $signature = 'workflow:process-escalations';

    protected $description = 'Escalate overdue approval steps past their SLA and complete elapsed Timer nodes';

    public function handle(WorkflowEngine $engine): void
    {
        $escalated = $this->escalateOverdueSteps($engine);
        $timers = $this->completeElapsedTimers($engine);

        $this->info("Workflow escalation sweep complete. Escalated {$escalated} step(s), completed {$timers} timer(s).");
    }

    protected function escalateOverdueSteps(WorkflowEngine $engine): int
    {
        $count = 0;

        ApprovalStep::query()
            ->where('status', 'Pending')
            ->where('approver_resolution_mode', '!=', 'timer')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with(['actions.approver', 'approvalRequest'])
            ->each(function (ApprovalStep $step) use ($engine, &$count) {
                $pendingAction = $step->actions->firstWhere('action', null);
                $target = $this->resolveEscalationTarget($pendingAction?->approver, $step->company_id);

                if (! $target) {
                    return;
                }

                $engine->escalate($step, $target, 'auto_sla_breach', (int) abs(now()->diffInHours($step->due_at)));
                $count++;
            });

        return $count;
    }

    protected function resolveEscalationTarget(?User $currentApprover, ?int $companyId): ?User
    {
        if ($currentApprover) {
            $staff = Staff::withoutGlobalScope('company')->where('email', $currentApprover->email)->first();
            $supervisor = $staff?->supervisor;

            if ($supervisor && $supervisor->email) {
                $user = User::query()->where('company_id', $companyId)->where('email', $supervisor->email)->first();

                if ($user) {
                    return $user;
                }
            }
        }

        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->first(fn (User $u) => $u->hasRole('Managing Director'));
    }

    protected function completeElapsedTimers(WorkflowEngine $engine): int
    {
        $count = 0;

        ApprovalStep::query()
            ->where('status', 'Pending')
            ->where('approver_resolution_mode', 'timer')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->each(function (ApprovalStep $step) use ($engine, &$count) {
                $engine->completeTimerStep($step);
                $count++;
            });

        return $count;
    }
}
