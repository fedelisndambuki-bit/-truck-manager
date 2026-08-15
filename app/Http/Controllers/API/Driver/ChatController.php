<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Models\Driver;
use App\Models\DriverCompanyChatMessage;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $count = DriverCompanyChatMessage::query()
            ->where('driver_id', $driver->id)
            ->where('sender_type', 'company')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);
        $employee = $driver->employee;
        $this->markCompanyMessagesAsRead($driver->id);

        $messages = DriverCompanyChatMessage::query()
            ->where('driver_id', $driver->id)
            ->whereNull('deleted_for_driver_at')
            ->latest('id')
            ->take(200)
            ->get()
            ->reverse()
            ->values()
            ->map(function (DriverCompanyChatMessage $message) use ($employee, $driver) {
                $isDriver = $message->sender_type === 'driver';
                $deletedForEveryone = $message->deleted_for_everyone_at !== null;
                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'message' => $deletedForEveryone ? 'This message was deleted.' : $message->message,
                    'edited_at' => optional($message->edited_at)->toISOString(),
                    'deleted_for_everyone_at' => optional($message->deleted_for_everyone_at)->toISOString(),
                    'voice_note_url' => $deletedForEveryone
                        ? null
                        : ($message->voice_note_path
                        ? $this->publicAssetUrl($message->voice_note_path)
                        : null),
                    'attachment_url' => $deletedForEveryone
                        ? null
                        : ($message->attachment_path
                        ? $this->publicAssetUrl($message->attachment_path)
                        : null),
                    'attachment_name' => $deletedForEveryone ? null : $message->attachment_name,
                    'attachment_mime' => $deletedForEveryone ? null : $message->attachment_mime,
                    'read_at' => optional($message->read_at)->toISOString(),
                    'created_at' => optional($message->created_at)->toISOString(),
                    'sender_name' => $isDriver
                        ? ($employee?->full_name ?: ($driver->user->name ?? 'Driver'))
                        : ($message->senderUser?->name ?? 'Company'),
                    'sender_profile_url' => $isDriver
                        ? $this->driverProfileUrl($employee)
                        : $this->companySenderProfileUrl($message, $driver->company_id),
                ];
            });

        return response()->json([
            'data' => $messages,
            'meta' => [
                'driver_name' => $employee?->full_name ?: ($driver->user->name ?? 'Driver'),
                'driver_profile_url' => $this->driverProfileUrl($employee),
                'company_profile_url' => $this->companyProfileUrl($driver->company_id),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $driver = $this->getDriver($request);

        $data = $request->validate([
            'message' => 'nullable|string|max:2000',
            'voice_note' => 'nullable|file|max:10240',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $message = trim((string) ($data['message'] ?? ''));
        $voiceNotePath = $request->hasFile('voice_note')
            ? $request->file('voice_note')->store('driver/chat-voice-notes', 'public')
            : null;
        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('driver/chat-attachments', 'public')
            : null;
        $attachmentName = $request->hasFile('attachment')
            ? $request->file('attachment')->getClientOriginalName()
            : null;
        $attachmentMime = $request->hasFile('attachment')
            ? $request->file('attachment')->getClientMimeType()
            : null;

        if ($message === '' && $voiceNotePath === null && $attachmentPath === null) {
            return response()->json([
                'message' => 'Message text, voice note, or attachment is required.',
            ], 422);
        }

        $chatMessage = DriverCompanyChatMessage::create([
            'company_id' => $driver->company_id,
            'driver_id' => $driver->id,
            'sender_type' => 'driver',
            'sender_user_id' => $request->user()->id,
            'message' => $message === ''
                ? ($voiceNotePath ? '[Voice Note]' : '[Attachment]')
                : $message,
            'voice_note_path' => $voiceNotePath,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);

        return response()->json([
            'message' => 'Message sent.',
            'data' => [
                'id' => $chatMessage->id,
            ],
        ], 201);
    }

    public function edit(Request $request, DriverCompanyChatMessage $message): JsonResponse
    {
        $driver = $this->getDriver($request);
        abort_unless($message->driver_id === $driver->id, 404);
        abort_unless($message->sender_type === 'driver', 403);
        abort_unless($message->deleted_for_everyone_at === null, 422, 'Message already deleted.');

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $text = trim($data['message']);
        if ($text === '') {
            return response()->json(['message' => 'Message cannot be empty.'], 422);
        }

        $message->update([
            'message' => $text,
            'edited_at' => now(),
        ]);

        return response()->json(['message' => 'Message updated.']);
    }

    public function delete(Request $request, DriverCompanyChatMessage $message): JsonResponse
    {
        $driver = $this->getDriver($request);
        abort_unless($message->driver_id === $driver->id, 404);

        $data = $request->validate([
            'scope' => 'required|in:me,everyone',
        ]);

        if ($data['scope'] === 'everyone') {
            abort_unless($message->sender_type === 'driver', 403);
            $message->update([
                'deleted_for_everyone_at' => now(),
                'edited_at' => null,
                'voice_note_path' => null,
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
                'message' => 'This message was deleted.',
            ]);

            return response()->json(['message' => 'Message deleted for everyone.']);
        }

        $message->update([
            'deleted_for_driver_at' => now(),
        ]);

        return response()->json(['message' => 'Message deleted for you.']);
    }

    private function getDriver(Request $request): Driver
    {
        return Driver::where('user_id', $request->user()->id)->firstOrFail();
    }

    private function markCompanyMessagesAsRead(int $driverId): void
    {
        DriverCompanyChatMessage::query()
            ->where('driver_id', $driverId)
            ->where('sender_type', 'company')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function driverProfileUrl(?Employee $employee): ?string
    {
        if (! $employee?->profile_photo) {
            return null;
        }
        return $this->publicAssetUrl($employee->profile_photo);
    }

    private function companySenderProfileUrl(DriverCompanyChatMessage $message, int $companyId): ?string
    {
        $logo = $this->companyProfileUrl($companyId);
        if ($logo) {
            return $logo;
        }

        return null;
    }

    private function companyProfileUrl(int $companyId): ?string
    {
        $setting = BrandSetting::query()->where('company_id', $companyId)->first();
        if ($setting?->logo_path) {
            return $this->publicAssetUrl($setting->logo_path);
        }
        if ($setting?->icon_path) {
            return $this->publicAssetUrl($setting->icon_path);
        }
        return null;
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        $url = Storage::disk('public')->url($normalized);
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }
}
