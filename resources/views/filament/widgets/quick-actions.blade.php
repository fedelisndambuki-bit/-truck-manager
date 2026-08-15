<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Quick Actions</x-slot>

        <div class="flex flex-nowrap gap-3 overflow-x-auto pb-1">
            @foreach ($this->getActions() as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="flex flex-shrink-0 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 shadow-sm transition hover:border-primary-400 hover:shadow dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-400"
                >
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$action['icon']" class="h-4 w-4" />
                    </div>
                    <span class="whitespace-nowrap text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
