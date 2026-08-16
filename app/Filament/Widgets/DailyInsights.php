<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;

class DailyInsights extends Widget
{
    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.daily-insights';

    public ?string $content = null;

    public bool $loading = false;
    
    public ?string $lastRefreshedAt = null;

    public function mount(): void
    {
        $this->loadInsights();
    }

    public function refreshInsights(): void
    {
        $this->loading = true;
        $this->content = null;
        try {
            $user = auth()->user();
            $companyId = $user->company_id ?? ($user->company?->id ?? null);
            if (! $companyId) {
                $this->content = 'No company context available.';
                $this->loading = false;
                return;
            }
            $ai = app(AIService::class);
            $res = $ai->generateDailyInsights($companyId, $user->id);
            $text = $res['success'] ? (string) $res['response'] : 'AI unavailable.';
            $key = $this->cacheKey($companyId);
            Cache::put($key, $text, now()->endOfDay());
            $this->lastRefreshedAt = now()->toDateTimeString();
            Cache::put($this->cacheKeyAt($companyId), $this->lastRefreshedAt, now()->endOfDay());
            $this->content = $text;
        } catch (\Throwable $e) {
            $this->content = 'Failed to refresh insights.';
        }
        $this->loading = false;
    }

    /**
     * Only ever reads from cache here — this runs inside mount(), which is
     * part of the dashboard's initial page render. Calling the AI directly
     * when nothing was cached yet used to block the ENTIRE dashboard load on
     * a live LLM call (seconds on Groq, up to 30-45s on an OpenRouter
     * fallback) for the first page view of the day. If nothing is cached,
     * the view triggers refreshInsights() itself via wire:init — a separate
     * Livewire request that fires only after the page has already rendered,
     * so a slow/missing cache no longer holds up the rest of the dashboard.
     */
    private function loadInsights(): void
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? ($user->company?->id ?? null);
        if (! $companyId) {
            $this->content = 'No company context available.';
            return;
        }
        $key = $this->cacheKey($companyId);
        $cached = Cache::get($key);
        if ($cached) {
            $this->content = (string) $cached;
            $this->lastRefreshedAt = Cache::get($this->cacheKeyAt($companyId));
        }
    }

    private function cacheKey(int $companyId): string
    {
        $date = now()->toDateString();
        return "daily_insights_{$companyId}_{$date}";
    }
    
    private function cacheKeyAt(int $companyId): string
    {
        $date = now()->toDateString();
        return "daily_insights_{$companyId}_{$date}_at";
    }
}
