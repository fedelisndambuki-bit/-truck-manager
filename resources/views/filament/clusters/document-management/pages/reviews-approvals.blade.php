@php
    $rows = $this->getRows();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286z" />',
        'title' => 'Reviews & Approvals',
        'subtitle' => 'Unified approval inbox across documents, drawings, RFIs, method statements and submittals',
    ])

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Pending Approvals</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Title</th>
                        <th>Step</th>
                        <th>Approver Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><span class="badge info">{{ $row['entity_label'] }}</span></td>
                            <td class="row-title">{{ $row['entity_number'] }}</td>
                            <td>{{ $row['entity_title'] }}</td>
                            <td>{{ $row['approval']->step_order }}</td>
                            <td>{{ $row['approval']->approver_role }}</td>
                            <td class="actions">
                                <button wire:click="approve({{ $row['approval']->id }})" class="action-btn view" title="Approve">
                                    <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                                </button>
                                <button wire:click="reject({{ $row['approval']->id }})" class="action-btn edit" title="Reject">
                                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No pending approvals</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
