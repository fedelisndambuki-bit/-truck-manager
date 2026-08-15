<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAIRequest;
use App\Models\AiLog;
use App\Policies\AIPolicy;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AIController extends Controller
{
    /**
     * Ask AI a question about fleet management
     */
    public function ask(Request $request): JsonResponse
    {
        // Check if user has permission to access AI
        Gate::authorize('accessAI', AIPolicy::class);

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:5|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid question format',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $companyId = $user->company_id ?? ($user->company?->id ?? null);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company association is required to use the AI assistant.'
            ], 422);
        }

        $company = $user->company;
        if ($company && ! app(PlanLimitService::class)->canUseAi($company)) {
            return response()->json([
                'success' => false,
                'message' => 'AI request limit reached for your current plan.'
            ], 429);
        }
        $question = $request->input('question');
        $jobId = Str::uuid()->toString();

        // Dispatch the AI request job
        ProcessAIRequest::dispatch(
            $companyId,
            $user->id,
            $question,
            $jobId
        );

        return response()->json([
            'success' => true,
            'message' => 'Your question is being processed',
            'job_id' => $jobId,
            'estimated_wait_time' => '30-60 seconds'
        ]);
    }

    /**
     * Get the result of an AI request
     */
    public function getResult(Request $request): JsonResponse
    {
        Gate::authorize('accessAI', AIPolicy::class);

        $validator = Validator::make($request->all(), [
            'job_id' => 'required|string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid job ID',
                'errors' => $validator->errors()
            ], 422);
        }

        $jobId = $request->input('job_id');
        $user = Auth::user();
        $companyId = $user->company_id ?? ($user->company?->id ?? null);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company association is required to retrieve AI results.'
            ], 422);
        }

        $result = cache()->get("ai_response_{$companyId}_{$user->id}_{$jobId}");

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found or expired',
                'status' => 'not_found'
            ], 404);
        }

        // Clear the cache after retrieval
        cache()->forget("ai_response_{$companyId}_{$user->id}_{$jobId}");

        return response()->json($result);
    }

    /**
     * Get AI logs for the company
     */
    public function logs(Request $request): JsonResponse
    {
        Gate::authorize('viewLogs', AIPolicy::class);

        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'status' => 'nullable|in:success,error',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $query = AiLog::where('company_id', $user->company_id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Get total count
        $total = $query->count();

        // Apply pagination
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $logs = $query->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name,
                    'question' => $log->question,
                    'response_preview' => Str::limit(strip_tags($log->response), 150),
                    'status' => $log->status,
                    'response_time_ms' => $log->response_time_ms,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Get detailed AI log entry
     */
    public function logDetail(int $id): JsonResponse
    {
        Gate::authorize('viewLogs', AIPolicy::class);

        $user = Auth::user();
        $log = AiLog::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with('user:id,name,email')
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log entry not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'user' => [
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ],
                'question' => $log->question,
                'response' => $log->response,
                'fleet_data' => $log->fleet_data,
                'status' => $log->status,
                'response_time_ms' => $log->response_time_ms,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->toISOString(),
            ]
        ]);
    }

    /**
     * Delete AI logs
     */
    public function deleteLogs(Request $request): JsonResponse
    {
        Gate::authorize('deleteLogs', AIPolicy::class);

        $validator = Validator::make($request->all(), [
            'log_ids' => 'required|array|min:1',
            'log_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid log IDs',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $deletedCount = AiLog::where('company_id', $user->company_id)
            ->whereIn('id', $request->input('log_ids'))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} log entries",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get AI usage statistics
     */
    public function stats(): JsonResponse
    {
        Gate::authorize('viewLogs', AIPolicy::class);

        $user = Auth::user();
        $companyId = $user->company_id;

        $stats = [
            'total_requests' => AiLog::where('company_id', $companyId)->count(),
            'successful_requests' => AiLog::where('company_id', $companyId)->where('status', 'success')->count(),
            'failed_requests' => AiLog::where('company_id', $companyId)->where('status', 'error')->count(),
            'avg_response_time' => AiLog::where('company_id', $companyId)
                ->where('status', 'success')
                ->avg('response_time_ms'),
            'requests_this_month' => AiLog::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'top_users' => AiLog::join('users', 'ai_logs.user_id', '=', 'users.id')
                ->where('ai_logs.company_id', $companyId)
                ->selectRaw('users.name, COUNT(ai_logs.id) as request_count')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('request_count')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
