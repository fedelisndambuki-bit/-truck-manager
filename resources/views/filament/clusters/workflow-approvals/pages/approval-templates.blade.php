@php
    $templates = $this->getTemplates();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
        'title' => 'Approval Templates',
        'subtitle' => 'Start from a proven pattern instead of a blank canvas — clone a template, then customize and publish it.',
    ])

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($templates as $template)
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-o-rectangle-group" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $template->name }}</div>
                        <div class="text-xs text-gray-400">{{ count($template->definition['nodes'] ?? []) }} nodes</div>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $template->description }}</p>
                <div class="mt-4">
                    <button wire:click="useTemplate({{ $template->id }})"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-9 pl-3 pr-4 text-sm inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500">
                        <x-filament::icon icon="heroicon-o-document-duplicate" class="h-4 w-4" />
                        Use Template
                    </button>
                </div>
            </x-filament::section>
        @endforeach

        @if ($templates->isEmpty())
            <p class="text-sm text-gray-400 col-span-full">No templates available yet.</p>
        @endif
    </div>
</x-filament-panels::page>
