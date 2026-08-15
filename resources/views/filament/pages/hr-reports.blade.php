<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12h-9m9-3h-9m4.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
        'title' => 'HR Reports',
        'subtitle' => 'Generate downloadable PDF reports across the Labour & HR module',
    ])

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->getReportCards() as $card)
            <x-filament::section>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="font-semibold text-gray-950 dark:text-white">{{ $card['title'] }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] }}</div>
                        <div class="mt-2 text-xs font-medium text-primary-600 dark:text-primary-400">{{ $card['count'] }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Downloadable Reports</x-slot>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Use the buttons at the top of this page to generate a PDF export of the Workforce, Attendance, Leave, Payroll, Training Compliance, or Certification Compliance reports. Additional report types will appear here as they become available.
        </p>
    </x-filament::section>
</x-filament-panels::page>
