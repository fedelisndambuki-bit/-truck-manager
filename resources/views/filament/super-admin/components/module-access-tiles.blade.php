@php
    $record = $getRecord();
    if (!$record) {
        return;
    }
    $allModules = \App\Models\Module::all()->groupBy('category');
    $planModules = $record->plan?->modules()->pluck('id')->toArray() ?? [];
    $customOverrides = $record->customModules()->pluck('module_id')->toArray();
    $effectiveModuleIds = $record->getEffectiveModules()->map(fn($slug) => \App\Models\Module::where('slug', $slug)->first()?->id)->filter()->toArray();
@endphp

<div class="space-y-8" x-data="{ 
    overrides: @js($customOverrides),
    planDefaults: @js($planModules),
    toggleModule(id) {
        if (this.overrides.includes(id)) {
            this.overrides = this.overrides.filter(i => i !== id);
        } else {
            this.overrides.push(id);
        }
        $wire.set('data.customModules', this.overrides);
    }
}">
    <div class="flex items-center justify-between bg-blue-50 p-3 rounded-lg border border-blue-100 mb-6">
        <div class="flex items-center gap-2">
            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
            <span class="text-xs font-medium text-blue-800">Highlighted tiles indicate active modules for this client. Plan defaults are combined with manual overrides.</span>
        </div>
        <button type="button" @click="overrides = []; $wire.set('data.customModules', [])" class="text-[10px] font-bold text-blue-600 hover:underline uppercase tracking-wider">
            Reset Overrides
        </button>
    </div>

    @foreach($allModules as $category => $modules)
        <div class="mod-section">
            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">{{ $category }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($modules as $module)
                    @php
                        $isPlanDefault = in_array($module->id, $planModules);
                        $isOverride = in_array($module->id, $customOverrides);
                        $isActive = in_array($module->id, $effectiveModuleIds);
                    @endphp
                    <div 
                        @click="toggleModule({{ $module->id }})"
                        :class="{
                            'border-[#2f4f4f] bg-[#f0f8f8] ring-1 ring-[#2f4f4f]': overrides.includes({{ $module->id }}),
                            'border-gray-200 bg-white opacity-60': !overrides.includes({{ $module->id }}) && !planDefaults.includes({{ $module->id }}),
                            'border-green-200 bg-green-50': planDefaults.includes({{ $module->id }}) && !overrides.includes({{ $module->id }})
                        }"
                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all hover:scale-[1.02] shadow-sm relative group"
                    >
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-white shadow-inner">
                            @if($module->icon)
                                @svg($module->icon, 'w-4 h-4 text-gray-600 group-hover:text-[#2f4f4f]')
                            @else
                                <x-heroicon-o-cpu-chip class="w-4 h-4 text-gray-400" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-gray-900 truncate">{{ $module->name }}</p>
                            <p class="text-[9px] text-gray-500 uppercase font-bold tracking-tighter">
                                @if($isPlanDefault) 
                                    <span class="text-green-600">Plan Default</span>
                                @else
                                    <span class="text-gray-400">Add-on</span>
                                @endif
                            </p>
                        </div>
                        
                        <!-- Green Circled Tick for Active Modules -->
                        <div x-show="overrides.includes({{ $module->id }}) || planDefaults.includes({{ $module->id }})" class="absolute -top-2 -right-1">
                            <div class="bg-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Override Indicator (Optional small dot or exclamation) -->
                        <div x-show="overrides.includes({{ $module->id }})" class="absolute -bottom-1 -right-1">
                            <span class="flex h-2.5 w-2.5 items-center justify-center rounded-full bg-[#2f4f4f] text-[6px] text-white font-bold shadow-sm">!</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
