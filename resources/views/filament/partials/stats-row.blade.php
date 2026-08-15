@php
    /**
     * @var array<int, array{label: string, value: string, icon: string, color?: string, sub?: string}> $stats
     */
@endphp
<div class="tm-stats-row">
    @foreach ($stats as $stat)
        <div class="tm-stat-card">
            <div class="tm-stat-icon {{ $stat['color'] ?? '' }}">
                <x-filament::icon :icon="$stat['icon']" />
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
