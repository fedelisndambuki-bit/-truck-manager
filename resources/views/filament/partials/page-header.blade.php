@php
    /**
     * @var string $icon Raw inner SVG markup (path elements only)
     * @var string $title
     * @var string $subtitle
     * @var array<int, array{label: string, value: string, icon: string, color?: string, sub?: string}>|null $stats
     */
@endphp
<div class="tm-page-header">
    <div class="tm-page-header-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            {!! $icon !!}
        </svg>
    </div>
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
</div>
@isset($stats)
    @include('filament.partials.stats-row', ['stats' => $stats])
@endisset
