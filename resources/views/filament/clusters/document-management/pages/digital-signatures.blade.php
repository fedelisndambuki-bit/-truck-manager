@php
    $rows = $this->getRows();
    $unsigned = $this->getUnsignedApproved();
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33" />',
        'title' => 'Digital Signatures',
        'subtitle' => 'Electronic sign-off history with timestamp and verification',
    ])

    @if (count($unsigned))
        <div class="table-section" style="margin-bottom:20px">
            <div class="table-header-custom"><div class="table-title">Awaiting Signature</div></div>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Type</th><th>Title</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($unsigned as $item)
                            <tr>
                                <td><span class="badge info">{{ $item['label'] }}</span></td>
                                <td class="row-title">{{ $item['title'] }}</td>
                                <td>
                                    <div style="display:flex; gap:8px; align-items:center;">
                                        <input type="text" wire:model="signerNames.{{ $item['id'] }}" placeholder="Type your name to sign"
                                               style="border:1px solid #e5e7eb; border-radius:6px; padding:4px 8px; font-size:12px;">
                                        <button wire:click="sign('{{ addslashes($item['type']) }}', {{ $item['id'] }})" class="action-btn view" title="Sign">
                                            <x-filament::icon icon="heroicon-o-pencil" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="table-section">
        <div class="table-header-custom"><div class="table-title">Signature Register</div></div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Signer</th>
                        <th>Signed At</th>
                        <th>Hash</th>
                        <th>Verified</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><span class="badge info">{{ $row['entity_label'] }}</span></td>
                            <td class="row-title">{{ $row['entity_title'] }}</td>
                            <td>{{ $row['signature']->signer_name ?? $row['signature']->signer?->full_name }}</td>
                            <td>{{ $row['signature']->signed_at?->format('d M Y H:i') }}</td>
                            <td style="font-family:monospace;font-size:10px">{{ \Illuminate\Support\Str::limit($row['signature']->signature_hash, 16) }}</td>
                            <td><span class="badge {{ $row['signature']->verified ? 'success' : 'danger' }}">{{ $row['signature']->verified ? 'Verified' : 'Unverified' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No signatures recorded yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
