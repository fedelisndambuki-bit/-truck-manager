<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\ProjectFinanceSetting;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Settings extends \Filament\Pages\Page implements HasForms
{
    use HasModuleAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.manage';
    }

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 19;

    protected static ?string $title = 'Project Finance Settings';

    protected static string $view = 'filament.clusters.project-finance.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_retention_percent' => ProjectFinanceSetting::getSetting($companyId, 'default_retention_percent', 10),
            'default_vat_percent' => ProjectFinanceSetting::getSetting($companyId, 'default_vat_percent', 16),
            'budget_alert_threshold_percent' => ProjectFinanceSetting::getSetting($companyId, 'budget_alert_threshold_percent', 90),
            'cost_variance_alert_threshold_percent' => ProjectFinanceSetting::getSetting($companyId, 'cost_variance_alert_threshold_percent', 10),
            'invoice_overdue_alert_days' => ProjectFinanceSetting::getSetting($companyId, 'invoice_overdue_alert_days', 0),
            'retention_due_alert_days' => ProjectFinanceSetting::getSetting($companyId, 'retention_due_alert_days', 30),
            'notify_budget_exceeded' => ProjectFinanceSetting::getSetting($companyId, 'notify_budget_exceeded', true),
            'notify_invoice_overdue' => ProjectFinanceSetting::getSetting($companyId, 'notify_invoice_overdue', true),
            'notify_retention_due' => ProjectFinanceSetting::getSetting($companyId, 'notify_retention_due', true),
            'notify_payment_received' => ProjectFinanceSetting::getSetting($companyId, 'notify_payment_received', true),
            'notify_cost_variance_high' => ProjectFinanceSetting::getSetting($companyId, 'notify_cost_variance_high', true),
            'notify_cash_flow_risk' => ProjectFinanceSetting::getSetting($companyId, 'notify_cash_flow_risk', true),
            'notify_variation_submitted' => ProjectFinanceSetting::getSetting($companyId, 'notify_variation_submitted', true),
            'notify_forecast_changed' => ProjectFinanceSetting::getSetting($companyId, 'notify_forecast_changed', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Defaults')->schema([
                    Forms\Components\TextInput::make('default_retention_percent')
                        ->label('Default Retention (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                    Forms\Components\TextInput::make('default_vat_percent')
                        ->label('Default VAT (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                ])->columns(2),
                Forms\Components\Section::make('Alert Thresholds')->schema([
                    Forms\Components\TextInput::make('budget_alert_threshold_percent')
                        ->label('Budget Utilization Alert Threshold (%)')->numeric()->minValue(1)->maxValue(200),
                    Forms\Components\TextInput::make('cost_variance_alert_threshold_percent')
                        ->label('Cost Variance Alert Threshold (%)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('invoice_overdue_alert_days')
                        ->label('Invoice Overdue Grace (days)')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('retention_due_alert_days')
                        ->label('Retention Due Lead Time (days)')->numeric()->minValue(1),
                ])->columns(2),
                Forms\Components\Section::make('Notifications')->schema([
                    Forms\Components\Toggle::make('notify_budget_exceeded')->label('Budget Exceeded'),
                    Forms\Components\Toggle::make('notify_invoice_overdue')->label('Invoice Overdue'),
                    Forms\Components\Toggle::make('notify_retention_due')->label('Retention Due'),
                    Forms\Components\Toggle::make('notify_payment_received')->label('Payment Received'),
                    Forms\Components\Toggle::make('notify_cost_variance_high')->label('Cost Variance High'),
                    Forms\Components\Toggle::make('notify_cash_flow_risk')->label('Cash Flow Risk'),
                    Forms\Components\Toggle::make('notify_variation_submitted')->label('Variation Submitted'),
                    Forms\Components\Toggle::make('notify_forecast_changed')->label('Forecast Changes'),
                ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        ProjectFinanceSetting::setSetting($companyId, 'default_retention_percent', $state['default_retention_percent'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'default_vat_percent', $state['default_vat_percent'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'budget_alert_threshold_percent', $state['budget_alert_threshold_percent'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'cost_variance_alert_threshold_percent', $state['cost_variance_alert_threshold_percent'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'invoice_overdue_alert_days', $state['invoice_overdue_alert_days'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'retention_due_alert_days', $state['retention_due_alert_days'], 'integer');
        ProjectFinanceSetting::setSetting($companyId, 'notify_budget_exceeded', $state['notify_budget_exceeded'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_invoice_overdue', $state['notify_invoice_overdue'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_retention_due', $state['notify_retention_due'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_payment_received', $state['notify_payment_received'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_cost_variance_high', $state['notify_cost_variance_high'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_cash_flow_risk', $state['notify_cash_flow_risk'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_variation_submitted', $state['notify_variation_submitted'], 'boolean');
        ProjectFinanceSetting::setSetting($companyId, 'notify_forecast_changed', $state['notify_forecast_changed'], 'boolean');

        Notification::make()->title('Project Finance settings saved')->success()->send();
    }
}
