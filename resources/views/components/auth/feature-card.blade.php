@props([
    'title',
    'path', // raw SVG <path>/<circle> markup for the icon, matched exactly to the reference
])

<div {{ $attributes->class(['tk-feature flex items-center gap-3.5 rounded-xl px-[18px] py-4']) }}>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7 shrink-0">
        {!! $path !!}
    </svg>
    <span class="text-[16.5px] font-semibold leading-tight text-white">{{ $title }}</span>
</div>
