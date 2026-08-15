@php
    $spendByProject = $this->getSpendByProject();
    $variance = $this->getBudgetVariance();
@endphp
<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Spend by Project (90 Days)</x-slot>
            @forelse ($spendByProject as $project => $amount)
                <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-t-0 dark:border-white/5">
                    <span>{{ $project }}</span>
                    <span class="font-semibold">{{ number_format($amount, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No project-linked purchase orders in the last 90 days.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Budget Variance ({{ now()->year }})</x-slot>
            @forelse ($variance as $row)
                <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-t-0 dark:border-white/5">
                    <span>{{ $row['label'] }}</span>
                    <span class="font-semibold {{ $row['variance'] < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                        {{ number_format($row['variance'], 2) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No active budgets configured for this financial year.</p>
            @endforelse
        </x-filament::section>
    </div>

    <x-filament::section class="mt-4">
        <x-slot name="heading">Purchase Savings (Awarded RFQs)</x-slot>
        <p class="text-2xl font-bold">{{ number_format($this->getPurchaseSavings(), 2) }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Sum of (highest quotation − awarded quotation) across every RFQ awarded to date.</p>
    </x-filament::section>
</x-filament-panels::page>
