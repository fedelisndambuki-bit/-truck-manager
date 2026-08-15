@php
    /**
     * @var array<int, array{label: string, value: string, icon: string, color?: string, sub?: string}> $stats
     */
@endphp
<div class="tm-stats-row">
    @foreach ($stats as $stat)
        <div class="tm-stat-card">
            <div class="tm-stat-icon {{ $stat['color'] ?? '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    {!! $stat['icon'] !!}
                </svg>
            </div>
            <div>
                <div class="tm-stat-label">{{ $stat['label'] }}</div>
                <div class="tm-stat-value">{{ $stat['value'] }}</div>
                @if (!empty($stat['sub']))
                    <div class="tm-stat-sub">{{ $stat['sub'] }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
