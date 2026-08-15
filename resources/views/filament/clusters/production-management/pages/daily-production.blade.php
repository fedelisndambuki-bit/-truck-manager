<x-filament-panels::page>
    <x-filament::section>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Quarry</label>
                <select wire:model.live="extraction_site_id" class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                    @foreach ($this->getSiteOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Stock Reconciliation (As of Now)</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-4">Material</th>
                        <th class="py-2 pr-4">Opening</th>
                        <th class="py-2 pr-4">Production</th>
                        <th class="py-2 pr-4">Transfers In</th>
                        <th class="py-2 pr-4">Transfers Out</th>
                        <th class="py-2 pr-4">Dispatches</th>
                        <th class="py-2 pr-4">Closing</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getReconciliation() as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-4 font-medium">{{ $row['material'] }}</td>
                            <td class="py-2 pr-4">{{ number_format($row['opening'], 1) }} t</td>
                            <td class="py-2 pr-4 text-success-600 dark:text-success-400">+{{ number_format($row['production'], 1) }} t</td>
                            <td class="py-2 pr-4 text-success-600 dark:text-success-400">+{{ number_format($row['transfers_in'], 1) }} t</td>
                            <td class="py-2 pr-4 text-danger-600 dark:text-danger-400">-{{ number_format($row['transfers_out'], 1) }} t</td>
                            <td class="py-2 pr-4 text-danger-600 dark:text-danger-400">-{{ number_format($row['dispatches'], 1) }} t</td>
                            <td class="py-2 pr-4 font-semibold">{{ number_format($row['closing'], 1) }} t</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">No stockpiles for this quarry yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Remarks</x-slot>

        <textarea wire:model="remarks" rows="3" class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5"></textarea>

        <x-filament::button wire:click="saveRemarks" class="mt-3">
            Save Remarks
        </x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
