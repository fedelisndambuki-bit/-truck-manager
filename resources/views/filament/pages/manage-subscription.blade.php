<x-filament-panels::page>

@php
$r             = 70;
$circumference = 439.82; // 2 * π * 70

if ($trialActive) {
    $daysLeft  = $trialDaysLeft;
    $totalDays = 7;
    $arcColor  = '#2F4F4F';
} elseif ($hasActiveSub) {
    $daysLeft  = $subscription->daysUntilExpiry();
    $totalDays = 30;
    $arcColor  = '#10B981';
} else {
    $daysLeft  = 0;
    $totalDays = 30;
    $arcColor  = '#EF4444';
}
$arcFilled = $totalDays > 0 ? round(($daysLeft / $totalDays) * $circumference, 2) : 0;
$initial   = strtoupper(substr($company?->name ?? 'T', 0, 1));
@endphp

<style>
/* ── Design tokens ─────────────────────────────────── */
:root {
    --primary:      #2F4F4F;
    --primary-10:   rgba(47,79,79,0.10);
    --primary-20:   rgba(47,79,79,0.20);
    --txt:          #0F172A;
    --txt2:         #64748B;
    --surface:      #F8FAFC;
    --card:         #FFFFFF;
    --border:       #E2E8F0;
    --radius:       16px;
    --shadow:       0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    --success:      #10B981;
    --warning:      #F59E0B;
    --danger:       #EF4444;
}

/* ── Two-column grid ────────────────────────────────── */
.ms-grid {
    display: grid;
    grid-template-columns: 1fr 310px;
    gap: 24px;
    align-items: start;
    padding-bottom: 60px;
}
.ms-left  { display: flex; flex-direction: column; gap: 20px; }
.ms-right { position: sticky; top: 80px; display: flex; flex-direction: column; gap: 16px; }

/* ── Card base ──────────────────────────────────────── */
.ms-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.dark .ms-card { background: #1e293b; border-color: #334155; }

/* ── Hero card ──────────────────────────────────────── */
.hero-inner {
    padding: 28px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 24px;
    align-items: center;
}
.hero-avatar {
    width: 60px; height: 60px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2F4F4F 0%, #3d6b6b 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 800; color: #fff;
    flex-shrink: 0;
}
.hero-meta .company { font-size: 20px; font-weight: 800; color: var(--txt); margin: 0 0 3px; }
.dark .hero-meta .company { color: #f1f5f9; }
.hero-meta .plan-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.hero-meta .plan-name { font-size: 13px; color: var(--txt2); }
.hero-meta .meta-items { display: flex; flex-wrap: wrap; gap: 14px; }
.hero-meta .meta-item { font-size: 12px; color: var(--txt2); display: flex; align-items: center; gap: 5px; }
.hero-meta .meta-item svg { width: 14px; height: 14px; }

/* Status badge */
.sbadge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
}
.sbadge-success { background: rgba(16,185,129,0.12); color: #059669; }
.sbadge-primary { background: var(--primary-10); color: var(--primary); }
.sbadge-danger  { background: rgba(239,68,68,0.12); color: #dc2626; }
.sbadge-warning { background: rgba(245,158,11,0.12); color: #d97706; }

/* Countdown ring */
.countdown-wrap { flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.ring-outer { position: relative; width: 156px; height: 156px; }
.ring-outer svg { width: 156px; height: 156px; transform: rotate(-90deg); }
.ring-inner {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.ring-inner .ring-num  { font-size: 34px; font-weight: 900; color: var(--txt); line-height: 1; }
.dark .ring-inner .ring-num { color: #f1f5f9; }
.ring-inner .ring-lbl  { font-size: 11px; color: var(--txt2); margin-top: 2px; }
.ring-btns { display: flex; gap: 8px; }

/* Buttons */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px;
    font-size: 12px; font-weight: 600; cursor: pointer; border: none;
    transition: all 0.18s ease; text-decoration: none;
}
.btn svg { width: 14px; height: 14px; }
.btn-p  { background: var(--primary); color: #fff; }
.btn-p:hover { background: #1a3a3a; box-shadow: 0 4px 12px rgba(47,79,79,0.35); }
.btn-o  { background: transparent; color: var(--primary); border: 1.5px solid var(--primary); }
.btn-o:hover { background: var(--primary-10); }

/* ── Section header ─────────────────────────────────── */
.sec-hdr {
    padding: 18px 22px 0;
    display: flex; align-items: center; justify-content: space-between;
}
.sec-title { font-size: 14px; font-weight: 700; color: var(--txt); margin: 0; }
.dark .sec-title { color: #f1f5f9; }

/* ── Plan cards ─────────────────────────────────────── */
.plan-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; padding: 18px 22px 22px; }
.plan-card {
    border: 1.5px solid var(--border); border-radius: 13px; padding: 18px;
    cursor: pointer; transition: all 0.2s; position: relative;
}
.dark .plan-card { border-color: #334155; }
.plan-card:hover  { border-color: var(--primary); box-shadow: 0 4px 16px rgba(47,79,79,0.12); }
.plan-card.active { border-color: var(--primary); background: linear-gradient(135deg,rgba(47,79,79,0.05),rgba(47,79,79,0.03)); }
.plan-badge {
    position: absolute; top: -9px; left: 50%; transform: translateX(-50%);
    background: var(--primary); color: #fff; font-size: 9px; font-weight: 800;
    padding: 2px 9px; border-radius: 20px; letter-spacing: 0.4px; white-space: nowrap;
}
.plan-tier  { font-size: 11px; font-weight: 600; color: var(--txt2); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
.plan-price { font-size: 20px; font-weight: 900; color: var(--txt); line-height: 1; }
.dark .plan-price { color: #f1f5f9; }
.plan-price span { font-size: 12px; font-weight: 500; color: var(--txt2); }
.plan-features { list-style: none; margin: 12px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.plan-features li { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--txt2); }
.plan-features li svg { width: 13px; height: 13px; color: var(--success); flex-shrink: 0; }
.plan-btn {
    display: block; width: 100%; margin-top: 14px; padding: 7px;
    border-radius: 7px; font-size: 11px; font-weight: 600; text-align: center;
    border: none; cursor: pointer; transition: all 0.15s;
}
.plan-btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--txt2); }
.plan-btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.plan-btn-filled  { background: var(--primary); color: #fff; }
.plan-btn-filled:hover { background: #1a3a3a; }

/* ── Filter tabs ────────────────────────────────────── */
.ftabs { display: flex; gap: 3px; background: var(--surface); padding: 3px; border-radius: 8px; border: 1px solid var(--border); }
.ftab  { padding: 4px 11px; border-radius: 6px; font-size: 11px; font-weight: 500; cursor: pointer; border: none; background: transparent; color: var(--txt2); transition: all 0.15s; }
.ftab.on, .ftab:hover { background: var(--card); color: var(--primary); box-shadow: 0 1px 4px rgba(0,0,0,0.08); }

/* ── Billing history table ──────────────────────────── */
.bh-wrap { overflow-x: auto; }
.bh-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.bh-table thead th { padding: 9px 16px; text-align: left; font-size: 10px; font-weight: 700; color: var(--txt2); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); white-space: nowrap; }
.bh-table tbody td { padding: 12px 16px; color: var(--txt); border-bottom: 1px solid var(--border); vertical-align: middle; }
.dark .bh-table tbody td { color: #cbd5e1; }
.bh-table tbody tr:last-child td { border-bottom: none; }
.bh-table tbody tr:hover td { background: var(--surface); }
.bh-table .mono { font-size: 11px; color: var(--txt2); font-family: monospace; }
.bh-table .fw7  { font-weight: 700; }
.dl-btn { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 500; color: var(--primary); padding: 3px 8px; border-radius: 5px; background: var(--primary-10); border: none; cursor: pointer; }
.dl-btn:hover { background: var(--primary-20); }
.dl-btn svg { width: 12px; height: 12px; }

/* ── KPI 2×2 ────────────────────────────────────────── */
.kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 18px 18px 6px; }
.kpi-item { background: var(--surface); border: 1px solid var(--border); border-radius: 11px; padding: 13px; }
.dark .kpi-item { background: #0f172a; border-color: #1e293b; }
.kpi-lbl { font-size: 10px; font-weight: 700; color: var(--txt2); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 5px; display: flex; align-items: center; gap: 4px; }
.kpi-lbl svg { width: 13px; height: 13px; }
.kpi-val { font-size: 20px; font-weight: 900; color: var(--txt); line-height: 1; }
.dark .kpi-val { color: #f1f5f9; }
.kpi-sub { font-size: 10px; color: var(--success); margin-top: 3px; font-weight: 500; }

/* ── Right card sections ────────────────────────────── */
.rc-pad { padding: 18px; }
.rc-title { font-size: 13px; font-weight: 700; color: var(--txt); margin: 0 0 12px; display: flex; align-items: center; gap: 6px; }
.dark .rc-title { color: #f1f5f9; }
.rc-title svg { width: 15px; height: 15px; color: var(--primary); }

/* Billing fields */
.bf-field { margin-bottom: 10px; }
.bf-label { font-size: 11px; font-weight: 600; color: var(--txt2); margin-bottom: 4px; display: block; }
.bf-input {
    width: 100%; padding: 8px 11px; border-radius: 8px;
    border: 1.5px solid var(--border); font-size: 12px; color: var(--txt);
    background: var(--surface); outline: none; transition: border-color 0.15s, box-shadow 0.15s;
}
.bf-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(47,79,79,0.12); }
.dark .bf-input { background: #0f172a; color: #f1f5f9; border-color: #334155; }

/* Payment method chips */
.pm-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.pm-chip {
    padding: 5px 11px; border-radius: 7px; font-size: 11px; font-weight: 600;
    border: 1.5px solid var(--border); background: var(--surface); color: var(--txt2);
    cursor: pointer; transition: all 0.15s;
}
.pm-chip.on { border-color: var(--primary); background: var(--primary-10); color: var(--primary); }
.pm-chip:hover { border-color: var(--primary); color: var(--primary); }

/* Quick actions */
.qa-list { display: flex; flex-direction: column; gap: 5px; }
.qa-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 11px; border-radius: 9px;
    background: var(--surface); border: 1px solid var(--border);
    cursor: pointer; transition: all 0.15s;
    text-decoration: none; color: var(--txt); font-size: 12px; font-weight: 500;
    width: 100%; text-align: left;
}
.qa-item:hover { background: var(--primary-10); border-color: var(--primary); }
.qa-item svg { width: 16px; height: 16px; color: var(--primary); flex-shrink: 0; }
.qa-arr { margin-left: auto; width: 14px; height: 14px; color: var(--txt2); }

/* Auto-renewal toggle */
.toggle-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.toggle-sw { position: relative; width: 42px; height: 23px; background: var(--border); border-radius: 11px; transition: background 0.2s; cursor: pointer; flex-shrink: 0; }
.toggle-sw.on { background: var(--success); }
.toggle-kn { position: absolute; top: 2.5px; left: 2.5px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.2); transition: transform 0.2s; }
.toggle-sw.on .toggle-kn { transform: translateX(19px); }
.renew-date { font-size: 11px; background: var(--surface); border: 1px solid var(--border); border-radius: 7px; padding: 7px 10px; color: var(--txt2); display: flex; align-items: center; gap: 5px; margin-top: 10px; }
.renew-date svg { width: 13px; height: 13px; color: var(--primary); }

/* ── Pay button ─────────────────────────────────────── */
.pay-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 11px; border-radius: 10px;
    background: var(--primary); color: #fff; font-size: 13px; font-weight: 700;
    border: none; cursor: pointer; transition: all 0.18s; margin-top: 4px;
}
.pay-btn:hover { background: #1a3a3a; box-shadow: 0 4px 14px rgba(47,79,79,0.4); }
.pay-btn svg { width: 16px; height: 16px; }

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 1100px) {
    .ms-grid { grid-template-columns: 1fr; }
    .ms-right { position: static; }
}
@media (max-width: 640px) {
    .hero-inner { grid-template-columns: 1fr; }
    .countdown-wrap { margin: 0 auto; }
    .plan-grid { grid-template-columns: 1fr; }
}
</style>

@if(! $company)
<div class="flex flex-col items-center justify-center py-20 text-slate-400">
    <x-heroicon-o-building-office class="w-12 h-12 mb-3 opacity-40"/>
    <p class="text-sm">No company linked to your account. Please contact support.</p>
</div>
@else

<div x-data="{
    historyFilter: 'all',
    pm: @entangle('payment_method').live
}" class="ms-grid">

    {{-- ═══════════════════ LEFT COLUMN ═══════════════════ --}}
    <div class="ms-left">

        {{-- ── HERO CARD ─────────────────────────────────── --}}
        <div class="ms-card">
            <div class="h-1 w-full" style="background:linear-gradient(90deg,#2F4F4F 0%,#4a7070 50%,#2F4F4F 100%)"></div>
            <div class="hero-inner">

                {{-- Avatar --}}
                <div class="hero-avatar">{{ $initial }}</div>

                {{-- Meta --}}
                <div class="hero-meta">
                    <p class="company">{{ $company->name ?? 'Your Company' }}</p>
                    <div class="plan-row">
                        <span class="plan-name">{{ $currentPlan?->name ?? $selectedPlan?->name ?? 'No Plan Selected' }}</span>
                        @if($hasActiveSub)
                            <span class="sbadge sbadge-success">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                Active
                            </span>
                        @elseif($trialActive)
                            <span class="sbadge sbadge-primary">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                                Trial
                            </span>
                        @elseif($trialExpired)
                            <span class="sbadge sbadge-danger">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                Expired
                            </span>
                        @else
                            <span class="sbadge sbadge-warning">Pending</span>
                        @endif
                    </div>
                    <div class="meta-items">
                        @if($hasActiveSub && $renewalDate)
                        <span class="meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Renews {{ $renewalDate->format('d M Y') }}
                        </span>
                        @elseif($trialActive && $nextPaymentDate)
                        <span class="meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Trial ends {{ $nextPaymentDate->format('d M Y') }}
                        </span>
                        @endif
                        @php $dp = $currentPlan ?? $selectedPlan; @endphp
                        @if($dp)
                        <span class="meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            KES {{ number_format($dp->price) }}/mo
                        </span>
                        <span class="meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3"/></svg>
                            Trucks: {{ $dp->truck_limit === -1 ? 'Unlimited' : $dp->truck_limit }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Circular countdown --}}
                <div class="countdown-wrap">
                    <div class="ring-outer">
                        <svg viewBox="0 0 156 156" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="78" cy="78" r="{{ $r }}" fill="none" stroke="#E2E8F0" stroke-width="10"/>
                            <circle cx="78" cy="78" r="{{ $r }}" fill="none"
                                    stroke="{{ $arcColor }}" stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $arcFilled }} {{ $circumference }}"/>
                        </svg>
                        <div class="ring-inner">
                            <span class="ring-num">{{ $daysLeft }}</span>
                            <span class="ring-lbl">{{ $daysLeft === 1 ? 'day' : 'days' }} left</span>
                        </div>
                    </div>
                    <div class="ring-btns">
                        <button class="btn btn-p" wire:click="initiatePayment" wire:loading.attr="disabled">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span wire:loading.remove wire:target="initiatePayment">{{ $hasActiveSub ? 'Renew' : 'Subscribe' }}</span>
                            <span wire:loading wire:target="initiatePayment">Processing…</span>
                        </button>
                        @if(!$hasActiveSub)
                        <button class="btn btn-o" onclick="document.getElementById('plan-section').scrollIntoView({behavior:'smooth'})">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Plans
                        </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ── PLAN CARDS ────────────────────────────────── --}}
        <div class="ms-card" id="plan-section">
            <div class="sec-hdr" style="padding-bottom:4px;">
                <h3 class="sec-title">Choose Your Plan</h3>
                @if($trialActive)
                <span style="font-size:11px;color:var(--txt2);">All plans include a 7-day free trial</span>
                @endif
            </div>
            <div class="plan-grid">
                @foreach($plans as $plan)
                @php
                    $isCurrent  = $currentPlan && $currentPlan->id === $plan->id;
                    $isSelected = $selected_plan_id === $plan->id;
                @endphp
                <div class="plan-card {{ $isCurrent || $isSelected ? 'active' : '' }}"
                     wire:click="$set('selected_plan_id', {{ $plan->id }})">
                    @if($isCurrent)
                    <div class="plan-badge">Current Plan</div>
                    @endif
                    <p class="plan-tier">{{ $plan->name }}</p>
                    <p class="plan-price" style="{{ $isCurrent ? 'color:var(--primary)' : '' }}">
                        KES {{ number_format($plan->price) }}<span>/mo</span>
                    </p>
                    <ul class="plan-features">
                        <li>
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            {{ $plan->truck_limit === -1 ? 'Unlimited trucks' : $plan->truck_limit . ' trucks' }}
                        </li>
                        <li>
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            {{ $plan->driver_limit === -1 ? 'Unlimited drivers' : $plan->driver_limit . ' drivers' }}
                        </li>
                        @if($plan->ai_limit > 0)
                        <li>
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            {{ $plan->ai_limit === -1 ? 'Unlimited AI queries' : $plan->ai_limit . ' AI queries/mo' }}
                        </li>
                        @endif
                        @foreach(($plan->modules ?? collect()) as $mod)
                        <li>
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            {{ $mod->name ?? $mod }}
                        </li>
                        @endforeach
                    </ul>
                    <button class="plan-btn {{ $isCurrent || $isSelected ? 'plan-btn-filled' : 'plan-btn-outline' }}">
                        {{ $isCurrent ? '✓ Current Plan' : ($isSelected ? '✓ Selected' : 'Select Plan') }}
                    </button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── BILLING HISTORY ───────────────────────────── --}}
        <div class="ms-card">
            <div class="sec-hdr">
                <h3 class="sec-title">Billing History</h3>
                <div class="ftabs">
                    <button class="ftab" :class="historyFilter==='all'?'on':''"    @click="historyFilter='all'">All</button>
                    <button class="ftab" :class="historyFilter==='active'?'on':''" @click="historyFilter='active'">Paid</button>
                    <button class="ftab" :class="historyFilter==='failed'?'on':''" @click="historyFilter='failed'">Failed</button>
                </div>
            </div>
            <div class="bh-wrap" style="margin-top:14px;">
                <table class="bh-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $pay)
                        @php
                            $pShow = match($pay->status ?? '') {
                                'active','paid','completed' => "historyFilter==='all'||historyFilter==='active'",
                                'failed','cancelled'        => "historyFilter==='all'||historyFilter==='failed'",
                                default                     => "historyFilter==='all'",
                            };
                            $pBadge = match($pay->status ?? '') {
                                'active','paid','completed' => 'sbadge-success',
                                'failed','cancelled'        => 'sbadge-danger',
                                default                     => 'sbadge-warning',
                            };
                        @endphp
                        <tr x-show="{{ $pShow }}">
                            <td class="mono">{{ $pay->created_at?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $pay->plan_name ?? $pay->plan?->name ?? '—' }}</td>
                            <td style="color:var(--txt2);">{{ ucfirst($pay->billing_cycle ?? 'Monthly') }}</td>
                            <td class="fw7">KES {{ number_format($pay->amount ?? 0) }}</td>
                            <td><span class="sbadge {{ $pBadge }}" style="font-size:10px;">{{ ucfirst($pay->status ?? 'pending') }}</span></td>
                            <td>
                                @if(in_array($pay->status ?? '', ['active','paid','completed']))
                                <button class="dl-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    PDF
                                </button>
                                @else
                                <span style="font-size:11px;color:var(--txt2);">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:28px 16px;color:var(--txt2);font-size:12px;">
                                No payment history yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:10px 22px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:11px;color:var(--txt2);">Showing last {{ $payments->count() }} records</span>
            </div>
        </div>

    </div>{{-- /ms-left --}}


    {{-- ═══════════════════ RIGHT COLUMN ══════════════════ --}}
    <div class="ms-right">

        {{-- ── KPI 2×2 ────────────────────────────────────── --}}
        <div class="ms-card">
            <div style="padding:16px 18px 4px;">
                <p style="font-size:13px;font-weight:700;color:var(--txt);margin:0;" class="dark:text-slate-200">Subscription Overview</p>
            </div>
            <div class="kpi-grid">
                <div class="kpi-item">
                    <div class="kpi-lbl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Days Left
                    </div>
                    <div class="kpi-val">{{ $daysLeft }}</div>
                    <div class="kpi-sub" style="{{ $daysLeft <= 3 ? 'color:var(--danger)' : '' }}">
                        {{ $trialActive ? 'of 7-day trial' : ($hasActiveSub ? 'of billing cycle' : 'Subscribe now') }}
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-lbl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Progress
                    </div>
                    <div class="kpi-val">{{ $progressPercent ?? 0 }}%</div>
                    <div class="kpi-sub">period used</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-lbl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Monthly Fee
                    </div>
                    <div class="kpi-val" style="font-size:16px;">{{ number_format(($currentPlan ?? $selectedPlan)?->price ?? 0) }}</div>
                    <div class="kpi-sub">KES / month</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-lbl">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Status
                    </div>
                    <div class="kpi-val" style="font-size:14px;font-weight:800;color:{{ $statusBadge['color'] }}">
                        {{ $statusBadge['label'] }}
                    </div>
                    <div class="kpi-sub">{{ $statusBadge['emoji'] }} current state</div>
                </div>
            </div>
            <div style="padding:0 18px 16px;"></div>
        </div>

        {{-- ── BILLING FORM ────────────────────────────────── --}}
        <div class="ms-card rc-pad">
            <p class="rc-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Billing Details
            </p>
            <div class="bf-field">
                <label class="bf-label">Full Name</label>
                <input class="bf-input" type="text" wire:model.live="billing_name" placeholder="Company name">
            </div>
            <div class="bf-field">
                <label class="bf-label">Email</label>
                <input class="bf-input" type="email" wire:model.live="billing_email" placeholder="billing@email.com">
            </div>
            <div class="bf-field">
                <label class="bf-label">Phone</label>
                <input class="bf-input" type="text" wire:model.live="billing_phone" placeholder="+254 7XX XXX XXX">
            </div>
            <div class="bf-field" style="margin-bottom:12px;">
                <label class="bf-label">Payment Method</label>
                <div class="pm-chips">
                    <button type="button" class="pm-chip" :class="pm==='mpesa'?'on':''" @click="pm='mpesa'">M-Pesa</button>
                    <button type="button" class="pm-chip" :class="pm==='card'?'on':''"  @click="pm='card'">Card</button>
                    <button type="button" class="pm-chip" :class="pm==='bank'?'on':''"  @click="pm='bank'">Bank</button>
                </div>
            </div>
            <button class="pay-btn" wire:click="initiatePayment" wire:loading.attr="disabled">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                <span wire:loading.remove wire:target="initiatePayment">
                    {{ $hasActiveSub ? 'Renew Subscription' : 'Subscribe Now' }}
                </span>
                <span wire:loading wire:target="initiatePayment">Processing…</span>
            </button>
        </div>

        {{-- ── QUICK ACTIONS ───────────────────────────────── --}}
        <div class="ms-card rc-pad">
            <p class="rc-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </p>
            <div class="qa-list">
                @if($payments->isNotEmpty())
                <button class="qa-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Latest Invoice
                    <svg class="qa-arr" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                @endif
                <button class="qa-item" wire:click="toggleAutoRenewal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Toggle Auto-Renewal
                    <svg class="qa-arr" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <a href="mailto:support@tanakihub.com" class="qa-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Contact Support
                    <svg class="qa-arr" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        {{-- ── AUTO-RENEWAL ────────────────────────────────── --}}
        <div class="ms-card rc-pad">
            <div class="toggle-row">
                <p class="rc-title" style="margin:0;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Auto-Renewal
                </p>
                <div x-data="{ on: {{ $autoRenewal ? 'true' : 'false' }} }"
                     class="toggle-row" style="margin:0;gap:8px;"
                     @click="on=!on; $wire.toggleAutoRenewal()">
                    <div class="toggle-sw" :class="on?'on':''">
                        <div class="toggle-kn"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;color:var(--txt);" x-text="on?'ON':'OFF'" class="dark:text-slate-200"></span>
                </div>
            </div>
            <p style="font-size:11px;color:var(--txt2);line-height:1.5;margin:8px 0 0;">
                When enabled, your subscription auto-renews and your payment method is charged before expiry.
            </p>
            @if($renewalDate)
            <div class="renew-date" style="{{ $autoRenewal ? '' : 'border-color:var(--warning);color:#D97706;' }}">
                @if($autoRenewal)
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Next renewal: <strong>{{ $renewalDate->format('d M Y') }}</strong>
                @else
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--warning)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Subscription will <strong>not renew</strong> automatically
                @endif
            </div>
            @endif
        </div>

    </div>{{-- /ms-right --}}

</div>{{-- /ms-grid --}}
@endif

</x-filament-panels::page>
