<?php

namespace App\Filament\Pages;

use App\Models\Truck;
use App\Traits\HasIndustryAccess;
use Filament\Pages\Page;

class LandingPage extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.landing-page';

    protected static ?string $title = 'Fleet Landing Page';

    public function getTrucks()
    {
        return Truck::all();
    }

    public function getTruckDetails($id)
    {
        return Truck::with(['driver', 'company'])->find($id);
    }
}
