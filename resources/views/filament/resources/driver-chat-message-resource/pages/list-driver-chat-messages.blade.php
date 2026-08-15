<x-filament-panels::page>
    <style>
        .chat-wrap{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .chat-top{background:#2f4f4f;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between}
        .chat-title{font-size:15px;font-weight:600;color:#f9fafb;display:flex;align-items:center;gap:8px}
        .chat-results{font-size:13px;color:rgba(249,250,251,.9)}
        .chat-results strong{color:#fff}
        .chat-body{height:560px;overflow:auto;padding:14px;background:#efeae2;position:relative}
        .chat-bubble{max-width:62%;padding:8px 10px;border-radius:9px;margin-bottom:8px;font-size:12px;line-height:1.3}
        .chat-bubble.driver{background:#fff}
        .chat-bubble.company{background:#d9fdd3}
        .chat-row{display:flex}
        .chat-row.driver{justify-content:flex-start}
        .chat-row.company{justify-content:flex-end}
        .chat-meta{margin-top:6px;font-size:11px;color:#6b7280;display:flex;gap:8px;justify-content:flex-end}
        .chat-input{padding:12px 16px;border-top:1px solid #e5e7eb;background:#fff}
        .chat-file-chip{margin-top:6px;display:inline-flex;align-items:center;gap:6px;font-size:11px;background:rgba(0,0,0,.05);padding:4px 8px;border-radius:999px}
        .chat-tools{display:flex;align-items:center;gap:10px}
        .chat-tool-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151}
        .chat-tool-btn:hover{background:#f3f4f6}
        .emoji-panel{display:none;max-height:180px;overflow:auto;border:1px solid #d1d5db;border-radius:8px;padding:8px;background:#fff;margin-top:8px}
        .emoji-btn{border:0;background:transparent;font-size:18px;cursor:pointer;line-height:1.2;padding:3px}
    </style>

    <div class="chat-wrap" wire:poll.2s>
        @php $messages = $this->getChatMessages(); @endphp
        @php $callState = $this->callState; @endphp

        <div class="chat-top">
            <div class="flex items-center gap-3">
                <div class="chat-title">
                    <x-heroicon-o-chat-bubble-left-right style="width:16px;height:16px;" />
                    Conversation
                </div>
                <div class="chat-results">
                    <strong>{{ $messages->count() }}</strong> messages
                </div>
            </div>
            <div class="w-64">
                <select id="driver-select" name="selectedDriverId" wire:model.live="selectedDriverId" class="w-full rounded-md border-gray-300 text-sm text-gray-800">
                    @foreach ($this->getScopedDrivers() as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->user->name ?? ('Driver #' . $driver->id) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="chat-body">
            @if(!empty($callState['active']) && !empty($callState['active']['url']))
                @php
                    $activeBase = $callState['active']['url'];
                    $activeRole = ($callState['active']['request_sender'] ?? '') === 'company' ? 'caller' : 'callee';
                    $activeUrl = str_contains($activeBase, '?')
                        ? ($activeBase . '&role=' . $activeRole . '&call_id=' . $callState['active']['call_id'])
                        : ($activeBase . '?role=' . $activeRole . '&call_id=' . $callState['active']['call_id']);
                @endphp
                <div class="mb-3 rounded-md border border-gray-300 bg-white p-2">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-semibold text-gray-800">Call In Progress</div>
                        <button
                            type="button"
                            wire:click="endActiveCall('{{ $callState['active']['call_id'] }}')"
                            class="px-3 py-1 text-xs rounded bg-red-600 text-white"
                        >
                            End
                        </button>
                    </div>
                    <iframe
                        src="{{ $activeUrl }}"
                        allow="camera; microphone; fullscreen; display-capture"
                        style="width:100%;height:320px;border:0;border-radius:8px;"
                    ></iframe>
                </div>
            @endif

            @if(!empty($callState['incoming']) && !empty($callState['incoming']['url']))
                <div id="incoming-call-banner" class="mb-3 rounded-md border border-amber-300 bg-amber-50 p-3">
                    <div class="text-sm font-semibold text-amber-800 mb-2">Incoming Call</div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="acceptIncomingCall('{{ $callState['incoming']['call_id'] }}')"
                            class="px-3 py-1 text-xs rounded bg-green-600 text-white"
                        >
                            Accept
                        </button>
                        <button
                            type="button"
                            wire:click="declineIncomingCall('{{ $callState['incoming']['call_id'] }}')"
                            class="px-3 py-1 text-xs rounded bg-red-600 text-white"
                        >
                            Decline
                        </button>
                    </div>
                </div>
            @endif

            @forelse ($messages as $message)
                @php
                    $isDriver = $message->sender_type === 'driver';
                    $senderName = $isDriver
                        ? ($message->driver?->user?->name ?? 'Driver')
                        : ($message->senderUser?->name ?? 'Company');
                    $raw = (string) $message->message;
                    $eventLabel = null;
                    if (str_starts_with($raw, 'CALL_EVENT::')) {
                        $event = json_decode(substr($raw, 12), true);
                        $eventType = is_array($event) ? ($event['type'] ?? null) : null;
                        $eventLabel = match ($eventType) {
                            'request' => 'Started a call',
                            'accept' => 'Accepted the call',
                            'decline' => 'Declined the call',
                            'end' => 'Ended the call',
                            default => 'Call event',
                        };
                    }
                @endphp
                <div class="chat-row {{ $isDriver ? 'driver' : 'company' }}">
                    <div class="chat-bubble {{ $isDriver ? 'driver' : 'company' }}">
                        <div class="text-xs text-gray-500 mb-1">{{ $senderName }}</div>
                        <div>{{ $eventLabel ?: $raw }}</div>
                        @if($message->edited_at)
                            <div class="text-[10px] text-gray-500 mt-1">(edited)</div>
                        @endif
                        @if($message->voice_note_path)
                            <div class="chat-file-chip">
                                <x-heroicon-o-microphone style="width:14px;height:14px;" />
                                <audio controls preload="none" style="height:26px;max-width:220px;">
                                    <source src="{{ Storage::disk('public')->url($message->voice_note_path) }}">
                                </audio>
                            </div>
                        @endif
                        @if($message->attachment_path)
                            <div class="chat-file-chip">
                                <x-heroicon-o-paper-clip style="width:14px;height:14px;" />
                                <a href="{{ Storage::disk('public')->url($message->attachment_path) }}" target="_blank" class="text-blue-700 hover:underline">
                                    {{ $message->attachment_name ?: 'Attachment' }}
                                </a>
                            </div>
                        @endif
                        <div class="chat-meta">
                            @if($message->sender_type === 'company' && !str_starts_with((string)$message->message, 'CALL_EVENT::'))
                                <button
                                    type="button"
                                    class="text-blue-600 hover:text-blue-500"
                                    onclick="(function(){ const t = prompt('Edit message', @js((string)$message->message)); if(t !== null){ $wire.editCompanyMessage({{ $message->id }}, t); } })()"
                                >Edit</button>
                                <button
                                    type="button"
                                    class="text-red-600 hover:text-red-500"
                                    onclick="if(confirm('Delete for everyone? OK = everyone, Cancel = for me only')) { $wire.deleteCompanyMessage({{ $message->id }}, 'everyone'); } else { $wire.deleteCompanyMessage({{ $message->id }}, 'me'); }"
                                >Delete</button>
                            @endif
                            <span>{{ optional($message->created_at)->format('M d, H:i') }}</span>
                            @if(! $isDriver)
                                @if($message->read_at)
                                    <span class="text-emerald-600">✓✓</span>
                                @else
                                    <span class="text-gray-500">✓</span>
                                @endif
                            @else
                                @if($message->read_at)
                                    <span class="text-emerald-600">✓✓</span>
                                @else
                                    <button type="button" wire:click="markOpened({{ $message->id }})" class="text-blue-600 hover:text-blue-500">✓</button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 mt-16">No chat messages yet.</div>
            @endforelse
        </div>

        <div class="chat-input">
            <form wire:submit="sendMessage" class="flex items-center gap-3">
                <label class="chat-tool-btn" title="Upload voice note file">
                    <x-heroicon-o-microphone style="width:16px;height:16px;" />
                    <input wire:model="voiceNote" type="file" accept="audio/*" capture class="hidden" />
                </label>
                <label class="chat-tool-btn" title="Attach file">
                    <x-heroicon-o-paper-clip style="width:16px;height:16px;" />
                    <input wire:model="attachment" type="file" class="hidden" />
                </label>
                <button type="button" wire:click="sendCallInvite" class="chat-tool-btn" title="Call driver">
                    <x-heroicon-o-phone style="width:16px;height:16px;" />
                </button>
                <button type="button" id="emoji-toggle-btn" class="chat-tool-btn" title="Emoji">
                    <x-heroicon-o-face-smile style="width:16px;height:16px;" />
                </button>
                <input
                    id="chat-message-input"
                    wire:model="message"
                    type="text"
                    class="flex-1 rounded-md border-gray-300 text-sm"
                    placeholder="Type message to selected driver..."
                />
                <button type="submit" class="px-4 py-2 rounded-md text-white text-sm font-medium" style="background:#2f4f4f;">
                    Send
                </button>
            </form>
            <div id="emoji-panel" class="emoji-panel"></div>
            <div class="chat-tools mt-2 text-xs text-gray-500">
                <span>Web: choose/record audio, then press Send. Mobile: direct record/send.</span>
                @if($voiceNote)
                    <span>Voice: {{ method_exists($voiceNote, 'getClientOriginalName') ? $voiceNote->getClientOriginalName() : 'selected' }}</span>
                @endif
                @if($attachment)
                    <span>File: {{ method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'selected' }}</span>
                @endif
            </div>
        </div>
    </div>
    <script>
        (function () {
            let ringTimer = null;
            let audioContext = null;
            const emojiPanel = document.getElementById('emoji-panel');
            const emojiToggleBtn = document.getElementById('emoji-toggle-btn');
            const chatInput = document.getElementById('chat-message-input');
            const emojiChars = Array.from('😀😃😄😁😆😅😂🤣😊😇🙂🙃😉😍🥰😘😗😙😚😋😜😝🤑🤗🤔🤨😐😑😶🙄😏😣😥😮😪😫🥱😴😌😛😎🤓🧐🤠🥳😤😠😡🤬😱😨😰😥😓🤯😳🥺😭😢😵🤕🤒🤧🤢🤮🤠👋🤚🖐✋🖖👌🤌🤏✌🤞🫶🤟🤘🤙👈👉👆👇☝👍👎✊👊🤛🤜👏🙌👐🤲🙏💪🦾🧠🫀🫁👀👂👃👄🫦💋👶🧒👦👧🧑👨👩🧔👵👴👮🕵️👷💂🧑‍⚕️👩‍⚕️👨‍⚕️🧑‍💻👩‍💻👨‍💻🧑‍🔧👩‍🔧👨‍🔧🎉🎊✨🔥🌟⭐💯✅❌⚠️📞📱💬🔔🎵🎶🚚🛻🚛🗺️📍🛠️⛽');

            function beepOnce() {
                try {
                    audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
                    const osc = audioContext.createOscillator();
                    const gain = audioContext.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = 800;
                    gain.gain.value = 0.08;
                    osc.connect(gain);
                    gain.connect(audioContext.destination);
                    osc.start();
                    setTimeout(() => osc.stop(), 250);
                } catch (_) {}
            }

            function syncRingtone() {
                const incoming = document.getElementById('incoming-call-banner');
                if (incoming && !ringTimer) {
                    beepOnce();
                    ringTimer = setInterval(beepOnce, 1200);
                }
                if (!incoming && ringTimer) {
                    clearInterval(ringTimer);
                    ringTimer = null;
                }
            }

            function appendEmoji(emoji) {
                if (!chatInput) return;
                chatInput.value = `${chatInput.value}${emoji}`;
                chatInput.dispatchEvent(new Event('input', { bubbles: true }));
                chatInput.focus();
            }

            function buildEmojiPanel() {
                if (!emojiPanel) return;
                emojiPanel.innerHTML = '';
                emojiChars.forEach((emoji) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'emoji-btn';
                    btn.textContent = emoji;
                    btn.addEventListener('click', () => appendEmoji(emoji));
                    emojiPanel.appendChild(btn);
                });
            }

            if (emojiToggleBtn && emojiPanel) {
                buildEmojiPanel();
                emojiToggleBtn.addEventListener('click', () => {
                    emojiPanel.style.display = emojiPanel.style.display === 'block' ? 'none' : 'block';
                });
            }

            setInterval(syncRingtone, 500);
        })();
    </script>
</x-filament-panels::page>
