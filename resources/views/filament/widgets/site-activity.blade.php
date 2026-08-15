<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Recent Site Activity</x-slot>

        <div class="space-y-4">
            @forelse ($this->getActivity() as $event)
                <div class="flex items-start gap-3">
                    <div @class([
                        'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full',
                        'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' => $event['color'] === 'success',
                        'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400' => $event['color'] === 'warning',
                        'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' => $event['color'] === 'danger',
                        'bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400' => $event['color'] === 'info',
                        'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400' => $event['color'] === 'gray',
                    ])>
                        <x-filament::icon :icon="$event['icon']" class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $event['text'] }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $event['time']?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-gray-500 dark:text-gray-400">No recent activity.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
