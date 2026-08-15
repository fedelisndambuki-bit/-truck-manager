<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Traits\HasModuleAccess;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Live supplier scorecards — on-time delivery is computed straight from
 * PurchaseOrder.delivery_confirmed_at vs expected_delivery, quality/overall
 * from SupplierPerformanceReview history, exactly like EAM computed
 * MTBF/MTTR live rather than maintaining a redundant rollup table.
 */
class SupplierPerformance extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Supplier Performance';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Supplier Performance';

    protected static string $view = 'filament.clusters.procurement.pages.supplier-performance';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Supplier Performance',
            'subtitle' => 'On-time delivery, quality and overall supplier scorecards.',
        ]);
    }

    protected function onTimeDeliveryPercent(Supplier $supplier): ?float
    {
        $orders = PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('expected_delivery')
            ->whereNotNull('delivery_confirmed_at')
            ->get(['expected_delivery', 'delivery_confirmed_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        $onTime = $orders->filter(fn (PurchaseOrder $o) => $o->delivery_confirmed_at <= $o->expected_delivery->endOfDay())->count();

        return round(($onTime / $orders->count()) * 100, 1);
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(Supplier::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Supplier')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('on_time_delivery')->label('On-Time Delivery')
                    ->state(fn (Supplier $record) => $this->onTimeDeliveryPercent($record))
                    ->formatStateUsing(fn (?float $state) => $state === null ? '—' : "{$state}%"),
                Tables\Columns\TextColumn::make('quality_rating')->label('Quality')->suffix(' / 5')->placeholder('—'),
                Tables\Columns\TextColumn::make('performance_rating')->label('Overall Rating')->suffix(' / 5')->placeholder('—'),
                Tables\Columns\TextColumn::make('latest_review')->label('Last Review Score')
                    ->state(fn (Supplier $record) => $record->performanceReviews()->latest('review_period_end')->value('overall_score'))
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : "{$state} / 100"),
                Tables\Columns\TextColumn::make('risk_rating')->badge()->color(fn (string $state) => match ($state) {
                    'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_rating')->options(array_combine(Supplier::RISK_RATINGS, Supplier::RISK_RATINGS)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('open')
                    ->label('Open Profile')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->infolist(fn (Infolist $infolist) => \App\Filament\Clusters\Procurement\Resources\SupplierResource::infolist($infolist)),
            ])
            ->striped();
    }
}
