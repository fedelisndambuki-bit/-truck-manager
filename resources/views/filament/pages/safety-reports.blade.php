<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />',
        'title' => 'Safety Reports',
        'subtitle' => 'Generate downloadable PDF reports for every area of the EHS system',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->getReportCards() as $card)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">{{ $card['title'] }}</div>
                            <div class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">{{ $card['count'] }}</div>
                        </div>
                    </div>
                    <button type="button" wire:click="{{ $card['action'] }}" class="fi-btn fi-btn-size-sm fi-color-gray flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3.5 w-3.5" />
                        PDF
                    </button>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
