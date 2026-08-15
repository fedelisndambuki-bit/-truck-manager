@php
    $cards = $this->getHubCards();
@endphp
<x-filament-panels::page>
    <x-filament::section class="mb-4">
        {{ $this->form }}
    </x-filament::section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$card['icon'] ?? 'heroicon-o-document-text'" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $card['label'] }}</div>
                    </div>
                </div>
                @if (!empty($card['description']))
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                @endif
                <div class="mt-4">
                    <button wire:click="{{ $card['action'] }}"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500">
                        <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-4 w-4" />
                        Generate PDF
                    </button>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
