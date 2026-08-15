<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Company;
use App\Models\Truck;
use App\Models\Trip;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class AIService
{
    private const TIMEOUT = 45;

    public function processRequest(int $companyId, int $userId, string $question): array
    {
        $startTime = microtime(true);
        $user = \App\Models\User::find($userId);
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;
        
        Log::info('AI REQUEST ROLE DETECTION', [
            'user_id' => $userId,
            'is_super_admin' => $isSuperAdmin,
            'roles' => $user?->getRoleNames() ?? []
        ]);

        try {
            if ($isSuperAdmin) {
                $contextData = $this->getSuperAdminSummary();
            } else {
                $contextData = $this->getFleetSummary($companyId);
                $enriched = $this->enrichContext($question, $companyId);
                if (!empty($enriched)) {
                    $contextData['focused_context'] = $enriched;
                }
            }

            $prompt = $this->buildPrompt($question, $contextData, $isSuperAdmin);
            $response = $this->callOllama($prompt);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            $this->logAiRequest($companyId, $userId, $question, $response, $contextData, $responseTime, 'success');
            
            return [
                'success' => true,
                'response' => $response,
                'response_time_ms' => $responseTime,
                'fleet_data' => $contextData
            ];
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logAiRequest($companyId, $userId, $question, null, null, $responseTime, 'error', $e->getMessage());
            return [
                'success' => false,
                'error' => 'AI Service Error: ' . $e->getMessage(),
                'response_time_ms' => $responseTime
            ];
        }
    }

    private function getSuperAdminSummary(): array
    {
        $cacheKey = "super_admin_summary";
        return Cache::remember($cacheKey, 300, function () {
            return [
                'role' => 'Super Admin',
                'total_companies' => \App\Models\Company::count(),
                'total_users' => \App\Models\User::count(),
                'total_plans' => \App\Models\Plan::count(),
                'total_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
                'platform_settings' => \App\Models\PlatformSetting::count(),
                'active_modules' => \App\Models\Module::where('is_active', true)->count(),
                'recent_logs' => \App\Models\AiLog::count(),
                'generated_at' => now()->toIso8601String()
            ];
        });
    }

    public function generateDailyInsights(int $companyId, int $userId): array
    {
        $startTime = microtime(true);
        $user = \App\Models\User::find($userId);
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;

        try {
            if ($isSuperAdmin) {
                $contextData = $this->getSuperAdminSummary();
                $question = 'Generate a platform-wide health summary and 2 operational suggestions for the Super Admin.';
            } else {
                $contextData = $this->getFleetSummary($companyId);
                $question = 'Generate today\'s fleet performance summary and 2 suggestions.';
            }

            $prompt = $this->buildPrompt($question, $contextData, $isSuperAdmin);
            $response = $this->callOllama($prompt, true);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logAiRequest($companyId, $userId, '[auto] daily_insights', $response, $contextData, $responseTime, 'success');
            return [
                'success' => true,
                'response' => $response,
                'response_time_ms' => $responseTime,
                'fleet_data' => $contextData,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'AI unavailable.'];
        }
    }

    private function getFleetSummary(int $companyId): array
    {
        $cacheKey = "fleet_summary_{$companyId}";
        return Cache::remember($cacheKey, 300, function () use ($companyId) {
            $company = Company::find($companyId);
            return [
                'role' => 'Fleet Admin',
                'company_name' => $company->name ?? 'Your Company',
                'total_trucks' => Truck::where('company_id', $companyId)->count(),
                'active_trips' => Trip::where('company_id', $companyId)->whereIn('status', ['In Progress', 'Scheduled'])->count(),
                'fuel_this_month' => (float) FuelLog::where('company_id', $companyId)->whereMonth('date', now()->month)->sum('quantity_litres'),
                'maintenance_pending' => Maintenance::where('company_id', $companyId)->where('status', 'Pending')->count(),
                'generated_at' => now()->toIso8601String()
            ];
        });
    }

    private function enrichContext(string $question, int $companyId): array
    {
        $context = [];
        if (preg_match_all('/\b[Kk][A-Za-z]{2}[-\s]?\d{3}[A-Za-z]?\b/i', $question, $matches)) {
            foreach (array_unique($matches[0]) as $plate) {
                $normalized = str_replace([' ', '-'], '', strtoupper($plate));
                $truck = Truck::where('company_id', $companyId)
                    ->where(function($q) use ($plate, $normalized) {
                        $q->where('plate_number', 'LIKE', "%{$plate}%")->orWhere('plate_number', 'LIKE', "%{$normalized}%");
                    })->first();
                if ($truck) {
                    $context['trucks'][$truck->plate_number] = [
                        'status' => ($truck->status instanceof \BackedEnum) ? $truck->status->value : $truck->status,
                        'driver' => $truck->driver?->full_name ?? 'Unassigned',
                        'mileage' => $truck->current_mileage,
                        'last_trip' => Trip::where('truck_id', $truck->id)->latest()->first()?->route_name ?? 'None'
                    ];
                }
            }
        }
        return $context;
    }

    private function buildPrompt(string $userQuestion, array $contextData, bool $isSuperAdmin = false): string
    {
        return $this->getSystemPrompt($contextData, $isSuperAdmin) . "\n\nUser Question: " . trim($userQuestion);
    }

    private function getSystemPrompt(array $contextData, bool $isSuperAdmin = false): string
    {
        $contextJson = json_encode($contextData, JSON_PRETTY_PRINT);
        $date = now()->format('l, F j, Y H:i');
        
        if ($isSuperAdmin) {
            return <<<PROMPT
You are the Super Admin Intelligence Assistant for the Truck Manager Platform.
Current Date: {$date}
Platform Global Data: {$contextJson}

Guidelines:
1. You provide high-level insights across all companies, plans, and system modules.
2. Be extremely concise. Use bullet points for stats.
3. Your role is platform health, scaling, and billing oversight.
4. AT THE END of your response, provide exactly 2 short follow-up questions for a Super Admin.
   Format: SUGGESTIONS: [Question 1] | [Question 2]
PROMPT;
        }

        return <<<PROMPT
You are a fast, professional Fleet AI Assistant for {$contextData['company_name']}.
Current Date: {$date}
Fleet Data: {$contextJson}

Guidelines:
1. Be extremely concise. Use bullet points for stats.
2. If data is in 'focused_context', prioritize it.
3. If you can't find specific data, tell the user to use a command (e.g., /trips, /fuel).
4. AT THE END of your response, provide exactly 2 short follow-up questions.
   Format: SUGGESTIONS: [Question 1] | [Question 2]
PROMPT;
    }

    private function callOllama(string $prompt, bool $isLongResponse = false): string
    {
        $ollamaUrl = Config::get('services.ollama.url');
        $ollamaModel = Config::get('services.ollama.model');

        $response = Http::timeout(self::TIMEOUT)->post($ollamaUrl, [
            'model' => $ollamaModel,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => $isLongResponse ? 1000 : 400,
                'temperature' => 0.2,
            ]
        ]);

        if (!$response->successful()) throw new \Exception('Ollama error: ' . $response->status());
        return $response->json('response') ?? 'AI unavailable.';
    }

    public function clearFleetCache(int $companyId): void
    {
        Cache::forget("fleet_summary_{$companyId}");
    }

    private function logAiRequest($companyId, $userId, $question, $response, $fleetData, $responseTime, $status, $errorMessage = null): void {
        AiLog::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'question' => $question,
            'response' => $response,
            'fleet_data' => $fleetData,
            'response_time_ms' => $responseTime,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
