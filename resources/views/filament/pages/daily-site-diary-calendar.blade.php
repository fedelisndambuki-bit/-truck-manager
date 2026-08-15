@php
    $statusColors = [
        'Draft' => 'gray', 'Submitted' => 'warning', 'Reviewed' => 'info',
        'Approved' => 'success', 'Rejected' => 'danger', 'Locked' => 'gray',
    ];
    $cursor = \Carbon\Carbon::parse($this->cursor);
    $days = $this->getDays();
    $diariesByDate = $this->getDiariesByDate();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
        'title' => 'Diary Calendar',
        'subtitle' => 'Monthly and weekly view of diary status, weather, and missing entries',
    ])

    <style>
        .cal-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px}
        .cal-nav-btn{padding:6px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;font-size:12px;font-weight:600;cursor:pointer;color:#374151}
        .cal-nav-btn:hover{background:#f9fafb}
        .cal-mode-group{display:flex;gap:2px;background:#F1F5F9;border-radius:8px;padding:4px;border:1px solid #E2E8F0}
        .cal-mode-btn{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B}
        .cal-mode-btn.active{background:#fff;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px}
        .cal-dow{text-align:center;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;padding-bottom:6px}
        .cal-cell{background:#fff;border:1px solid #E2E8F0;border-radius:10px;min-height:100px;padding:8px;position:relative}
        .cal-cell.other-month{opacity:.4}
        .cal-cell.today{border-color:#3b82f6;border-width:2px}
        .cal-cell.missing{background:#fef2f2;border-color:#fecaca}
        .cal-day-num{font-size:12px;font-weight:700;color:#0F172A}
        .cal-diary-chip{margin-top:4px;font-size:10px;padding:3px 6px;border-radius:6px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .cal-missing-tag{margin-top:4px;font-size:9px;color:#dc2626;font-weight:700;text-transform:uppercase}
    </style>

    <div class="cal-toolbar">
        <div style="display:flex;gap:8px;align-items:center;">
            <button wire:click="previousPeriod" class="cal-nav-btn">←</button>
            <button wire:click="goToday" class="cal-nav-btn">Today</button>
            <button wire:click="nextPeriod" class="cal-nav-btn">→</button>
            <span style="font-weight:700;font-size:16px;color:#0F172A;margin-left:8px;">{{ $cursor->format('F Y') }}</span>
        </div>
        <div class="cal-mode-group">
            <div class="cal-mode-btn {{ $viewMode === 'month' ? 'active' : '' }}" wire:click="setViewMode('month')">Month</div>
            <div class="cal-mode-btn {{ $viewMode === 'week' ? 'active' : '' }}" wire:click="setViewMode('week')">Week</div>
        </div>
    </div>

    <div class="cal-grid">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow)
            <div class="cal-dow">{{ $dow }}</div>
        @endforeach

        @foreach ($days as $day)
            @php
                $dateKey = $day->toDateString();
                $dayDiaries = $diariesByDate[$dateKey] ?? [];
                $missing = $this->isMissingDiary($dateKey);
            @endphp
            <div class="cal-cell {{ $day->month !== $cursor->month ? 'other-month' : '' }} {{ $day->isToday() ? 'today' : '' }} {{ $missing ? 'missing' : '' }}">
                <div class="cal-day-num">{{ $day->day }}</div>

                @foreach (array_slice($dayDiaries, 0, 3) as $diary)
                    <a href="{{ \App\Filament\Resources\DailySiteDiaryResource::getUrl('view', ['record' => $diary['id']]) }}"
                       class="cal-diary-chip badge {{ $statusColors[$diary['status']] ?? 'gray' }}">
                        {{ $diary['site']['name'] ?? 'Site' }}
                    </a>
                @endforeach

                @if (count($dayDiaries) > 3)
                    <div class="text-xs text-gray-400 mt-1">+{{ count($dayDiaries) - 3 }} more</div>
                @endif

                @if ($missing)
                    <div class="cal-missing-tag">Missing Diary</div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
