@php
    /**
     * @var string $icon Raw inner SVG markup (path elements only)
     * @var string $title
     * @var string $subtitle
     */
@endphp
<div class="page-header-custom">
    <div class="page-header-content">
        <div class="page-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                {!! $icon !!}
            </svg>
        </div>
        <div>
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
    </div>
</div>
