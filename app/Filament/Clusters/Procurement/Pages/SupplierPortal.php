<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\PurchaseOrder;
use App\Models\RequestForQuotation;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * External supplier access, built as role-scoped views inside the existing
 * admin panel rather than a separate branded portal — a Supplier-role user
 * (Auth::user()->supplier_id set) sees only their own invited RFQs,
 * quotations, purchase orders and performance here.
 */
class SupplierPortal extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Supplier Portal';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Supplier Portal';

    protected static string $view = 'filament.clusters.procurement.pages.supplier-portal';

    protected function supplier(): ?Supplier
    {
        return Auth::user()?->supplier;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.615m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" />',
            'title' => 'Supplier Portal',
            'subtitle' => $this->supplier()
                ? "Viewing as {$this->supplier()->company_name}"
                : 'Internal staff view — sign in as a Supplier Portal user to see a supplier-scoped view.',
        ]);
    }

    public function getInvitedRfqs(): Collection
    {
        $supplier = $this->supplier();

        if (! $supplier) {
            return collect();
        }

        return $supplier->rfqInvitations()->with('requestForQuotation')->get()->pluck('requestForQuotation')->filter();
    }

    public function getMyQuotations(): Collection
    {
        $supplier = $this->supplier();

        return $supplier ? $supplier->quotations()->with('requestForQuotation')->latest()->get() : collect();
    }

    public function getMyPurchaseOrders(): Collection
    {
        $supplier = $this->supplier();

        return $supplier ? $supplier->purchaseOrders()->latest()->get() : collect();
    }

    public function confirmDelivery(int $purchaseOrderId): void
    {
        $supplier = $this->supplier();
        $order = PurchaseOrder::where('id', $purchaseOrderId)->where('supplier_id', $supplier?->id)->first();

        if (! $order) {
            return;
        }

        $order->update(['delivery_confirmed_at' => now()]);

        Notification::make()->title("Delivery confirmed for {$order->po_number}")->success()->send();
    }
}
