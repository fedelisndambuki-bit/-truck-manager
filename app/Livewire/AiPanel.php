<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AIService;
use App\Services\FleetQueryService;
use App\Models\Company;
use App\Models\AiLog;
use App\Models\Truck;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiPanel extends Component
{
    public string $message = '';
    public array $history = [];
    public bool $showPanel = false;
    public bool $loading = false;
    public array $autoCompleteSuggestions = [];

    protected $listeners = ['focus-input' => 'focusInput'];

    public function togglePanel(): void
    {
        $this->showPanel = !$this->showPanel;
        if ($this->showPanel) $this->autoCompleteSuggestions = [];
    }

    public function clearHistory(): void
    {
        $this->history = [];
        $this->autoCompleteSuggestions = [];
    }

    public function refreshSession(): void
    {
        $user = auth()->user();
        if ($user) {
            $companyId = $user->company_id ?? ($user->company?->id ?? null);
            if ($companyId) {
                app(AIService::class)->clearContextCache($companyId);
            }
        }
        $this->clearHistory();
        $this->dispatch('notify', ['message' => 'AI Session Refreshed', 'type' => 'success']);
    }

    public function updatedMessage(): void
    {
        $input = trim($this->message);
        $this->autoCompleteSuggestions = [];

        if (strlen($input) < 2) return;

        $user = auth()->user();
        if (!$user) return;
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyId = $user->company_id ?? ($user->company?->id ?? null);

        // 1. Slash Command Suggestions
        if ($isSuperAdmin) {
            $cmds = ['/companies', '/plans', '/subscriptions', '/settings', '/system', '/logs', '/help'];
        } else {
            $cmds = match ($user->company?->industry) {
                'construction' => ['/projects', '/project', '/site', '/subcontractor', '/safety', '/materials', '/insights', '/help'],
                'quarry' => ['/production', '/dispatch', '/blast', '/stockpile', '/weighbridge', '/safety', '/insights', '/help'],
                default => ['/trips', '/truck', '/fuel', '/maintenance', '/driver', '/invoices', '/predict', '/fraud', '/insights', '/help'],
            };
        }

        foreach ($cmds as $c) {
            if (Str::startsWith($c, strtolower($input))) {
                $this->autoCompleteSuggestions[] = ['text' => $c, 'type' => 'command'];
            }
        }

        if ($isSuperAdmin) return; // Super Admin doesn't get truck/driver suggestions for now
    }

    public function selectSuggestion(string $text): void
    {
        $this->message = $text;
        $this->autoCompleteSuggestions = [];
    }

    public function quickAsk(string $text): void
    {
        $this->message = $text;
        $this->sendMessage();
    }

    public function rateResponse(int $index, int $rating): void
    {
        if (isset($this->history[$index]['log_id'])) {
            $log = AiLog::find($this->history[$index]['log_id']);
            if ($log) {
                $log->update(['rating' => $rating]);
                $this->history[$index]['rating'] = $rating;
            }
        }
    }

    public function sendMessage(): void
    {
        $this->validate(['message' => 'required|string|max:1000']);
        $user = auth()->user();
        if (!$user) return;

        $this->history[] = ['role' => 'user', 'content' => $this->message];
        $this->loading = true;
        $this->autoCompleteSuggestions = [];

        try {
            $isSuperAdmin = $user->hasRole('Super Admin');
            $companyId = $user->company_id ?? ($user->company?->id ?? null);
            if (!$companyId && !$isSuperAdmin) {
                $company = Company::query()->first() ?? Company::create([]);
                $user->company_id = $company->id;
                $user->save();
                $companyId = $company->id;
            }

            $industry = $user->company?->industry;
            $queryServiceClass = match ($industry) {
                'construction' => \App\Services\Construction\QueryService::class,
                'quarry' => \App\Services\Quarry\QueryService::class,
                default => FleetQueryService::class,
            };
            $fq = app($queryServiceClass);
            if ($isSuperAdmin) {
                $fastPathMapping = [
                    'total companies' => '/companies count',
                    'active subscriptions' => '/subscriptions active',
                    'system logs' => '/logs recent',
                    'platform settings' => '/settings list',
                ];
            } else {
                $fastPathMapping = match ($industry) {
                    'construction' => [
                        'active projects' => '/projects active',
                        'delayed projects' => '/projects delayed',
                        'safety incidents' => '/safety incidents-this-month',
                        'low stock' => '/materials wastage-this-month',
                    ],
                    'quarry' => [
                        'production today' => '/production today',
                        'dispatch queue' => '/dispatch queue',
                        'weighbridge queue' => '/weighbridge queue',
                        'low stockpiles' => '/stockpile low',
                        'safety incidents' => '/safety incidents',
                    ],
                    default => [
                        'active trips' => '/trips active',
                        'delayed trips' => '/trips delayed-today',
                        'maintenance due' => '/maintenance due',
                        'unpaid invoices' => '/invoices unpaid-this-month',
                        'fuel this month' => '/fuel this-month',
                    ],
                };
            }

            $msgLower = strtolower($this->message);
            foreach ($fastPathMapping as $key => $cmd) {
                if (str_contains($msgLower, $key)) {
                    $res = $fq->handle($companyId ?? 0, $cmd);
                    if ($res) {
                        $this->history[] = ['role' => 'ai', 'content' => $res, 'suggestions' => []];
                        $this->loading = false;
                        $this->message = '';
                        return;
                    }
                }
            }

            $maybe = $fq->handle($companyId ?? 0, $this->message);
            if ($maybe !== null) {
                $this->history[] = ['role' => 'ai', 'content' => $maybe, 'suggestions' => []];
                $this->loading = false;
                $this->message = '';
                return;
            }

            $ai = app(AIService::class);
            $result = $ai->processRequest($companyId ?? 0, $user->id, $this->message);

            $rawResponse = $result['success'] ? $result['response'] : ($result['error'] ?? 'AI service unavailable.');
            
            // Extract suggestions from AI response
            $content = $rawResponse;
            $aiSuggestions = [];
            if (str_contains($rawResponse, 'SUGGESTIONS:')) {
                $parts = explode('SUGGESTIONS:', $rawResponse);
                $content = trim($parts[0]);
                $aiSuggestions = array_map('trim', explode('|', $parts[1]));
            }

            $lastLog = AiLog::where('user_id', $user->id)->latest()->first();

            $this->history[] = [
                'role' => 'ai', 
                'content' => $content,
                'suggestions' => $aiSuggestions,
                'log_id' => $lastLog?->id,
                'rating' => null
            ];

        } catch (\Exception $e) {
            Log::error('AI Error: ' . $e->getMessage());
            $this->history[] = ['role' => 'ai', 'content' => 'Error: ' . $e->getMessage(), 'suggestions' => []];
        }

        $this->loading = false;
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.ai-panel');
    }
}
