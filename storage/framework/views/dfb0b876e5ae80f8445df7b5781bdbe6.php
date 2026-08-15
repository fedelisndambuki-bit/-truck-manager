<div class="truck-manager-ai-panel" style="position: fixed; bottom: 4.5rem; right: 1.25rem; z-index: 9999;">
    <style>
        .truck-manager-ai-dropdown{width:380px;max-width:92vw;background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden}
        .truck-manager-ai-header{background:#2f4f4f;color:#f9fafb;padding:12px 14px;display:flex;align-items:center;justify-content:space-between}
        .truck-manager-ai-title{font-size:16px;font-weight:600;margin:0}
        .truck-manager-ai-close{background:transparent;border:0;color:#f9fafb;cursor:pointer;padding:6px;line-height:1}
        .truck-manager-ai-close-icon{width:22px;height:22px}
        .truck-manager-ai-body{padding:12px;display:flex;flex-direction:column;gap:10px;max-height:50vh;overflow-y:auto;-ms-overflow-style:none;scrollbar-width:none}
        .truck-manager-ai-body::-webkit-scrollbar{display:none}
        .truck-manager-ai-bubble-wrap{display:flex;flex-direction:column}
        .truck-manager-ai-bubble-user{align-items:flex-end}
        .truck-manager-ai-bubble-ai{align-items:flex-start}
        .truck-manager-ai-bubble{max-width:85%;padding:10px 12px;border-radius:12px;line-height:1.45;position:relative}
        .truck-manager-ai-bubble-user .truck-manager-ai-bubble{background:#e6f4ff;color:#0b4870;border-top-right-radius:4px}
        .truck-manager-ai-bubble-ai .truck-manager-ai-bubble{background:#f3f4f6;color:#111827;border-top-left-radius:4px}
        .truck-manager-ai-bubble-text{margin:0;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere}
        .truck-manager-ai-footer{border-top:1px solid #e5e7eb;padding:10px;position:relative}
        .truck-manager-ai-quick{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}
        .truck-manager-ai-chip{background:#f3f4f6;color:#111827;border:1px solid #e5e7eb;border-radius:999px;padding:6px 10px;font-size:12px;cursor:pointer;transition:all 0.2s}
        .truck-manager-ai-chip:hover{background:#e5e7eb;border-color:#d1d5db}
        .truck-manager-ai-chip-ai{background:#2f4f4f;color:#fff;border:none}
        .truck-manager-ai-chip-ai:hover{background:#3b6363}
        .truck-manager-ai-form{display:flex;flex-direction:column}
        .truck-manager-ai-input-wrap{display:flex;align-items:center;gap:8px;width:100%}
        .truck-manager-ai-input{flex:1 1 auto;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;outline:none;background:#fff;color:#111827;line-height:1.4}
        .truck-manager-ai-autocomplete{position:absolute;bottom:100%;left:10px;right:10px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 -5px 20px rgba(0,0,0,0.1);z-index:10001;max-height:200px;overflow-y:auto}
        .truck-manager-ai-suggestion-item{padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:13px}
        .truck-manager-ai-suggestion-item:hover{background:#f9fafb}
        .truck-manager-ai-suggestion-type{font-size:10px;text-transform:uppercase;color:#6b7280;background:#f3f4f6;padding:2px 4px;border-radius:4px}
    </style>

    <button wire:click="togglePanel" type="button" class="truck-manager-ai-btn" style="display: flex; align-items: center; justify-content: center; width: 56px; height: 56px; border: none; border-radius: 50%; background-color: #2f4f4f; color: #f9fafb; cursor: pointer; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
        <svg xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px;" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM5 9a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 4a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z" clip-rule="evenodd" />
        </svg>
    </button>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showPanel): ?>
    <div x-data="{}" x-transition class="truck-manager-ai-dropdown">
        <div class="truck-manager-ai-header">
            <div class="flex items-center gap-2">
                <h3 class="truck-manager-ai-title">AI Assistant</h3>
                <button 
                    wire:click="clearHistory" 
                    title="New Chat" 
                    class="p-1 rounded hover:bg-white/10 text-white/80 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
                <button 
                    wire:click="refreshSession" 
                    title="Refresh AI Data" 
                    class="p-1 rounded hover:bg-white/10 text-white/80 transition"
                    wire:loading.class="animate-spin" 
                    wire:target="refreshSession"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
            <button type="button" wire:click="togglePanel" class="truck-manager-ai-close"><svg xmlns="http://www.w3.org/2000/svg" class="truck-manager-ai-close-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <div id="ai-chat-body" class="truck-manager-ai-body" x-data="{ scrollToBottom() { $el.scrollTo({ top: $el.scrollHeight, behavior: 'smooth' }); } }" x-init="scrollToBottom(); $watch('$wire.history', () => $nextTick(() => scrollToBottom()))">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="truck-manager-ai-bubble-wrap <?php if($entry['role'] == 'user'): ?> truck-manager-ai-bubble-user <?php else: ?> truck-manager-ai-bubble-ai <?php endif; ?>">
                    <div class="truck-manager-ai-bubble">
                        <p class="truck-manager-ai-bubble-text"><?php echo e($entry['content']); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($entry['role'] == 'ai' && isset($entry['log_id'])): ?>
                            <div class="flex items-center gap-2 mt-2 pt-1 border-t border-gray-100">
                                <button wire:click="rateResponse(<?php echo e($index); ?>, 1)" class="p-1 <?php if(($entry['rating'] ?? 0) == 1): ?> text-green-600 <?php else: ?> text-gray-400 <?php endif; ?>"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.708C19.746 10 20.621 10.875 20.621 12.012c0 .493-.165.986-.494 1.366l-3.328 3.75c-.329.38-.81.611-1.317.611H8V8.414l3.293-3.293a1 1 0 011.414 0l.707.707a1 1 0 010 1.414L10.828 10H14z" /></svg></button>
                                <button wire:click="rateResponse(<?php echo e($index); ?>, -1)" class="p-1 <?php if(($entry['rating'] ?? 0) == -1): ?> text-red-600 <?php else: ?> text-gray-400 <?php endif; ?>"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" transform="scale(1,-1)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.708C19.746 10 20.621 10.875 20.621 12.012c0 .493-.165.986-.494 1.366l-3.328 3.75c-.329.38-.81.611-1.317.611H8V8.414l3.293-3.293a1 1 0 011.414 0l.707.707a1 1 0 010 1.414L10.828 10H14z" /></svg></button>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($entry['role'] == 'ai' && !empty($entry['suggestions'])): ?>
                        <div class="flex flex-wrap gap-2 mt-2 ml-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $entry['suggestions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button wire:click="quickAsk('<?php echo e(addslashes($suggestion)); ?>')" class="truck-manager-ai-chip truck-manager-ai-chip-ai"><?php echo e($suggestion); ?></button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($loading): ?>
                <div class="truck-manager-ai-bubble-wrap truck-manager-ai-bubble-ai"><div class="truck-manager-ai-bubble"><p class="truck-manager-ai-bubble-text">...</p></div></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="truck-manager-ai-footer">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($autoCompleteSuggestions)): ?>
                <div class="truck-manager-ai-autocomplete">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $autoCompleteSuggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div wire:click="selectSuggestion('<?php echo e($s['text']); ?>')" class="truck-manager-ai-suggestion-item">
                            <span class="truck-manager-ai-suggestion-type"><?php echo e($s['type']); ?></span>
                            <span><?php echo e($s['text']); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="truck-manager-ai-quick">
                <?php
                    $user = auth()->user();
                    $isSuperAdmin = $user?->hasRole('Super Admin');
                ?>
                <button class="truck-manager-ai-chip" wire:click="quickAsk('/help')">/help</button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isSuperAdmin): ?>
                    <button class="truck-manager-ai-chip" wire:click="quickAsk('total companies')">Total companies</button>
                    <button class="truck-manager-ai-chip" wire:click="quickAsk('active subscriptions')">Active subscriptions</button>
                <?php else: ?>
                    <button class="truck-manager-ai-chip" wire:click="quickAsk('active trips')">Active trips</button>
                    <button class="truck-manager-ai-chip" wire:click="quickAsk('maintenance due')">Maintenance due</button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <form wire:submit.prevent="sendMessage" class="truck-manager-ai-form">
                <div class="truck-manager-ai-input-wrap">
                    <input 
                        wire:model.live="message" 
                        type="text" 
                        class="truck-manager-ai-input" 
                        placeholder="<?php echo e($isSuperAdmin ? 'Ask about companies, plans, logs...' : 'Ask about a truck, driver or trip...'); ?>" 
                        autocomplete="off"
                    >
                    <button type="submit" class="truck-manager-ai-send" style="background:#2f4f4f;color:#fff;border:none;border-radius:10px;padding:10px;cursor:pointer"><svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px" viewBox="0 0 20 20" fill="currentColor"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.428A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/livewire/ai-panel.blade.php ENDPATH**/ ?>