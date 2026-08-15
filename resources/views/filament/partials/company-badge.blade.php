<div class="tm-pill tm-company-badge" title="{{ $companyName }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M13 21V9l6 3v9M9 9h.01M9 12h.01M9 15h.01" /></svg>
    <span class="tm-company-name">{{ $companyName }}</span>
    @if ($isImpersonating)
        <span class="tm-impersonating-dot" title="Impersonating"></span>
    @endif
</div>
