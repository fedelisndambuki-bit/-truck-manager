<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->company_id;
        $deviceId = (string) $request->query('device_id', '');

        $query = Announcement::query()
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->where('audience', 'all')
                    ->orWhere(function ($inner) use ($companyId) {
                        $inner->where('audience', 'company')
                            ->where('target_company_id', $companyId);
                    });
            })
            ->where(function ($q) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->limit(30);

        $announcements = $query->get(['id', 'title', 'message', 'audience', 'active_from', 'expires_at', 'created_at']);

        $readIds = AnnouncementRead::query()
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->where(function ($q) use ($user, $deviceId) {
                $q->where('user_id', $user?->id);
                if ($deviceId !== '') {
                    $q->orWhere('device_id', $deviceId);
                }
            })
            ->pluck('announcement_id')
            ->all();

        return response()->json([
            'data' => $announcements->map(function ($announcement) use ($readIds) {
                return array_merge($announcement->toArray(), [
                    'is_read' => in_array($announcement->id, $readIds, true),
                ]);
            })->values(),
        ]);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        $deviceId = (string) $request->input('device_id', '');

        AnnouncementRead::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user?->id,
                'device_id' => $deviceId !== '' ? $deviceId : null,
            ],
            [
                'read_at' => now(),
            ]
        );

        return response()->json(['message' => 'Announcement marked as read.']);
    }
}
