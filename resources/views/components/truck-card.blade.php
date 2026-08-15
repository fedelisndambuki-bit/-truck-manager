<div class="group relative bg-white dark:bg-gray-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-2xl hover:border-indigo-500/30">
                    
    {{-- Image Header with Zoom --}}
    <div class="relative h-52 overflow-hidden bg-slate-100">
        <img 
            src="{{ $truck->truck_image ? asset('storage/' . $truck->truck_image) : 'https://placehold.co/600x400?text=Truck' }}" 
            alt="{{ $truck->brand }} {{ $truck->model }}"
            class="w-full h-full object-cover transition-transform duration-500 ease-in-out group-hover:scale-110"
        />
        
        {{-- Status Badge --}}
        <div class="absolute top-4 right-4">
            @php
                $statusClass = match($truck->status) {
                    'Active' => 'bg-emerald-100 text-emerald-700',
                    'Maintenance' => 'bg-amber-100 text-amber-700',
                    'Idle' => 'bg-slate-100 text-slate-700',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp
            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm backdrop-blur-md {{ $statusClass }}">
                {{ $truck->status ?? 'Active' }}
            </span>
        </div>

        {{-- Plate Number --}}
        <div class="absolute bottom-4 left-4">
            <span class="bg-slate-900/80 backdrop-blur-md text-white px-3 py-1.5 rounded-xl font-mono font-bold tracking-widest border border-white/20">
                {{ $truck->plate_number }}
            </span>
        </div>
    </div>

    {{-- Body --}}
    <div class="p-8">
        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-1">{{ $truck->brand }}</p>
        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 leading-tight">{{ $truck->model }}</h3>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-3 gap-3">
            {{-- Mileage --}}
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-700 text-center transition-colors group-hover:bg-indigo-50/50">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Mileage</p>
                <div class="flex flex-col items-center gap-1 text-slate-900 dark:text-slate-200">
                    <i class="fas fa-road text-slate-400 text-xs"></i>
                    <span class="font-bold text-xs truncate w-full">
                        {{ number_format($truck->current_mileage) }} km
                    </span>
                </div>
            </div>

            {{-- Fuel --}}
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-700 text-center transition-colors group-hover:bg-indigo-50/50">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Fuel</p>
                <div class="flex flex-col items-center gap-1 text-slate-900 dark:text-slate-200">
                    <i class="fas fa-gas-pump text-slate-400 text-xs"></i>
                    <span class="font-bold text-xs">{{ $truck->fuel_consumption ?? '0' }} <span class="text-[9px] text-slate-400">L</span></span>
                </div>
            </div>

            {{-- Capacity --}}
            <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-100 dark:border-slate-700 text-center transition-colors group-hover:bg-indigo-50/50">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Capacity</p>
                <div class="flex flex-col items-center gap-1 text-slate-900 dark:text-slate-200">
                    <i class="fas fa-weight-hanging text-slate-400 text-xs"></i>
                    <span class="font-bold text-xs">{{ $truck->capacity_tons }}<span class="text-[9px] text-slate-400">t</span></span>
                </div>
            </div>
        </div>

        {{-- Card Footer --}}
        <div class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-800 flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[9px] text-slate-400 uppercase font-black tracking-tighter">Last Service</span>
                <span class="text-xs text-slate-600 dark:text-slate-400 font-bold">{{ $truck->last_service_date?->format('d M Y') ?? 'N/A' }}</span>
            </div>
            <a href="{{ url('admin/trucks/' . $truck->id . '/edit') }}" 
               class="inline-flex items-center text-xs font-black text-indigo-600 transition-all group-hover:gap-2">
                Details 
                <i class="fas fa-chevron-right text-[10px] transition-transform group-hover/btn:translate-x-1"></i>
            </a>
        </div>
    </div>
</div>