<?php

namespace App\Services\Ai;

use App\Contracts\AiDataProviderInterface;
use App\Models\User;

/**
 * The one place every AI data provider is registered and gated. Business
 * modules never talk to the AI directly, and the AI never queries Eloquent
 * models directly — everything goes through here, so tenant scoping
 * (explicit company_id on every provider call) and permission checks
 * (reusing the same $user->can($slug) convention HasModuleAccess already
 * uses) are guaranteed regardless of which entry point triggered the read.
 */
class AiDataRegistry
{
    /** @var AiDataProviderInterface[] */
    protected array $providers;

    /**
     * @param AiDataProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return AiDataProviderInterface[]
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function find(string $key): ?AiDataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Providers relevant to this company's industry, BEFORE the permission
     * check — used to detect "the user asked about something that exists
     * for their industry but they lack permission for" versus "that module
     * doesn't apply to their industry at all" (the former should get an
     * explicit denial message; the latter should just be silently absent).
     *
     * @return array<string, AiDataProviderInterface>
     */
    public function industryApplicableProviders(?string $industry): array
    {
        $result = [];

        foreach ($this->providers as $provider) {
            if ($provider->appliesToIndustry($industry)) {
                $result[$provider->key()] = $provider;
            }
        }

        return $result;
    }

    /**
     * The providers this specific user may read from, for this company's
     * industry. This is the ONLY authorization check the AI layer relies on
     * — it is never delegated to the model.
     *
     * @return array<string, AiDataProviderInterface> keyed by provider key
     */
    public function authorizedProviders(User $user, ?string $industry): array
    {
        $result = [];

        foreach ($this->providers as $provider) {
            if (! $provider->appliesToIndustry($industry)) {
                continue;
            }

            $permission = $provider->requiredPermission();
            if ($permission !== null && ! $user->can($permission)) {
                continue;
            }

            $result[$provider->key()] = $provider;
        }

        return $result;
    }

    /**
     * Flatten the OpenAI tool-schema definitions for a set of providers.
     *
     * @param AiDataProviderInterface[] $providers
     */
    public function toolsFor(array $providers): array
    {
        $tools = [];
        foreach ($providers as $provider) {
            foreach ($provider->tools() as $tool) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    /**
     * Dispatch a model-requested tool call. $authorizedProviders must be the
     * caller's own already-permission-filtered set (from
     * authorizedProviders() above) — a tool name belonging to a provider
     * NOT in that set is refused, even if the provider exists in the
     * registry overall, so a mid-conversation tool call can never bypass
     * the authorization check that gated the initial context.
     *
     * @param array<string, AiDataProviderInterface> $authorizedProviders
     */
    public function callTool(string $toolName, array $arguments, int $companyId, array $authorizedProviders): array
    {
        foreach ($authorizedProviders as $provider) {
            foreach ($provider->tools() as $tool) {
                if (($tool['function']['name'] ?? null) === $toolName) {
                    return $provider->callTool($toolName, $arguments, $companyId);
                }
            }
        }

        return ['error' => 'That tool is not available or you do not have permission to use it.'];
    }
}
