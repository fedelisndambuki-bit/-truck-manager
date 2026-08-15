<?php

namespace App\Filament\Resources\DriverChatMessageResource\Pages;

use App\Filament\Resources\DriverChatMessageResource;
use App\Models\Driver;
use App\Models\DriverCompanyChatMessage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;

class ListDriverChatMessages extends ListRecords
{
    use WithFileUploads;

    protected static string $resource = DriverChatMessageResource::class;

    protected static string $view = 'filament.resources.driver-chat-message-resource.pages.list-driver-chat-messages';

    protected static ?string $title = 'Chat';

    protected ?string $subheading = 'Direct conversation between company and drivers';

    public ?int $selectedDriverId = null;

    public string $message = '';
    public $voiceNote = null;
    public $attachment = null;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'title' => 'Driver Chat',
            'subtitle' => 'Direct messaging and calls between your company and drivers.',
        ]);
    }

    public function mount(): void
    {
        parent::mount();

        if (! $this->selectedDriverId) {
            $firstDriver = $this->getScopedDrivers()->first();
            $this->selectedDriverId = $firstDriver?->id;
        }
    }

    public function getScopedDrivers(): Collection
    {
        $user = auth()->user();

        return Driver::query()
            ->when($user, fn ($q) => $q->where('company_id', $user->company_id))
            ->with('user')
            ->orderBy('id')
            ->get();
    }

    public function getChatMessages(): Collection
    {
        if (! $this->selectedDriverId) {
            return collect();
        }

        return DriverCompanyChatMessage::query()
            ->where('driver_id', $this->selectedDriverId)
            ->whereNull('deleted_for_company_at')
            ->orderBy('id')
            ->with(['senderUser', 'driver.user'])
            ->get();
    }

    public function getCallStateProperty(): array
    {
        return $this->buildCallState($this->getChatMessages());
    }

    public function sendMessage(): void
    {
        if (! $this->selectedDriverId) {
            return;
        }

        $message = trim($this->message);
        if ($message === '' && ! $this->voiceNote && ! $this->attachment) {
            return;
        }

        $driver = Driver::query()->find($this->selectedDriverId);
        if (! $driver) {
            return;
        }

        $voiceNotePath = $this->voiceNote
            ? $this->voiceNote->store('driver/chat-voice-notes', 'public')
            : null;
        $attachmentPath = $this->attachment
            ? $this->attachment->store('driver/chat-attachments', 'public')
            : null;

        DriverCompanyChatMessage::create([
            'company_id' => $driver->company_id,
            'driver_id' => $driver->id,
            'sender_type' => 'company',
            'sender_user_id' => auth()->id(),
            'message' => $message === ''
                ? ($voiceNotePath ? '[Voice Note]' : '[Attachment]')
                : $message,
            'voice_note_path' => $voiceNotePath,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $this->attachment?->getClientOriginalName(),
            'attachment_mime' => $this->attachment?->getMimeType(),
        ]);

        $this->message = '';
        $this->voiceNote = null;
        $this->attachment = null;

        Notification::make()
            ->title('Message sent')
            ->success()
            ->send();
    }

    public function sendCallInvite(): void
    {
        if (! $this->selectedDriverId) {
            return;
        }

        $driver = Driver::query()->find($this->selectedDriverId);
        if (! $driver) {
            return;
        }

        $room = 'ShiftHubDriver' . now()->timestamp . random_int(100, 999);
        $callId = (string) now()->timestamp . random_int(1000, 9999);
        $link = route('calls.room', ['room' => $room]) . '?role=caller&call_id=' . $callId;
        $this->sendCallEvent($driver, [
            'type' => 'request',
            'call_id' => $callId,
            'url' => $link,
            'from' => 'company',
        ]);

        Notification::make()
            ->title('Call invite sent')
            ->success()
            ->send();
    }

    public function acceptIncomingCall(string $callId): void
    {
        $driver = Driver::query()->find($this->selectedDriverId);
        if (! $driver) {
            return;
        }

        $this->sendCallEvent($driver, [
            'type' => 'accept',
            'call_id' => $callId,
            'from' => 'company',
        ]);
    }

    public function declineIncomingCall(string $callId): void
    {
        $driver = Driver::query()->find($this->selectedDriverId);
        if (! $driver) {
            return;
        }

        $this->sendCallEvent($driver, [
            'type' => 'decline',
            'call_id' => $callId,
            'from' => 'company',
        ]);
    }

    public function endActiveCall(string $callId): void
    {
        $driver = Driver::query()->find($this->selectedDriverId);
        if (! $driver) {
            return;
        }

        $this->sendCallEvent($driver, [
            'type' => 'end',
            'call_id' => $callId,
            'from' => 'company',
        ]);
    }

    public function markOpened(int $messageId): void
    {
        $message = DriverCompanyChatMessage::query()
            ->whereKey($messageId)
            ->where('sender_type', 'driver')
            ->first();

        if (! $message) {
            return;
        }

        $message->update(['read_at' => now()]);
    }

    public function editCompanyMessage(int $messageId, string $text): void
    {
        $message = DriverCompanyChatMessage::query()
            ->whereKey($messageId)
            ->where('driver_id', $this->selectedDriverId)
            ->where('sender_type', 'company')
            ->whereNull('deleted_for_everyone_at')
            ->first();

        if (! $message) {
            return;
        }

        $trimmed = trim($text);
        if ($trimmed === '') {
            return;
        }

        $message->update([
            'message' => $trimmed,
            'edited_at' => now(),
        ]);
    }

    public function deleteCompanyMessage(int $messageId, string $scope): void
    {
        $message = DriverCompanyChatMessage::query()
            ->whereKey($messageId)
            ->where('driver_id', $this->selectedDriverId)
            ->first();

        if (! $message) {
            return;
        }

        if ($scope === 'everyone') {
            if ($message->sender_type !== 'company') {
                return;
            }
            $message->update([
                'deleted_for_everyone_at' => now(),
                'edited_at' => null,
                'voice_note_path' => null,
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_mime' => null,
                'message' => 'This message was deleted.',
            ]);
            return;
        }

        $message->update([
            'deleted_for_company_at' => now(),
        ]);
    }

    private function sendCallEvent(Driver $driver, array $event): void
    {
        DriverCompanyChatMessage::create([
            'company_id' => $driver->company_id,
            'driver_id' => $driver->id,
            'sender_type' => 'company',
            'sender_user_id' => auth()->id(),
            'message' => 'CALL_EVENT::' . json_encode($event),
        ]);
    }

    private function buildCallState(Collection $messages): array
    {
        $calls = [];

        foreach ($messages as $message) {
            $event = $this->parseCallEvent((string) $message->message);
            if (! $event) {
                continue;
            }

            $callId = (string) ($event['call_id'] ?? '');
            if ($callId === '') {
                continue;
            }

            $calls[$callId] ??= [
                'call_id' => $callId,
                'url' => null,
                'request_sender' => null,
                'request_id' => 0,
                'accepted' => false,
                'declined' => false,
                'ended' => false,
            ];

            if (($event['type'] ?? '') === 'request') {
                $calls[$callId]['url'] = $event['url'] ?? null;
                $calls[$callId]['request_sender'] = $message->sender_type;
                $calls[$callId]['request_id'] = max($calls[$callId]['request_id'], (int) $message->id);
            }
            if (($event['type'] ?? '') === 'accept') {
                $calls[$callId]['accepted'] = true;
            }
            if (($event['type'] ?? '') === 'decline') {
                $calls[$callId]['declined'] = true;
            }
            if (($event['type'] ?? '') === 'end') {
                $calls[$callId]['ended'] = true;
            }
        }

        if (empty($calls)) {
            return ['incoming' => null, 'outgoing' => null, 'active' => null];
        }

        uasort($calls, fn ($a, $b) => ($b['request_id'] <=> $a['request_id']));

        $incoming = null;
        $outgoing = null;
        $active = null;

        foreach ($calls as $call) {
            $isClosed = $call['declined'] || $call['ended'];
            if ($isClosed) {
                continue;
            }

            if ($call['accepted'] && ! $active) {
                $active = $call;
                continue;
            }

            if (! $call['accepted']) {
                if ($call['request_sender'] === 'driver' && ! $incoming) {
                    $incoming = $call;
                }
                if ($call['request_sender'] === 'company' && ! $outgoing) {
                    $outgoing = $call;
                }
            }
        }

        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'active' => $active,
        ];
    }

    private function parseCallEvent(string $message): ?array
    {
        if (! str_starts_with($message, 'CALL_EVENT::')) {
            return null;
        }

        $json = substr($message, 12);
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
