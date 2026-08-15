<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Project Progress</x-slot>

        @php $currency = session('currency', 'KES'); @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->getProjects() as $project)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-gray-950 dark:text-white">{{ $project->name }}</div>
                            <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $project->client?->company_name ?? 'No client' }}</div>
                        </div>
                        <x-filament::badge :color="match($project->status) {
                            'Planning' => 'gray',
                            'Active' => 'success',
                            'Delayed' => 'danger',
                            'On Hold' => 'warning',
                            'Completed' => 'info',
                            default => 'gray',
                        }">
                            {{ $project->status }}
                        </x-filament::badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                        <dt class="text-gray-500 dark:text-gray-400">Project Manager</dt>
                        <dd class="text-right text-gray-700 dark:text-gray-300">{{ $project->projectManager?->full_name ?? '—' }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Location</dt>
                        <dd class="text-right text-gray-700 dark:text-gray-300">{{ $project->site_address ?? '—' }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Contract Value</dt>
                        <dd class="text-right text-gray-700 dark:text-gray-300">{{ $currency }} {{ number_format($project->contract_value ?? 0, 0) }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Completion</dt>
                        <dd class="text-right text-gray-700 dark:text-gray-300">{{ $project->end_date?->format('d M Y') ?? '—' }}</dd>
                    </dl>

                    @php $progress = $project->progress_percent ?? 0; @endphp
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Progress</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div
                                class="h-full rounded-full {{ $progress < 70 ? 'bg-success-500' : ($progress < 90 ? 'bg-warning-500' : 'bg-danger-500') }}"
                                style="width: {{ $progress }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-6 text-center text-gray-500 dark:text-gray-400">No active projects yet.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
