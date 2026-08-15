<div class="stats-grid">
    @foreach ($this->getStats() as $stat)
        <div class="stat-card">
            <div class="stat-icon">
                <i class="{{ $stat->getIcon() }}"></i>
            </div>
            <div class="stat-label">{{ $stat->getLabel() }}</div>
            <div class="stat-value">{{ $stat->getValue() }}</div>
            <div class="stat-subtext">{{ $stat->getDescription() }}</div>
        </div>
    @endforeach
</div>
