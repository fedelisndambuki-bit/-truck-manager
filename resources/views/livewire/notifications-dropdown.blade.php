<div class="relative" x-data="{
    open: false,
    currentFilter: 'all',
    expandedId: null,
    notifications: @entangle('notifications'),
    unreadCount: @entangle('unreadCount'),
    icons: { system:'🚨', client:'👤', billing:'💳', module:'◈' },
    labels: { system:'System', client:'Client', billing:'Billing', module:'Module' },
    markRead(id) {
        $wire.markRead(id);
    },
    markAllRead() {
        $wire.markAllRead();
    },
    ringBell() {
        const btn = $refs.bellBtn;
        btn.classList.remove('bell-ring');
        void btn.offsetWidth;
        btn.classList.add('bell-ring');
    },
    toggleDropdown() {
        this.open = !this.open;
        if (this.open) this.ringBell();
    },
    expand(id) {
        if (this.expandedId === id) {
            this.expandedId = null;
        } else {
            this.expandedId = id;
            this.markRead(id);
        }
    }
}" @click.away="open = false">
    <style>
        .bell-wrap { position: relative; font-family: inherit; }
        .bell-btn {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--tm-pill-bg); border: 1px solid var(--tm-pill-border);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; position: relative;
            color: var(--tm-pill-muted); font-family: inherit;
        }
        .bell-btn svg { width: 16px; height: 16px; }
        .bell-btn:hover { background: var(--tm-pill-bg-hover); color: var(--tm-pill-text); }
        .bell-btn.active { background: var(--tm-pill-bg-hover); color: var(--tm-pill-text); }

        .bell-badge {
            position: absolute; top: -5px; right: -5px;
            width: 19px; height: 19px; border-radius: 50%;
            background: #ef4444; border: 2px solid #1e3535;
            font-size: 9px; font-weight: 700; color: #fff;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; font-family: inherit;
        }
        .bell-badge.hidden { opacity: 0; transform: scale(0); }

        .notif-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: 380px; background: #1e3535; border: 1px solid #243d3d;
            border-radius: 14px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 4px 16px rgba(0, 0, 0, 0.08);
            z-index: 500; transform-origin: top right; overflow: hidden;
            font-family: inherit;
        }

        .nd-header { padding: 16px 18px 12px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #2f4f4f; background: #243d3d; font-family: inherit; }
        .nd-title { font-weight: 700; font-size: 15px; color: #f7fafa; font-family: inherit; }
        .unread-pill { background: #4e9a9a; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 500; font-family: inherit; }
        .mark-all { font-size: 11px; color: #6db8b8; background: none; border: none; cursor: pointer; transition: color 0.15s; font-family: inherit; }
        .mark-all:hover { color: #fff; }

        .nd-tabs { display: flex; padding: 0 18px; border-bottom: 1px solid #2f4f4f; background: #1e3535; overflow-x: auto; gap: 0; font-family: inherit; }
        .nd-tab { padding: 9px 12px; font-size: 10px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; color: #6b8a8a; letter-spacing: 0.08em; text-transform: uppercase; transition: all 0.15s; white-space: nowrap; margin-bottom: -1px; font-family: inherit; }
        .nd-tab.active { color: #4e9a9a; border-bottom-color: #4e9a9a; }
        .nd-tab:hover:not(.active) { color: #f7fafa; }

        .nd-list { max-height: 380px; overflow-y: auto; background: #1e3535; font-family: inherit; }
        .nd-item { display: flex; align-items: flex-start; gap: 12px; padding: 13px 18px; border-bottom: 1px solid #243d3d; cursor: pointer; transition: background 0.12s; position: relative; background: #1e3535; font-family: inherit; }
        .nd-item:hover { background: #243d3d; }
        .nd-item.unread { background: #2a4444; }
        .nd-item.unread::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #4e9a9a; border-radius: 0 2px 2px 0; }

        .nd-icon { width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; margin-top: 1px; }
        .icon-system { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
        .icon-client { background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .icon-billing { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .icon-module { background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.2); color: #8b5cf6; }

        .nd-body { flex: 1; min-width: 0; font-family: inherit; }
        .nd-row1 { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 3px; }
        .nd-title-text { font-size: 12px; font-weight: 500; color: #f7fafa; line-height: 1.3; text-align: left; font-family: inherit; }
        .nd-time { font-size: 10px; color: #aab8b8; white-space: nowrap; flex-shrink: 0; font-family: inherit; }
        .nd-msg { font-size: 11px; color: #6b8a8a; line-height: 1.5; text-align: left; font-family: inherit; }
        .nd-msg.truncated { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .nd-tag { display: inline-block; margin-top: 5px; font-size: 9px; padding: 2px 7px; border-radius: 10px; letter-spacing: 0.06em; text-transform: uppercase; font-weight: 500; font-family: inherit; }
        .tag-system { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .tag-client { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        .tag-billing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .tag-module { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .nd-unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #4e9a9a; flex-shrink: 0; margin-top: 6px; }

        .nd-footer { padding: 11px 18px; border-top: 1px solid #2f4f4f; background: #243d3d; display: flex; align-items: center; justify-content: space-between; font-family: inherit; }
        .nd-footer-link { font-size: 11px; color: #6db8b8; background: none; border: none; cursor: pointer; font-family: inherit; }
        .nd-settings { font-size: 11px; color: #6b8a8a; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px; font-family: inherit; }
        .nd-settings:hover { color: #f7fafa; }

        @keyframes ring { 0% { transform: rotate(0); } 10% { transform: rotate(14deg); } 20% { transform: rotate(-10deg); } 30% { transform: rotate(8deg); } 40% { transform: rotate(-5deg); } 50%, 100% { transform: rotate(0); } }
        .bell-ring { animation: ring 0.6s ease; }
    </style>

    <button class="bell-btn" :class="{ 'active': open }" x-ref="bellBtn" @click="toggleDropdown" type="button" aria-label="Notifications">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
        <div class="bell-badge" :class="{ 'hidden': unreadCount === 0 }" x-text="unreadCount"></div>
    </button>

    <div class="notif-dropdown" x-show="open"
        x-transition:enter="transition opacity-200 ease-out transform"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition opacity-150 ease-in transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95">

        <div class="nd-header">
            <div class="nd-title">Notifications</div>
            <div class="nd-meta flex items-center gap-2">
                <span class="unread-pill" x-show="unreadCount > 0" x-text="unreadCount + ' unread'"></span>
                <button class="mark-all" @click="markAllRead">Mark all read</button>
            </div>
        </div>

        <div class="nd-tabs">
            <template x-for="tab in ['all', 'system', 'client', 'billing', 'module']">
                <button class="nd-tab" :class="{ 'active': currentFilter === tab }"
                    @click="currentFilter = tab"
                    x-text="tab.charAt(0).toUpperCase() + tab.slice(1)"></button>
            </template>
        </div>

        <div class="nd-list">
            <template x-for="n in notifications">
                <div class="nd-item" :class="{ 'unread': n.unread }"
                    x-show="currentFilter === 'all' || n.type === currentFilter"
                    @click="expand(n.id)">
                    <div class="nd-icon" :class="'icon-' + n.type" x-text="icons[n.type]"></div>
                    <div class="nd-body">
                        <div class="nd-row1">
                            <div class="nd-title-text" x-text="n.title"></div>
                            <div class="nd-time" x-text="n.time"></div>
                        </div>
                        <div class="nd-msg" :class="{ 'truncated': expandedId !== n.id }" x-text="n.msg"></div>
                        <span class="nd-tag" :class="'tag-' + n.type" x-text="labels[n.type]"></span>
                    </div>
                    <div class="nd-unread-dot" x-show="n.unread"></div>
                </div>
            </template>

            <div class="p-8 text-center text-[#6b8a8a]" x-show="notifications.filter(n => currentFilter === 'all' || n.type === currentFilter).length === 0">
                <svg class="mx-auto mb-2" style="width:28px;height:28px;color:#4e6b6b" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.6 13.4L18 11V9a6 6 0 00-9.5-4.9M4 4l16 16M6.3 8.3A5.9 5.9 0 006 9v2l-1.4 2.6A1 1 0 005.4 15h9.9m-1.8 2a2 2 0 01-3.4 1.4" /></svg>
                <div class="text-sm font-medium">No notifications</div>
                <div class="text-xs opacity-70">Nothing here for this category</div>
            </div>
        </div>

        <div class="nd-footer">
            <button class="nd-footer-link">View all notifications →</button>
            <button class="nd-settings">⚙ Preferences</button>
        </div>
    </div>
</div>
