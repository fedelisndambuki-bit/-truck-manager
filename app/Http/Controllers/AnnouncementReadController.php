<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementReadController extends Controller
{
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        AnnouncementRead::query()->updateOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user?->id,
                'device_id' => null,
            ],
            [
                'read_at' => now(),
            ]
        );

        return back();
    }
}
