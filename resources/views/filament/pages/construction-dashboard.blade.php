<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v13.5A1.5 1.5 0 005.25 18h13.5a1.5 1.5 0 001.5-1.5V3M3.75 3h16.5M3.75 3l5.25 6M20.25 3l-5.25 6" />',
        'title' => 'Construction Dashboard',
        'subtitle' => 'Overview of projects, sites, equipment and site activity',
    ])

    <x-filament-widgets::widgets :widgets="$this->getWidgets()" :columns="$this->getColumns()" />
</x-filament-panels::page>
