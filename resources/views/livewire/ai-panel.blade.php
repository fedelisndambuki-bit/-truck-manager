<div class="tm-ai-panel" style="position: fixed; bottom: 4.5rem; right: 1.25rem; z-index: 9999;">
    <style>
        .tm-ai-panel{
            --tm-brand:#2f4f4f;
            --tm-brand-light:#3b6363;
            --tm-brand-tint:#e9f0f0;
            --tm-surface:#ffffff;
            --tm-surface-sunken:#f7f8f9;
            --tm-bubble-ai:#ffffff;
            --tm-text:#1f2937;
            --tm-text-muted:#6b7280;
            --tm-text-faint:#9ca3af;
            --tm-border:#eceef0;
            --tm-shadow:0 12px 32px rgba(15,23,32,.16), 0 2px 8px rgba(15,23,32,.08);
        }
        html.dark .tm-ai-panel{
            --tm-brand-tint:#25302f;
            --tm-surface:#171e26;
            --tm-surface-sunken:#0f151b;
            --tm-bubble-ai:#232c35;
            --tm-text:#e5e7eb;
            --tm-text-muted:#9aa4b2;
            --tm-text-faint:#6b7583;
            --tm-border:#2a333d;
            --tm-shadow:0 16px 40px rgba(0,0,0,.45), 0 2px 8px rgba(0,0,0,.3);
        }

        .tm-ai-fab{position:relative;display:flex;align-items:center;justify-content:center;width:56px;height:56px;border:none;border-radius:9999px;background:var(--tm-brand);color:#fff;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,.25);transition:transform .15s ease, box-shadow .15s ease}
        .tm-ai-fab:hover{transform:scale(1.06);box-shadow:0 8px 22px rgba(0,0,0,.3)}
        .tm-ai-fab:active{transform:scale(.96)}
        .tm-ai-fab svg{width:24px;height:24px;position:relative}
        .tm-ai-fab-ring{position:absolute;inset:0;border-radius:9999px;background:var(--tm-brand);opacity:.5;animation:tm-ai-pulse 2.6s ease-out infinite}
        @keyframes tm-ai-pulse{0%{transform:scale(1);opacity:.45}70%{transform:scale(1.6);opacity:0}100%{transform:scale(1.6);opacity:0}}
        @media (prefers-reduced-motion: reduce){.tm-ai-fab-ring{animation:none;display:none}}

        .tm-ai-dropdown{position:absolute;bottom:68px;right:0;width:380px;max-width:92vw;max-height:min(600px,72vh);background:var(--tm-surface);border-radius:18px;box-shadow:var(--tm-shadow);display:flex;flex-direction:column;overflow:hidden;animation:tm-ai-pop .18s ease-out}
        @keyframes tm-ai-pop{from{opacity:0;transform:translateY(10px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}

        .tm-ai-header{background:var(--tm-brand);color:#f4f7f6;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px}
        .tm-ai-header-id{display:flex;align-items:center;gap:10px;min-width:0}
        .tm-ai-avatar{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:9999px;background:rgba(255,255,255,.15);flex-shrink:0}
        .tm-ai-avatar svg{width:18px;height:18px}
        .tm-ai-title-wrap{min-width:0}
        .tm-ai-title{font-size:14px;font-weight:600;margin:0;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .tm-ai-status{display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(244,247,246,.72);margin-top:1px}
        .tm-ai-status-dot{width:6px;height:6px;border-radius:9999px;background:#34d399}
        .tm-ai-header-actions{display:flex;align-items:center;gap:2px;flex-shrink:0}
        .tm-ai-icon-btn{background:transparent;border:0;color:rgba(244,247,246,.8);cursor:pointer;padding:6px;line-height:1;border-radius:8px;transition:background .15s, color .15s}
        .tm-ai-icon-btn:hover{background:rgba(255,255,255,.12);color:#fff}
        .tm-ai-icon-btn svg{width:16px;height:16px;display:block}

        .tm-ai-body{padding:12px;display:flex;flex-direction:column;gap:3px;max-height:50vh;overflow-y:auto;background:var(--tm-surface-sunken);-ms-overflow-style:none}
        .tm-ai-body::-webkit-scrollbar{width:5px}
        .tm-ai-body::-webkit-scrollbar-track{background:transparent}
        .tm-ai-body::-webkit-scrollbar-thumb{background:rgba(120,120,120,.35);border-radius:999px}

        .tm-ai-welcome{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:28px 18px 20px}
        .tm-ai-welcome-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:9999px;background:var(--tm-brand-tint);margin-bottom:2px}
        .tm-ai-welcome-icon svg{width:22px;height:22px}
        .tm-ai-welcome-title{font-size:14px;font-weight:600;color:var(--tm-text);margin:0}
        .tm-ai-welcome-text{font-size:12.5px;color:var(--tm-text-muted);max-width:230px;margin:0;line-height:1.5}

        .tm-ai-row{display:flex;flex-direction:column;margin:6px 0}
        .tm-ai-row-user{align-items:flex-end}
        .tm-ai-row-ai{align-items:flex-start}
        .tm-ai-line{display:flex;align-items:flex-end;gap:7px;max-width:88%}
        .tm-ai-row-user .tm-ai-line{flex-direction:row-reverse}
        .tm-ai-avatar-sm{display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:9999px;background:var(--tm-brand-tint);flex-shrink:0;margin-bottom:2px}
        .tm-ai-avatar-sm svg{width:13px;height:13px}
        .tm-ai-bubble{padding:9px 12px;border-radius:16px;font-size:13.5px;line-height:1.5;box-shadow:0 1px 2px rgba(0,0,0,.04)}
        .tm-ai-bubble-user{background:var(--tm-brand);color:#fff;border-bottom-right-radius:5px}
        .tm-ai-bubble-ai{background:var(--tm-bubble-ai);color:var(--tm-text);border-bottom-left-radius:5px}
        .tm-ai-bubble-text{margin:0;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere}
        .tm-ai-bubble-text p{margin:0 0 6px 0}
        .tm-ai-bubble-text p:last-child{margin-bottom:0}
        .tm-ai-bubble-text ul,.tm-ai-bubble-text ol{margin:2px 0 8px 0;padding-left:18px}
        .tm-ai-bubble-text li{margin-bottom:2px}
        .tm-ai-bubble-text strong.tm-ai-heading{display:block;margin:10px 0 4px 0;padding-bottom:2px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--tm-border)}
        .tm-ai-bubble-text strong.tm-ai-heading:first-child{margin-top:0}
        .tm-ai-time{font-size:10px;color:var(--tm-text-faint);margin-top:3px}
        .tm-ai-row-user .tm-ai-time{margin-right:2px}
        .tm-ai-row-ai .tm-ai-time{margin-left:31px}

        .tm-ai-rate{display:flex;align-items:center;gap:8px;margin-top:6px;padding-top:6px;border-top:1px solid var(--tm-border)}
        .tm-ai-rate-btn{background:transparent;border:0;padding:2px;cursor:pointer;color:var(--tm-text-faint);transition:color .15s}
        .tm-ai-rate-btn svg{width:13px;height:13px;display:block}
        .tm-ai-rate-btn:hover{color:var(--tm-text-muted)}
        .tm-ai-rate-btn.is-up{color:#059669}
        .tm-ai-rate-btn.is-down{color:#dc2626}

        .tm-ai-suggestions{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;margin-left:31px}
        .tm-ai-chip-suggestion{background:var(--tm-brand);color:#fff;border:none;border-radius:999px;padding:5px 10px;font-size:11px;cursor:pointer;transition:opacity .15s}
        .tm-ai-chip-suggestion:hover{opacity:.88}

        .tm-ai-typing{display:inline-flex;align-items:center;gap:3px;padding:2px 2px}
        .tm-ai-typing-dot{width:6px;height:6px;border-radius:9999px;background:var(--tm-text-faint);display:inline-block;animation:tm-ai-typing 1.2s ease-in-out infinite}
        .tm-ai-typing-dot:nth-child(2){animation-delay:.15s}
        .tm-ai-typing-dot:nth-child(3){animation-delay:.3s}
        @keyframes tm-ai-typing{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-4px);opacity:1}}

        .tm-ai-footer{border-top:1px solid var(--tm-border);padding:10px;position:relative;background:var(--tm-surface)}
        .tm-ai-quick{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px}
        .tm-ai-chip{background:transparent;color:var(--tm-text-muted);border:1px solid var(--tm-border);border-radius:999px;padding:5px 10px;font-size:11px;cursor:pointer;transition:all .15s}
        .tm-ai-chip:hover{background:var(--tm-surface-sunken);border-color:var(--tm-text-faint)}

        .tm-ai-autocomplete{position:absolute;bottom:100%;left:10px;right:10px;margin-bottom:4px;background:var(--tm-surface);border:1px solid var(--tm-border);border-radius:12px;box-shadow:0 -8px 24px rgba(0,0,0,.12);z-index:10001;max-height:200px;overflow-y:auto}
        .tm-ai-suggestion-item{padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--tm-text)}
        .tm-ai-suggestion-item:hover{background:var(--tm-surface-sunken)}
        .tm-ai-suggestion-type{font-size:9.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--tm-text-muted);background:var(--tm-surface-sunken);padding:2px 5px;border-radius:5px}

        .tm-ai-form{display:flex;align-items:center;gap:8px}
        .tm-ai-input-wrap{flex:1 1 auto;min-width:0}
        .tm-ai-input{width:100%;border:1px solid var(--tm-border);border-radius:999px;padding:10px 15px;outline:none;background:var(--tm-surface-sunken);color:var(--tm-text);font-size:13.5px;line-height:1.4;box-sizing:border-box;transition:border-color .15s, box-shadow .15s}
        .tm-ai-input::placeholder{color:var(--tm-text-faint)}
        .tm-ai-input:focus{border-color:var(--tm-brand);box-shadow:0 0 0 3px rgba(47,79,79,.15)}
        .tm-ai-send{display:flex;align-items:center;justify-content:center;width:38px;height:38px;flex-shrink:0;background:var(--tm-brand);color:#fff;border:none;border-radius:9999px;cursor:pointer;transition:opacity .15s}
        .tm-ai-send:hover{opacity:.9}
        .tm-ai-send:disabled{opacity:.55;cursor:default}
        .tm-ai-send svg{width:16px;height:16px}
        .tm-ai-spin{animation:tm-ai-spin 0.8s linear infinite}
        @keyframes tm-ai-spin{to{transform:rotate(360deg)}}
    </style>

    {{-- Floating launcher --}}
    <button wire:click="togglePanel" type="button" aria-label="{{ $showPanel ? 'Close AI Assistant' : 'Open AI Assistant' }}" class="tm-ai-fab">
        @unless($showPanel)
            <span class="tm-ai-fab-ring"></span>
        @endunless
        @if($showPanel)
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.36-.305c-.687.61-1.86 1.176-3.427 1.282-.156.01-.293-.104-.303-.26a.34.34 0 01.062-.212c.451-.622.827-1.372.964-2.05C3.363 14.404 2 12.33 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" /></svg>
        @endif
    </button>

    @if($showPanel)
    <div x-data="{}" class="tm-ai-dropdown">
        <div class="tm-ai-header">
            <div class="tm-ai-header-id">
                <div class="tm-ai-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.36-.305c-.687.61-1.86 1.176-3.427 1.282-.156.01-.293-.104-.303-.26a.34.34 0 01.062-.212c.451-.622.827-1.372.964-2.05C3.363 14.404 2 12.33 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" /></svg>
                </div>
                <div class="tm-ai-title-wrap">
                    <h3 class="tm-ai-title">AI Assistant</h3>
                    <p class="tm-ai-status"><span class="tm-ai-status-dot"></span>Online</p>
                </div>
            </div>
            <div class="tm-ai-header-actions">
                <button wire:click="clearHistory" type="button" title="New chat" aria-label="Start a new chat" class="tm-ai-icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </button>
                <button wire:click="refreshSession" type="button" title="Refresh AI data" aria-label="Refresh AI data" class="tm-ai-icon-btn" wire:loading.class="tm-ai-spin" wire:target="refreshSession">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                <button type="button" wire:click="togglePanel" aria-label="Close AI Assistant" class="tm-ai-icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <div id="ai-chat-body" class="tm-ai-body" x-data="{ scrollToBottom() { $el.scrollTo({ top: $el.scrollHeight, behavior: 'smooth' }); } }" x-init="scrollToBottom(); $watch('$wire.history', () => $nextTick(() => scrollToBottom()))">
            @if(empty($history))
                <div class="tm-ai-welcome">
                    <div class="tm-ai-welcome-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="var(--tm-brand)"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.36-.305c-.687.61-1.86 1.176-3.427 1.282-.156.01-.293-.104-.303-.26a.34.34 0 01.062-.212c.451-.622.827-1.372.964-2.05C3.363 14.404 2 12.33 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="tm-ai-welcome-title">Hi, I'm your AI Assistant</p>
                    <p class="tm-ai-welcome-text">Ask me about trucks, trips, drivers or anything else in your fleet &mdash; or try a quick action below.</p>
                </div>
            @endif

            @foreach($history as $index => $entry)
                <div class="tm-ai-row {{ $entry['role'] == 'user' ? 'tm-ai-row-user' : 'tm-ai-row-ai' }}">
                    <div class="tm-ai-line">
                        @if($entry['role'] == 'ai')
                            <div class="tm-ai-avatar-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="var(--tm-brand)"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.36-.305c-.687.61-1.86 1.176-3.427 1.282-.156.01-.293-.104-.303-.26a.34.34 0 01.062-.212c.451-.622.827-1.372.964-2.05C3.363 14.404 2 12.33 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" /></svg>
                            </div>
                        @endif
                        <div class="tm-ai-bubble {{ $entry['role'] == 'user' ? 'tm-ai-bubble-user' : 'tm-ai-bubble-ai' }}">
                            @if($entry['role'] == 'user')
                                <p class="tm-ai-bubble-text">{{ $entry['content'] }}</p>
                            @else
                                <div class="tm-ai-bubble-text">{!! \App\Support\SimpleMarkdown::toHtml($entry['content']) !!}</div>
                            @endif
                            @if($entry['role'] == 'ai' && isset($entry['log_id']))
                                <div class="tm-ai-rate">
                                    <button wire:click="rateResponse({{ $index }}, 1)" aria-label="Good response" class="tm-ai-rate-btn {{ ($entry['rating'] ?? 0) == 1 ? 'is-up' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.708C19.746 10 20.621 10.875 20.621 12.012c0 .493-.165.986-.494 1.366l-3.328 3.75c-.329.38-.81.611-1.317.611H8V8.414l3.293-3.293a1 1 0 011.414 0l.707.707a1 1 0 010 1.414L10.828 10H14z" /></svg>
                                    </button>
                                    <button wire:click="rateResponse({{ $index }}, -1)" aria-label="Poor response" class="tm-ai-rate-btn {{ ($entry['rating'] ?? 0) == -1 ? 'is-down' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" transform="scale(1,-1)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.708C19.746 10 20.621 10.875 20.621 12.012c0 .493-.165.986-.494 1.366l-3.328 3.75c-.329.38-.81.611-1.317.611H8V8.414l3.293-3.293a1 1 0 011.414 0l.707.707a1 1 0 010 1.414L10.828 10H14z" /></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(!empty($entry['time']))
                        <span class="tm-ai-time">{{ $entry['time'] }}</span>
                    @endif

                    @if($entry['role'] == 'ai' && !empty($entry['suggestions']))
                        <div class="tm-ai-suggestions">
                            @foreach($entry['suggestions'] as $suggestion)
                                <button wire:click="quickAsk('{{ addslashes($suggestion) }}')" class="tm-ai-chip-suggestion">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if($loading)
                <div class="tm-ai-row tm-ai-row-ai">
                    <div class="tm-ai-line">
                        <div class="tm-ai-avatar-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="var(--tm-brand)"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.36-.305c-.687.61-1.86 1.176-3.427 1.282-.156.01-.293-.104-.303-.26a.34.34 0 01.062-.212c.451-.622.827-1.372.964-2.05C3.363 14.404 2 12.33 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z" clip-rule="evenodd" /></svg>
                        </div>
                        <div class="tm-ai-bubble tm-ai-bubble-ai">
                            <span class="tm-ai-typing">
                                <span class="tm-ai-typing-dot"></span>
                                <span class="tm-ai-typing-dot"></span>
                                <span class="tm-ai-typing-dot"></span>
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="tm-ai-footer">
            @if(!empty($autoCompleteSuggestions))
                <div class="tm-ai-autocomplete">
                    @foreach($autoCompleteSuggestions as $s)
                        <div wire:click="selectSuggestion('{{ $s['text'] }}')" class="tm-ai-suggestion-item">
                            <span class="tm-ai-suggestion-type">{{ $s['type'] }}</span>
                            <span>{{ $s['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="tm-ai-quick">
                @php
                    $user = auth()->user();
                    $isSuperAdmin = $user?->hasRole('Super Admin');
                @endphp
                <button class="tm-ai-chip" wire:click="quickAsk('/help')">/help</button>
                @if($isSuperAdmin)
                    <button class="tm-ai-chip" wire:click="quickAsk('total companies')">Total companies</button>
                    <button class="tm-ai-chip" wire:click="quickAsk('active subscriptions')">Active subscriptions</button>
                @else
                    <button class="tm-ai-chip" wire:click="quickAsk('active trips')">Active trips</button>
                    <button class="tm-ai-chip" wire:click="quickAsk('maintenance due')">Maintenance due</button>
                @endif
            </div>

            <form wire:submit.prevent="sendMessage" class="tm-ai-form">
                <div class="tm-ai-input-wrap">
                    <input
                        wire:model.live="message"
                        type="text"
                        class="tm-ai-input"
                        placeholder="{{ $isSuperAdmin ? 'Ask about companies, plans, logs...' : 'Ask about a truck, driver or trip...' }}"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" aria-label="Send message" wire:loading.attr="disabled" wire:target="sendMessage" class="tm-ai-send">
                    <svg wire:loading.remove wire:target="sendMessage" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg>
                    <svg wire:loading wire:target="sendMessage" class="tm-ai-spin" viewBox="0 0 24 24" fill="none"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
