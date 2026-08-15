@php
    $rfqs = $this->getInvitedRfqs();
    $quotations = $this->getMyQuotations();
    $orders = $this->getMyPurchaseOrders();
@endphp
<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Open RFQs</x-slot>
            @forelse ($rfqs as $rfq)
                <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-t-0 dark:border-white/5">
                    <span>{{ $rfq->rfq_number }} — {{ $rfq->title ?? 'Untitled' }}</span>
                    <span class="fi-badge inline-flex items-center rounded-md bg-info-50 px-2 py-0.5 text-xs font-medium text-info-700 dark:bg-info-500/10 dark:text-info-400">{{ $rfq->status }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No RFQ invitations yet.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">My Quotations</x-slot>
            @forelse ($quotations as $quotation)
                <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-t-0 dark:border-white/5">
                    <span>{{ $quotation->quotation_number }} — {{ number_format($quotation->total_amount, 2) }}</span>
                    <span class="fi-badge inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                        {{ $quotation->status === 'Accepted' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400' }}">
                        {{ $quotation->status }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No quotations submitted yet.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">My Purchase Orders</x-slot>
            @forelse ($orders as $order)
                <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm first:border-t-0 dark:border-white/5">
                    <span>{{ $order->po_number }} — {{ number_format($order->total_amount, 2) }}</span>
                    @if (! $order->delivery_confirmed_at)
                        <button
                            wire:click="confirmDelivery({{ $order->id }})"
                            wire:confirm="Confirm delivery for {{ $order->po_number }}?"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-7 px-2 text-xs inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500"
                        >
                            Confirm Delivery
                        </button>
                    @else
                        <span class="fi-badge inline-flex items-center rounded-md bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Delivered</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No purchase orders yet.</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
