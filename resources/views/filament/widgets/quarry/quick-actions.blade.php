<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Quick Actions</x-slot>

        <div class="flex flex-nowrap gap-3 overflow-x-auto pb-1">
            @foreach ($this->getActions() as $action)
                @if (!empty($action['comingSoon']))
                    <div class="flex flex-shrink-0 items-center gap-2.5 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3.5 py-2.5 opacity-60 dark:border-white/10 dark:bg-white/5">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                            <x-filament::icon :icon="$action['icon']" class="h-4 w-4" />
                        </div>
                        <div>
                            <span class="block whitespace-nowrap text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $action['label'] }}</span>
                            <span class="block text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Coming soon</span>
                        </div>
                    </div>
                @else
                    <a
                        href="{{ $action['url'] }}"
                        class="flex flex-shrink-0 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 shadow-sm transition hover:border-primary-400 hover:shadow dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-400"
                    >
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <x-filament::icon :icon="$action['icon']" class="h-4 w-4" />
                        </div>
                        <span class="whitespace-nowrap text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $action['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
