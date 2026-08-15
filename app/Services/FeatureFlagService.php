<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\User;

class FeatureFlagService
{
    public function enabled(string $key, ?int $companyId = null): bool
    {
        return $this->enabledForContext($key, $companyId, null, null);
    }

    public function enabledForUser(string $key, ?User $user): bool
    {
        return $this->enabledForContext(
            $key,
            $user?->company_id,
            $user?->company?->plan_id,
            $user?->id
        );
    }

    private function enabledForContext(string $key, ?int $companyId, ?int $planId, ?int $userId): bool
    {
        $companySpecific = FeatureFlag::query()
            ->where('key', $key)
            ->when($companyId !== null, fn ($q) => $q->where('company_id', $companyId))
            ->whereNull('plan_id')
            ->first();

        if ($companySpecific) {
            return $this->passesRollout($companySpecific->rollout_percentage ?? 100, $companyId, $userId)
                ? (bool) $companySpecific->enabled
                : false;
        }

        $planSpecific = FeatureFlag::query()
            ->where('key', $key)
            ->whereNull('company_id')
            ->when($planId !== null, fn ($q) => $q->where('plan_id', $planId))
            ->first();

        if ($planSpecific) {
            return $this->passesRollout($planSpecific->rollout_percentage ?? 100, $companyId, $userId)
                ? (bool) $planSpecific->enabled
                : false;
        }

        $global = FeatureFlag::query()
            ->where('key', $key)
            ->whereNull('company_id')
            ->whereNull('plan_id')
            ->first();

        if ($global) {
            return $this->passesRollout($global->rollout_percentage ?? 100, $companyId, $userId)
                ? (bool) $global->enabled
                : false;
        }

        // Default open to avoid hard break when flag is undefined.
        return true;
    }

    private function passesRollout(int $percentage, ?int $companyId, ?int $userId): bool
    {
        $percentage = max(0, min(100, $percentage));

        if ($percentage >= 100) {
            return true;
        }

        if ($percentage <= 0) {
            return false;
        }

        $seed = (string) ($userId ?: $companyId ?: 0);
        $bucket = abs((int) crc32($seed)) % 100;

        return $bucket < $percentage;
    }
}
