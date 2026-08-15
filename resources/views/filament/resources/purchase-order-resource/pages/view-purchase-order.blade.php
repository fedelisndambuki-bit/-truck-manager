@php
    use App\Filament\Resources\PurchaseOrderResource;
    $order = $record;
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'title' => $order->po_number,
        'subtitle' => $order->supplier->company_name . ' — ' . $order->approval_status,
    ])

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Status</div>
            <div class="mt-1"><x-filament::badge :color="PurchaseOrderResource::getStatusColor($order->approval_status)">{{ $order->approval_status }}</x-filament::badge></div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Subtotal</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Tax / Discount</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">+{{ number_format($order->tax_amount, 2) }} / -{{ number_format($order->discount_amount, 2) }}</div>
        </div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;">
            <div style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;">Total</div>
            <div style="font-size:20px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Line Items</x-slot>
        <table class="fi-ta-table w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="py-2">Material</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                @forelse ($this->getItems() as $item)
                    <tr class="border-t border-gray-100 dark:border-white/10">
                        <td class="py-2">{{ $item->material?->name }}</td>
                        <td>{{ number_format($item->quantity, 2) }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No line items</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Delivery</x-slot>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Address</dt><dd>{{ $order->delivery_address ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Expected Delivery</dt><dd>{{ $order->expected_delivery?->format('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Payment Terms</dt><dd>{{ $order->payment_terms ?? '—' }}</dd></div>
            </dl>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Supplier</x-slot>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Company</dt><dd>{{ $order->supplier->company_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Contact</dt><dd>{{ $order->supplier->contact_person ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $order->supplier->phone ?? '—' }}</dd></div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
