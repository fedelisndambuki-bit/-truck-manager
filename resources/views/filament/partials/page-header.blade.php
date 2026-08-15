@php
    /**
     * @var string $icon Heroicon name, e.g. "heroicon-o-document-text"
     * @var string $title
     * @var string $subtitle
     * @var array<int, array{label: string, value: string, icon: string, color?: string, sub?: string}>|null $stats
     */
@endphp
<div class="tm-page-header">
    <div class="tm-page-header-icon">
        <x-filament::icon :icon="$icon" />
    </div>
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
</div>
@isset($stats)
    @include('filament.partials.stats-row', ['stats' => $stats])
@endisset
