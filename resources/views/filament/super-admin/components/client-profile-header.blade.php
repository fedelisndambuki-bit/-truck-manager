@php
    $record = $getRecord();
    if (!$record) {
        return;
    }
    $initials = strtoupper(substr($record->name ?? 'CL', 0, 2));
    $color = '#2f4f4f';
@endphp

<div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
    <div class="flex items-center justify-center w-12 h-12 rounded-lg text-white font-bold text-lg" style="background-color: {{ $color }};">
        {{ $initials }}
    </div>
    <div class="flex-1 min-w-0">
        <h2 class="text-sm font-bold text-gray-900 truncate">{{ $record->name }}</h2>
        <p class="text-xs text-gray-500 truncate">{{ $record->email }}</p>
        <p class="text-[10px] text-gray-400 font-mono mt-1">#CLT-{{ str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>
</div>
