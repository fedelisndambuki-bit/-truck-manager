<div class="relative"
    x-data="{
        open: false,
        theme: localStorage.getItem('theme') ?? 'system',
        setTheme(value) {
            this.theme = value;
            localStorage.setItem('theme', value);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: value }));
        }
    }"
    @click.away="open = false"
    @keydown.escape.window="open = false"
>
    <button type="button" class="tm-profile-trigger" @click="open = !open">
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $userName }}" class="tm-avatar tm-avatar-img">
        @else
            <span class="tm-avatar">{{ $initials }}</span>
        @endif
        <span class="tm-profile-trigger-text">
            <span class="tm-profile-name">{{ $userName }}</span>
            <span class="tm-profile-role">{{ $roleName }}</span>
        </span>
        <svg class="tm-chevron" :class="{ 'tm-chevron-open': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="tm-dropdown tm-profile-dropdown"
        style="display: none;"
    >
        <div class="tm-pd-header">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $userName }}" class="tm-avatar tm-avatar-lg tm-avatar-img">
            @else
                <span class="tm-avatar tm-avatar-lg">{{ $initials }}</span>
            @endif
            <div class="tm-pd-header-text">
                <div class="tm-pd-name">{{ $userName }}</div>
                <div class="tm-pd-email">{{ $userEmail }}</div>
            </div>
        </div>

        <div class="tm-pd-section">
            <div class="tm-menu-label">Appearance</div>
            <div class="tm-theme-row">
                <span class="tm-theme-label">Theme</span>
                <div class="tm-theme-toggle">
                    <button type="button" class="tm-tog-opt" :class="{ 'tm-tog-act': theme === 'light' }" @click="setTheme('light')" aria-label="Light mode">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4" /><path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" /></svg>
                    </button>
                    <button type="button" class="tm-tog-opt" :class="{ 'tm-tog-act': theme === 'dark' }" @click="setTheme('dark')" aria-label="Dark mode">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" /></svg>
                    </button>
                    <button type="button" class="tm-tog-opt tm-tog-auto" :class="{ 'tm-tog-act': theme === 'system' }" @click="setTheme('system')">Auto</button>
                </div>
            </div>
        </div>

        <div class="tm-pd-section">
            <div class="tm-menu-label">{{ $canViewOrganisation || $canViewTeam || $canViewBilling || $canViewPlatformSettings ? 'Workspace · ' . $companyName : 'Account' }}</div>
            @if ($canViewOrganisation)
                <a href="{{ $organisationUrl }}" class="tm-pd-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M13 21V9l6 3v9M9 9h.01M9 12h.01M9 15h.01" /></svg>
                    Organisation
                </a>
            @endif
            @if ($canViewPlatformSettings)
                <a href="{{ $platformSettingsUrl }}" class="tm-pd-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><circle cx="12" cy="12" r="3" /></svg>
                    Platform settings
                </a>
            @endif
            <a href="{{ $profileSettingsUrl }}" class="tm-pd-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" /></svg>
                Profile settings
            </a>
            @if ($canViewTeam)
                <a href="{{ $teamUrl }}" class="tm-pd-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m5-4.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 100-8 4 4 0 000 8z" /></svg>
                    Team members
                </a>
            @endif
            @if ($canViewBilling)
                <a href="/admin/manage-subscription" class="tm-pd-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 10h20M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1zM6 15h4" /></svg>
                    Billing
                </a>
            @endif
        </div>

        <div class="tm-pd-section">
            <form method="POST" action="{{ $logoutUrl }}">
                @csrf
                <button type="submit" class="tm-pd-item tm-pd-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 5v1a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Sign out
                </button>
            </form>
        </div>
    </div>
</div>
