<x-filament::section>
    <x-slot name="heading">Daily Insights</x-slot>
    <div class="space-y-3">
        @if(empty($content))
            {{-- Nothing cached yet: fire the real generation as its own
                 request AFTER this page has already rendered, instead of
                 blocking the dashboard's initial load on a live AI call. --}}
            <div wire:init="refreshInsights"></div>
        @endif
        <div class="text-sm text-gray-500" wire:loading wire:target="refreshInsights">
            Generating insights...
        </div>
        <div class="prose max-w-none dark:prose-invert">
            @if(!empty($content))
                <pre class="whitespace-pre-wrap text-sm leading-relaxed">{{ $content }}</pre>
            @else
                <div class="text-sm text-gray-500" wire:loading.remove wire:target="refreshInsights">No insights available yet.</div>
            @endif
        </div>
        @if(!empty($lastRefreshedAt))
            <div class="text-xs text-gray-400">Last updated: {{ $lastRefreshedAt }}</div>
        @endif
        <div class="flex justify-end">
            <x-filament::button wire:click="refreshInsights" wire:loading.attr="disabled" wire:target="refreshInsights" size="sm" color="gray" outlined>
                Refresh
            </x-filament::button>
        </div>
    </div>
</x-filament::section>
