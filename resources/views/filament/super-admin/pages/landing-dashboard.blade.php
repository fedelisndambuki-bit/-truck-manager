<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Stats Widgets will go here -->
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500">Total Visitors</div>
            <div class="text-2xl font-bold">12,345</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500">Trial Signups</div>
            <div class="text-2xl font-bold">1,234</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500">Conversion Rate</div>
            <div class="text-2xl font-bold">10%</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500">Most Clicked Plan</div>
            <div class="text-2xl font-bold">Growth Plan</div>
        </x-filament::section>
    </div>

    <!-- Table Section -->
    <x-filament::section>
        <x-slot name="heading">
            Performance Overview
        </x-slot>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Visitors</th>
                        <th scope="col" class="px-6 py-3">Trials</th>
                        <th scope="col" class="px-6 py-3">Paid Conversions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Static data for now, will replace with dynamic later -->
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4">2026-03-14</td>
                        <td class="px-6 py-4">150</td>
                        <td class="px-6 py-4">12</td>
                        <td class="px-6 py-4">3</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
