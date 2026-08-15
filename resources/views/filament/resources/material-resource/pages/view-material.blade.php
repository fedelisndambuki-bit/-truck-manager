@php
    use App\Filament\Resources\MaterialResource;
    $material = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
        'title' => $material->name,
        'subtitle' => $material->material_code . ' — ' . ($material->category?->name ?? 'Uncategorized'),
    ])

    <style>
        .tab-bar{display:flex;gap:2px;background:#F1F5F9;border-radius:10px;padding:4px;border:1px solid #E2E8F0;overflow-x:auto;margin-bottom:20px}
        .tab{padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;transition:all .15s ease;white-space:nowrap}
        .tab.active{background:#FFFFFF;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #E2E8F0}
        .ov-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px}
        .ov-label{font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .ov-value{font-size:20px;font-weight:700;color:#0F172A;margin-top:4px}
    </style>

    <div x-data="{ activeTab: 'overview' }">
        <div class="tab-bar">
            @foreach ([
                'overview' => 'Overview', 'stock' => 'Stock Levels', 'purchases' => 'Purchase History',
                'consumption' => 'Consumption', 'transfers' => 'Transfers', 'returns' => 'Returns',
                'wastage' => 'Wastage', 'suppliers' => 'Suppliers', 'documents' => 'Documents',
                'analytics' => 'Analytics', 'reports' => 'Reports',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Stock Status</div>
                    <div class="mt-1"><x-filament::badge :color="MaterialResource::getStockStatusColor($material->stock_status)">{{ $material->stock_status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">On Hand</div>
                    <div class="ov-value">{{ number_format($material->quantity_on_hand, 2) }} {{ $material->unit }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Inventory Value</div>
                    <div class="ov-value">{{ $currency }} {{ number_format($material->inventory_value, 2) }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="MaterialResource::getStatusColor($material->status)">{{ $material->status }}</x-filament::badge></div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Material Details</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Category</dt><dd>{{ $material->category?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Subcategory</dt><dd>{{ $material->subcategory?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Unit of Measure</dt><dd>{{ $material->unit }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Barcode</dt><dd>{{ $material->barcode ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Costing</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Unit Cost</dt><dd>{{ $currency }} {{ number_format($material->unit_cost, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Standard Cost</dt><dd>{{ $currency }} {{ number_format($material->standard_cost ?? 0, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Average Cost</dt><dd>{{ $currency }} {{ number_format($material->average_cost ?? 0, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Reorder Level</dt><dd>{{ $material->reorder_level }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>

            @if ($material->qr_code)
                <div class="mt-6">
                    <x-filament::section>
                        <x-slot name="heading">QR Code</x-slot>
                        <div class="flex items-center gap-4">
                            {!! $material->qr_code !!}
                            <span class="text-sm text-gray-500">Encodes: {{ $material->material_code }}</span>
                        </div>
                    </x-filament::section>
                </div>
            @endif
        </div>

        {{-- STOCK LEVELS --}}
        <div x-show="activeTab === 'stock'">
            <x-filament::section>
                <x-slot name="heading">Stock by Warehouse / Project / Site</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Warehouse</th><th>Project</th><th>Site</th><th>Batch</th><th>On Hand</th><th>Reserved</th><th>Available</th><th>Damaged</th><th>Expiry</th></tr></thead>
                    <tbody>
                        @forelse ($this->getStockLevels() as $level)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $level->warehouse?->name ?? '—' }}</td>
                                <td>{{ $level->project?->name ?? '—' }}</td>
                                <td>{{ $level->site?->name ?? '—' }}</td>
                                <td>{{ $level->batch_number ?: '—' }}</td>
                                <td>{{ number_format($level->quantity_on_hand, 2) }}</td>
                                <td>{{ number_format($level->reserved_quantity, 2) }}</td>
                                <td>{{ number_format($level->available_quantity, 2) }}</td>
                                <td>{{ number_format($level->damaged_quantity, 2) }}</td>
                                <td>{{ $level->expiry_date?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-4 text-center text-gray-400">No stock recorded in any warehouse yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PURCHASE HISTORY --}}
        <div x-show="activeTab === 'purchases'">
            <x-filament::section>
                <x-slot name="heading">Purchase History</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">PO #</th><th>Supplier</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPurchaseHistory() as $item)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $item->purchaseOrder?->po_number }}</td>
                                <td>{{ $item->purchaseOrder?->supplier?->company_name ?? '—' }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No purchase history yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- CONSUMPTION --}}
        <div x-show="activeTab === 'consumption'">
            <x-filament::section>
                <x-slot name="heading">Consumption History</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Project</th><th>Site</th><th>Activity</th><th>Quantity</th><th>Cost</th></tr></thead>
                    <tbody>
                        @forelse ($this->getConsumptionHistory() as $entry)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $entry->date->format('d M Y') }}</td>
                                <td>{{ $entry->project?->name ?? '—' }}</td>
                                <td>{{ $entry->site?->name ?? '—' }}</td>
                                <td>{{ $entry->activity ?? '—' }}</td>
                                <td>{{ number_format($entry->quantity_used, 2) }}</td>
                                <td>{{ number_format($entry->cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No consumption recorded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- TRANSFERS --}}
        <div x-show="activeTab === 'transfers'">
            <x-filament::section>
                <x-slot name="heading">Stock Transfers</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Transfer #</th><th>From</th><th>To</th><th>Quantity</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getTransferHistory() as $transfer)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $transfer->transfer_number }}</td>
                                <td>{{ $transfer->sourceWarehouse?->name ?? '—' }}</td>
                                <td>{{ $transfer->destinationWarehouse?->name ?? '—' }}</td>
                                <td>{{ number_format($transfer->quantity, 2) }}</td>
                                <td>{{ $transfer->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No transfers recorded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- RETURNS --}}
        <div x-show="activeTab === 'returns'">
            <x-filament::section>
                <x-slot name="heading">Returns</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Warehouse</th><th>Quantity</th><th>Condition</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getReturnHistory() as $return)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $return->warehouse?->name ?? '—' }}</td>
                                <td>{{ number_format($return->return_quantity, 2) }}</td>
                                <td>{{ $return->condition }}</td>
                                <td>{{ $return->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No returns recorded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- WASTAGE --}}
        <div x-show="activeTab === 'wastage'">
            <x-filament::section>
                <x-slot name="heading">Wastage</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Type</th><th>Quantity</th><th>Waste %</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getWastageHistory() as $wastage)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $wastage->waste_type }}</td>
                                <td>{{ number_format($wastage->quantity, 2) }}</td>
                                <td>{{ $wastage->waste_percentage ?? '—' }}{{ $wastage->waste_percentage !== null ? '%' : '' }}</td>
                                <td>{{ $wastage->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No wastage recorded yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- SUPPLIERS --}}
        <div x-show="activeTab === 'suppliers'">
            <x-filament::section>
                <x-slot name="heading">Preferred Supplier</x-slot>
                @if ($material->preferredSupplier)
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Company</dt><dd>{{ $material->preferredSupplier->company_name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Contact</dt><dd>{{ $material->preferredSupplier->contact_person ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Rating</dt><dd>{{ $material->preferredSupplier->performance_rating ?? '—' }} / 5</dd></div>
                    </dl>
                @else
                    <p class="text-sm text-gray-400">No preferred supplier set.</p>
                @endif
            </x-filament::section>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="activeTab === 'documents'">
            <x-filament::section>
                <x-slot name="heading">Documents</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Title</th><th>Category</th><th>Uploaded</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($this->getDocuments() as $document)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $document->title }}</td>
                                <td>{{ $document->category }}</td>
                                <td>{{ $document->created_at->format('d M Y') }}</td>
                                <td>
                                    @if ($document->file_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" class="text-primary-600 hover:underline text-xs font-semibold">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No documents on file</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- ANALYTICS --}}
        <div x-show="activeTab === 'analytics'">
            <x-filament::section>
                <x-slot name="heading">Analytics</x-slot>
                <p class="text-sm text-gray-400">Consumption trend, cost trend, and turnover charts for this material will appear here.</p>
            </x-filament::section>
        </div>

        {{-- REPORTS --}}
        <div x-show="activeTab === 'reports'">
            <x-filament::section>
                <x-slot name="heading">Reports</x-slot>
                <p class="text-sm text-gray-400">Use the module Reports page to generate material-specific PDF exports.</p>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
