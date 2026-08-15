@php
    $widgets = $this->getWidgets();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />',
        'title' => static::$title,
        'subtitle' => 'Personalize which KPIs you see and in what order — changes are saved per user.',
    ])

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($widgets as $item)
            <x-filament::section :compact="true" :class="! $item['is_visible'] ? 'opacity-50' : ''">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-filament::icon :icon="$item['icon']" class="h-5 w-5 text-primary-600 dark:text-primary-400 flex-shrink-0" />
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $item['label'] }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $item['value'] }}</div>
                <div class="mt-3 flex items-center gap-1">
                    <button wire:click="moveUp('{{ $item['key'] }}')" class="fi-btn rounded-md h-6 w-6 inline-flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-chevron-up" class="h-4 w-4" />
                    </button>
                    <button wire:click="moveDown('{{ $item['key'] }}')" class="fi-btn rounded-md h-6 w-6 inline-flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <x-filament::icon icon="heroicon-o-chevron-down" class="h-4 w-4" />
                    </button>
                    <button wire:click="toggleVisibility('{{ $item['key'] }}')" class="fi-btn rounded-md h-6 w-6 inline-flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <x-filament::icon :icon="$item['is_visible'] ? 'heroicon-o-eye' : 'heroicon-o-eye-slash'" class="h-4 w-4" />
                    </button>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
