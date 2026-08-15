@php
    $insights = $this->getInsights();
@endphp
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <x-slot name="heading">AI Insights</x-slot>
            <button wire:click="generateInsights" wire:loading.attr="disabled"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg h-8 pl-2.5 pr-3 text-xs inline-grid gap-1 shadow-sm bg-primary-600 text-white hover:bg-primary-500">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-3.5 w-3.5" />
                <span wire:loading.remove>Generate Insights</span>
                <span wire:loading>Analyzing…</span>
            </button>
        </div>

        @if ($insights->isEmpty())
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No insights generated yet. Click "Generate Insights" to run the analysis.</p>
        @else
            <div class="mt-3 space-y-3">
                @foreach ($insights as $insight)
                    <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-white/10 p-3">
                        <x-filament::icon
                            :icon="\App\Models\AiInsight::getIcon($insight->type)"
                            class="h-5 w-5 flex-shrink-0 mt-0.5"
                            :class="match(\App\Models\AiInsight::getColor($insight->severity)) {
                                'danger' => 'text-danger-600 dark:text-danger-400',
                                'warning' => 'text-warning-600 dark:text-warning-400',
                                default => 'text-info-600 dark:text-info-400',
                            }"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $insight->title }}</span>
                                <x-filament::badge :color="\App\Models\AiInsight::getColor($insight->severity)" size="sm">
                                    {{ $insight->severity }}
                                </x-filament::badge>
                            </div>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $insight->narrative }}</p>
                            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $insight->generated_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
