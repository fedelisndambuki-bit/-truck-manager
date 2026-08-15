<div class="relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
    <button
        @click="open = !open"
        class="tm-pill tm-curr-trigger"
        type="button"
    >
        <span class="tm-curr-symbol">{{ $currencies[$currentCurrency]['symbol'] }}</span>
        <span>{{ $currentCurrency }}</span>
        <svg class="tm-chevron" :class="{ 'tm-chevron-open': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="tm-dropdown tm-curr-dropdown"
        style="display: none;"
    >
        <div class="tm-menu-label">Currency</div>
        @foreach ($currencies as $code => $data)
            <button
                wire:click="setCurrency('{{ $code }}')"
                @click="open = false"
                class="tm-menu-item {{ $currentCurrency === $code ? 'tm-menu-item-sel' : '' }}"
                type="button"
            >
                <span class="tm-curr-symbol-box">{{ $data['symbol'] }}</span>
                <span>{{ $data['label'] }}</span>
                @if ($currentCurrency === $code)
                    <svg class="tm-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                @endif
            </button>
        @endforeach
    </div>
</div>
