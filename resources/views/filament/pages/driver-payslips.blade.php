<x-filament-panels::page>
    <div class="space-y-4">

        {{-- PAGE HEADER + MONTH PICKER --}}
        <div class="flex flex-col gap-3 rounded-xl p-4 text-white sm:flex-row sm:items-center sm:justify-between" style="background-color: #2f4f4f !important;">
            <div>
                <h1 class="text-lg font-bold">Driver Payslips</h1>
                <p class="text-xs text-white/80">Generate and manage monthly driver payslips</p>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-white/80">Period:</label>
                <input
                    type="month"
                    wire:model.live="payslipMonth"
                    value="{{ $this->payslipMonth }}"
                    class="rounded-lg border border-white/30 bg-white/10 px-3 py-1.5 text-sm text-white placeholder-white/50 focus:outline-none focus:ring-1 focus:ring-white/50"
                >
            </div>
        </div>

        {{-- PAYSLIP CARDS --}}
        @php
            $drivers = $this->getDrivers();
            $summaries = $this->getMonthSummaries();
            $currency = session('currency', 'KES');
            $periodLabel = \Carbon\Carbon::parse($this->payslipMonth)->format('M Y');
        @endphp

        @if ($drivers->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-12 text-center text-gray-400">
                No drivers found.
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($drivers as $driver)
                    @php
                        $summary = $summaries[$driver->id] ?? ['base' => 0, 'allowances' => 0, 'tax' => 0, 'net' => 0];
                        $base       = $summary['base'];
                        $allowances = $summary['allowances'];
                        $tax        = $summary['tax'];
                        $net        = $summary['net'];

                        $photo = $driver->profile_photo;
                        if ($photo && !\Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])) {
                            $photo = ltrim($photo, '/');
                            if (\Illuminate\Support\Str::startsWith($photo, 'storage/')) {
                                $photo = \Illuminate\Support\Str::after($photo, 'storage/');
                            }
                            $photo = \Illuminate\Support\Facades\Storage::disk('public')->url($photo);
                        }
                        $avatarUrl = $photo ?: 'https://ui-avatars.com/api/?name='.urlencode($driver->full_name).'&size=64&background=2f4f4f&color=fff';
                    @endphp

                    <div class="flex flex-col rounded-xl bg-white shadow-sm dark:bg-gray-800">

                        {{-- DRIVER HEADER --}}
                        <div class="flex items-center gap-2 border-b border-gray-100 p-3 dark:border-gray-700">
                            <img
                                src="{{ $avatarUrl }}"
                                alt="{{ $driver->full_name }}"
                                class="h-8 w-8 shrink-0 rounded-full object-cover"
                            >
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $driver->full_name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $periodLabel }}</p>
                            </div>
                        </div>

                        {{-- SALARY ROWS --}}
                        <div class="flex-1 space-y-1 px-3 py-2 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Base</span>
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ number_format($base, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-600">Allowances</span>
                                <span class="font-medium text-green-600">+{{ number_format($allowances, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-red-500">Tax</span>
                                <span class="font-medium text-red-500">-{{ number_format($tax, 2) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between border-t border-gray-100 pt-1 dark:border-gray-700">
                                <span class="font-bold text-gray-700 dark:text-gray-100">Net</span>
                                <span class="font-bold text-emerald-600">{{ number_format($net, 2) }}</span>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="grid grid-cols-2 gap-1.5 border-t border-gray-100 p-2 dark:border-gray-700">
                            <a
                                href="{{ route('payslips.generate', ['employee' => $driver->id, 'preview' => 1, 'month' => $this->payslipMonth]) }}"
                                target="_blank"
                                rel="noopener"
                                class="block rounded-md border border-[#2f4f4f] px-2 py-1.5 text-center text-[10px] font-semibold text-[#2f4f4f] hover:bg-gray-50 dark:text-emerald-400 dark:border-emerald-600 dark:hover:bg-gray-700"
                            >
                                Preview
                            </a>
                            <a
                                href="{{ route('payslips.generate', ['employee' => $driver->id, 'month' => $this->payslipMonth]) }}"
                                class="block rounded-md px-2 py-1.5 text-center text-[10px] font-semibold text-white hover:opacity-90"
                                style="background-color: #2f4f4f !important;"
                            >
                                Download
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
