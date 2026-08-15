@props(['partner'])

@php
    $tag = $partner->website_url ? 'a' : 'div';
    $src = \Illuminate\Support\Str::startsWith($partner->logo, ['http://', 'https://'])
        ? $partner->logo
        : asset('storage/' . $partner->logo);
@endphp

<{{ $tag }}
    @if ($partner->website_url) href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" @endif
    class="partner-logo-item flex shrink-0 items-center justify-center h-16 min-w-[130px] max-w-[170px] px-2"
    title="{{ $partner->name }}"
>
    <img
        src="{{ $src }}"
        alt="{{ $partner->name }}"
        loading="lazy"
        class="max-w-[140px] max-h-[55px] w-auto h-auto object-contain {{ $partner->grayscale ? 'grayscale opacity-[0.85]' : '' }}"
    >
</{{ $tag }}>
