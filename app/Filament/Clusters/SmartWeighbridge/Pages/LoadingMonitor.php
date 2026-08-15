<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Models\LoadingBay;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * The "Live Loading Board" — a big-screen, auto-refreshing view of every
 * loading bay's current truck/status, color-coded, for the quarry yard.
 */
class LoadingMonitor extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-tv';

    protected static ?string $navigationLabel = 'Loading Monitor';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Loading Monitor';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.loading-monitor';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />',
            'title' => 'Live Loading Board',
            'subtitle' => 'Real-time status of every loading bay across the quarry yard.',
        ]);
    }

    public function getBays()
    {
        $companyId = Auth::user()?->company_id;

        return LoadingBay::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['stockpile', 'loaderEquipment'])
            ->get()
            ->map(function (LoadingBay $bay) {
                $truck = $bay->currentTruck();
                $instruction = $bay->loadingInstructions()
                    ->whereIn('status', ['Accepted', 'Loading', 'Paused'])
                    ->latest('id')
                    ->first();

                return [
                    'bay' => $bay,
                    'truck' => $truck,
                    'instruction' => $instruction,
                ];
            });
    }
}
