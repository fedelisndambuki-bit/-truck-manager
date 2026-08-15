<?php

namespace App\Filament\Resources\DriverChatMessageResource\Pages;

use App\Filament\Resources\DriverChatMessageResource;
use App\Models\Driver;
use Filament\Resources\Pages\CreateRecord;

class CreateDriverChatMessage extends CreateRecord
{
    protected static string $resource = DriverChatMessageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $driver = Driver::query()->findOrFail($data['driver_id']);
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '') {
            $message = filled($data['voice_note_path'] ?? null)
                ? '[Voice Note]'
                : '[Attachment]';
        }

        $data['company_id'] = $driver->company_id ?: $user?->company_id;
        $data['sender_type'] = 'company';
        $data['sender_user_id'] = auth()->id();
        $data['message'] = $message;
        $data['attachment_name'] = filled($data['attachment_path'] ?? null)
            ? basename((string) $data['attachment_path'])
            : null;

        return $data;
    }
}
