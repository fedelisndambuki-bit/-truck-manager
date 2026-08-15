@php
    $bays = $this->getBays();
@endphp
<x-filament-panels::page>
    <div wire:poll.10s class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($bays as $row)
            @php
                $bay = $row['bay'];
                $truck = $row['truck'];
                $instruction = $row['instruction'];
                $status = $instruction?->status ?? ($bay->status === 'Available' ? 'Waiting' : $bay->status);
                $colorClasses = match (true) {
                    $status === 'Completed' => 'border-success-400 bg-success-50 dark:bg-success-500/10',
                    $status === 'Loading' => 'border-info-400 bg-info-50 dark:bg-info-500/10',
                    $status === 'Paused' => 'border-warning-400 bg-warning-50 dark:bg-warning-500/10',
                    $status === 'Accepted' => 'border-primary-400 bg-primary-50 dark:bg-primary-500/10',
                    in_array($status, ['Maintenance', 'Closed']) => 'border-danger-400 bg-danger-50 dark:bg-danger-500/10',
                    default => 'border-gray-200 bg-white dark:border-white/10 dark:bg-white/5',
                };
            @endphp
            <div class="rounded-xl border-2 p-4 shadow-sm {{ $colorClasses }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $bay->bay_code }} — {{ $bay->name }}</span>
                    <span class="rounded-md bg-black/5 px-2 py-0.5 text-xs font-semibold uppercase text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $status }}</span>
                </div>

                @if ($truck)
                    <div class="mt-3">
                        <div class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $truck->vehicle_registration }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $truck->customer?->company_name ?? '—' }} &middot; {{ $truck->material?->name ?? '—' }}</div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-gray-400">Loader</span><br>{{ $instruction?->loaderOperator?->full_name ?? '—' }}</div>
                        <div><span class="text-gray-400">Target</span><br>{{ $instruction?->target_net_weight ?? '—' }} t</div>
                        <div><span class="text-gray-400">Started</span><br>{{ $instruction?->loading_started_at?->format('H:i') ?? '—' }}</div>
                        <div><span class="text-gray-400">Priority</span><br>{{ $instruction?->priority ?? '—' }}</div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-400">No truck currently assigned.</p>
                @endif
            </div>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">No loading bays configured yet.</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
