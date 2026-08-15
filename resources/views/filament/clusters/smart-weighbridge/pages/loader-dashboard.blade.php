@php
    $current = $this->getCurrentJobs();
    $waiting = $this->getWaitingJobs();
    $completed = $this->getCompletedJobs();
@endphp
<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Current Job(s)</h2>
            <div class="space-y-3">
                @forelse ($current as $job)
                    <x-filament::section>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $job->dispatchOrder?->vehicle_registration ?? '—' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $job->loading_number }} &middot; {{ $job->dispatchOrder?->customer?->company_name ?? '—' }}</div>
                            </div>
                            <span @class([
                                'fi-badge rounded-md px-2 py-1 text-xs font-semibold',
                                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => $job->status === 'Accepted',
                                'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400' => $job->status === 'Loading',
                                'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400' => $job->status === 'Paused',
                            ])>{{ $job->status }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                            <div><span class="text-gray-400">Product</span><br>{{ $job->dispatchOrder?->material?->name ?? '—' }}</div>
                            <div><span class="text-gray-400">Stockpile</span><br>{{ $job->stockpile?->label ?? '—' }}</div>
                            <div><span class="text-gray-400">Target</span><br>{{ $job->target_net_weight ?? '—' }} t</div>
                            <div><span class="text-gray-400">Bay</span><br>{{ $job->loadingBay?->name ?? '—' }}</div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($job->status === 'Accepted')
                                <button wire:click="startJob({{ $job->id }})" class="fi-btn rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500">Start Loading</button>
                            @endif
                            @if ($job->status === 'Loading')
                                <button wire:click="pauseJob({{ $job->id }})" class="fi-btn rounded-lg bg-gray-200 px-3 py-2 text-xs font-semibold text-gray-800 hover:bg-gray-300 dark:bg-white/10 dark:text-white">Pause</button>
                                <button wire:click="completeJob({{ $job->id }})" class="fi-btn rounded-lg bg-success-600 px-3 py-2 text-xs font-semibold text-white hover:bg-success-500">Complete Loading</button>
                            @endif
                            @if ($job->status === 'Paused')
                                <button wire:click="resumeJob({{ $job->id }})" class="fi-btn rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500">Resume</button>
                            @endif
                            <button wire:click="rejectJob({{ $job->id }})" class="fi-btn rounded-lg bg-danger-50 px-3 py-2 text-xs font-semibold text-danger-700 hover:bg-danger-100 dark:bg-danger-500/10 dark:text-danger-400">Request Supervisor</button>
                        </div>
                    </x-filament::section>
                @empty
                    <x-filament::section>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No active job right now.</p>
                    </x-filament::section>
                @endforelse
            </div>

            <h2 class="mb-3 mt-6 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waiting Jobs</h2>
            <div class="space-y-2">
                @forelse ($waiting as $job)
                    <x-filament::section>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $job->dispatchOrder?->vehicle_registration ?? '—' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $job->loading_number }} &middot; {{ $job->priority }} priority</div>
                            </div>
                            <button wire:click="acceptJob({{ $job->id }})" class="fi-btn rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500">Accept Job</button>
                        </div>
                    </x-filament::section>
                @empty
                    <x-filament::section>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No waiting jobs.</p>
                    </x-filament::section>
                @endforelse
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed Jobs</h2>
            <div class="space-y-2">
                @forelse ($completed as $job)
                    <x-filament::section>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $job->dispatchOrder?->vehicle_registration ?? '—' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $job->loading_number }} &middot; {{ $job->loading_completed_at?->format('d M Y H:i') }}</div>
                            </div>
                            <span class="text-xs font-semibold text-success-600">{{ $job->estimated_quantity_loaded ?? '—' }} t</span>
                        </div>
                    </x-filament::section>
                @empty
                    <x-filament::section>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No completed jobs yet.</p>
                    </x-filament::section>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
