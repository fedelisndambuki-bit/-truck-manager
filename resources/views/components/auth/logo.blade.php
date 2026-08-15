@props([
    'layout' => 'horizontal', // 'horizontal' (hero panel, left-aligned) | 'stacked' (auth card, centered)
    'markSize' => 44, // px
])

@php
    $wrapperClasses = $layout === 'stacked'
        ? 'flex flex-col items-center text-center'
        : 'flex items-center gap-3';

    $wordSize = $layout === 'stacked' ? 'text-2xl' : 'text-xl';
    $hubTracking = $layout === 'stacked' ? 'tracking-[4px]' : 'tracking-[3px]';
    $ruleWidth = $layout === 'stacked' ? 'w-10' : 'w-[34px]';
    $wordColor = $layout === 'stacked' ? 'text-tanaki-ink' : 'text-white';
@endphp

<div {{ $attributes->class([$wrapperClasses]) }}>
    <span class="shrink-0 {{ $layout === 'stacked' ? 'mb-1.5' : '' }}" style="width: {{ $markSize }}px; height: {{ $markSize }}px;">
        {{-- Official brand icon — light-background variant on the white auth
             card (stacked), dark-background variant on the hero photo
             (horizontal). --}}
        <img
            src="{{ asset('images/brand/tanaki-hub-icon-' . ($layout === 'stacked' ? 'light' : 'dark') . '.png') }}"
            alt="Tanaki Hub"
            class="h-full w-full object-contain"
        >
    </span>

    <div class="flex flex-col {{ $layout === 'stacked' ? 'items-center' : '' }} leading-none">
        <span class="{{ $wordSize }} {{ $wordColor }} font-extrabold tracking-wide">TANAKI</span>
        <span class="mt-0.5 text-[13px] font-bold {{ $hubTracking }} text-tanaki-teal">HUB</span>
        <span class="{{ $layout === 'stacked' ? 'mt-1' : 'mt-1.5' }} h-0.5 {{ $ruleWidth }} bg-tanaki-teal"></span>
    </div>
</div>
