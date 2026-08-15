<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use App\Services\PlanLimitService;
use Illuminate\Routing\Controller;

class AIController extends Controller
{
    public function ask(Request $request, AIService $aiService)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'module' => 'required|string'
        ]);

        $user = auth()->user();
        abort_unless($user->can('use ai assistant'), 403);

        $companyId = $user->company_id ?? ($user->company?->id ?? null);
        if (!$companyId) {
            return response()->json([
                'message' => 'Company association is required to use the AI assistant.'
            ], 422);
        }

        $company = $user->company;
        if ($company && ! app(PlanLimitService::class)->canUseAi($company)) {
            return response()->json([
                'message' => 'AI request limit reached for your current plan.'
            ], 429);
        }

        $result = $aiService->processRequest(
            $companyId,
            $user->id,
            $request->message
        );

        $answer = $result['success']
            ? $result['response']
            : ($result['error'] ?? 'AI service temporarily unavailable.');

        return response()->json([
            'reply' => $answer
        ]);
    }
}
