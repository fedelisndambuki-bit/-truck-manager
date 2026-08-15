@include('filament.construction.partials.styles')
@include('filament.construction.partials.header', [
    'icon' => $icon,
    'title' => $title,
    'subtitle' => $subtitle,
])

<x-filament::section :compact="true">
    <div class="relative">
        <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
            type="text"
            wire:model.live.debounce.400ms="search"
            placeholder="Search projects, sites, staff, equipment, suppliers, subcontractors, materials, documents…"
            class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm pl-9"
        />
    </div>

    @if (!empty($results))
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($results as $group => $items)
                <div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ $group }}</div>
                    <ul class="space-y-1">
                        @foreach ($items as $item)
                            <li class="text-sm text-gray-900 dark:text-white flex items-center justify-between gap-2">
                                <span class="truncate">{{ $item['label'] }}</span>
                                @if (!empty($item['meta']))
                                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $item['meta'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @elseif (trim($search) !== '')
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No matches found.</p>
    @endif
</x-filament::section>
