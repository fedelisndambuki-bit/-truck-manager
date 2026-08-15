<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Services\ReplenishmentRecommendationService;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InventoryReplenishment extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Inventory Replenishment';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Inventory Replenishment';

    protected static string $view = 'filament.clusters.procurement.pages.inventory-replenishment';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />',
            'title' => 'Inventory Replenishment',
            'subtitle' => 'Materials below reorder level, with a recommended purchase quantity based on max stock and reorder point.',
        ]);
    }

    public function getRecommendations(): Collection
    {
        return app(ReplenishmentRecommendationService::class)->recommendations(Auth::user()?->company_id);
    }

    public function createRequisition(int $materialId): void
    {
        $recommendation = $this->getRecommendations()->firstWhere(fn ($row) => $row['material']->id === $materialId);

        if (! $recommendation) {
            return;
        }

        $pr = app(ReplenishmentRecommendationService::class)->createRequisitionFrom($recommendation, Auth::user()?->company_id);

        Notification::make()->title("Requisition {$pr->pr_number} created")->success()->send();
    }
}
