<?php

namespace App\Providers;

use App\Filament\Widgets\DailyInsights;
use App\Filament\Widgets\PredictiveMaintenanceRisks;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\PartUsage;
use App\Models\Settlement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Company;
use App\Models\User;
use App\Observers\ExpenseObserver;
use App\Observers\TripObserver;
use App\Observers\MaintenanceObserver;
use App\Observers\FuelLogObserver;
use App\Observers\PartUsageObserver;
use App\Observers\SettlementObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Observers\CompanyObserver;
use App\Observers\UserObserver;
use App\Models\DailySiteDiary;
use App\Models\DiaryDelay;
use App\Models\Hazard;
use App\Models\SafetyIncident as SafetyIncidentModel;
use App\Observers\DailySiteDiaryObserver;
use App\Observers\DiaryDelayObserver;
use App\Observers\HazardObserver;
use App\Observers\SafetyIncidentObserver;
use App\Models\ProjectBudget;
use App\Models\ChangeOrder;
use App\Observers\BudgetObserver;
use App\Observers\ChangeOrderObserver;
use App\Models\EdmsDocument;
use App\Models\DocumentVersion;
use App\Models\DocumentShare;
use App\Observers\DocumentObserver;
use App\Observers\ProductionApprovalObserver;
use App\Observers\StockpileObserver;
use App\Models\ProductionRun;
use App\Models\ProductionLog;
use App\Models\Stockpile;
use App\Observers\DocumentVersionObserver;
use App\Observers\DocumentShareObserver;
use App\Models\TyreRepair;
use App\Observers\TyreRepairObserver;
use App\Models\DriverEmergencyAlert;
use App\Observers\DriverEmergencyAlertObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Illuminate\Support\Facades\Event;
use App\Events\WalletFunded;
use App\Events\FundingFailed;
use App\Listeners\SendWalletFundedNotification;
use App\Listeners\SendFundingFailedNotification;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Spatie\Permission\Events\RoleAttached;
use App\Models\Driver;
use App\Models\Employee;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Grant all admin roles full access to all Filament resources/pages.
        // Drivers are excluded — they don't use the admin panel.
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasAnyRole(['Super Admin', 'Company Admin', 'Fleet Manager', 'Accountant'])) {
                return true;
            }
            return null;
        });

        if (app()->environment('local') && isset($_SERVER['HTTP_HOST'])) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            config(['app.url' => "{$protocol}://{$_SERVER['HTTP_HOST']}"]);
        }

        // 12-char minimum, no forced complexity theater, checked against
        // known breaches via Laravel's built-in HaveIBeenPwned k-anonymity
        // integration (only a 5-char hash prefix ever leaves the server).
        \Illuminate\Validation\Rules\Password::defaults(function () {
            return \Illuminate\Validation\Rules\Password::min(12)->uncompromised();
        });

        User::observe(UserObserver::class);
        Company::observe(CompanyObserver::class);
        Expense::observe(ExpenseObserver::class);
        Trip::observe(TripObserver::class);
        Maintenance::observe(MaintenanceObserver::class);
        FuelLog::observe(FuelLogObserver::class);
        PartUsage::observe(PartUsageObserver::class);
        Settlement::observe(SettlementObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        DailySiteDiary::observe(DailySiteDiaryObserver::class);
        DiaryDelay::observe(DiaryDelayObserver::class);
        SafetyIncidentModel::observe(SafetyIncidentObserver::class);
        Hazard::observe(HazardObserver::class);
        ProjectBudget::observe(BudgetObserver::class);
        ChangeOrder::observe(ChangeOrderObserver::class);
        EdmsDocument::observe(DocumentObserver::class);
        DocumentVersion::observe(DocumentVersionObserver::class);
        DocumentShare::observe(DocumentShareObserver::class);
        ProductionRun::observe(ProductionApprovalObserver::class);
        ProductionLog::observe(ProductionApprovalObserver::class);
        Stockpile::observe(StockpileObserver::class);
        TyreRepair::observe(TyreRepairObserver::class);
        DriverEmergencyAlert::observe(DriverEmergencyAlertObserver::class);

        Livewire::component('currency-switcher', \App\Livewire\CurrencySwitcher::class);
        Livewire::component('notifications-dropdown', \App\Livewire\NotificationsDropdown::class);
        Livewire::component('profile-menu', \App\Livewire\ProfileMenu::class);
        Livewire::component('ai-panel', \App\Livewire\AiPanel::class);
        Livewire::component('app.filament.widgets.daily-insights', DailyInsights::class);
        Livewire::component('app.filament.widgets.predictive-maintenance-risks', PredictiveMaintenanceRisks::class);

        Event::listen(WalletFunded::class, SendWalletFundedNotification::class);
        Event::listen(FundingFailed::class, SendFundingFailedNotification::class);
        Event::listen(SocialiteWasCalled::class, [MicrosoftExtendSocialite::class, 'handle']);

        // Spatie ships with role/permission pivot events disabled by default
        // (config/permission.php isn't published in this app, so the
        // package's own default of events_enabled=false applies) — turn
        // them on so RoleAttached actually fires below.
        config(['permission.events_enabled' => true]);

        // Spatie's role pivot attach does NOT fire the User model's own
        // Eloquent events, so Driver auto-provisioning can't live in
        // UserObserver::created() — it has to react to the real attach event.
        Event::listen(RoleAttached::class, function (RoleAttached $event): void {
            $model = $event->model;

            if (! $model instanceof User || ! $model->company_id) {
                return;
            }

            if (! $model->hasRole('Driver')) {
                return;
            }

            $employeeId = Employee::where('company_id', $model->company_id)
                ->whereRaw('LOWER(email) = ?', [strtolower($model->email)])
                ->value('id');

            Driver::firstOrCreate(
                ['user_id' => $model->id],
                [
                    'company_id' => $model->company_id,
                    'employee_id' => $employeeId,
                    'license_number' => 'PENDING',
                    'risk_score' => 0,
                    'performance_score' => 0,
                    'status' => 'active',
                ]
            );
        });
    }
}
