<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\ProcurementSetting;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Settings extends \Filament\Pages\Page implements HasForms
{
    use HasModuleAccess;
    use InteractsWithForms;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.manage';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Procurement Settings';

    protected static string $view = 'filament.clusters.procurement.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'rfq_closing_soon_days' => ProcurementSetting::getSetting($companyId, 'rfq_closing_soon_days', 2),
            'contract_expiring_days' => ProcurementSetting::getSetting($companyId, 'contract_expiring_days', 30),
            'budget_alert_threshold_percent' => ProcurementSetting::getSetting($companyId, 'budget_alert_threshold_percent', 90),
            'invoice_match_tolerance_percent' => ProcurementSetting::getSetting($companyId, 'invoice_match_tolerance_percent', 1),
            'require_three_quotes_above' => ProcurementSetting::getSetting($companyId, 'require_three_quotes_above', 100000),
            'notify_email' => ProcurementSetting::getSetting($companyId, 'notify_email', true),
            'notify_database' => ProcurementSetting::getSetting($companyId, 'notify_database', true),
        ]);
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />',
            'title' => 'Settings',
            'subtitle' => 'Alert thresholds and notification channels for Procurement & Supply Chain.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Alert Thresholds')->schema([
                    Forms\Components\TextInput::make('rfq_closing_soon_days')
                        ->label('RFQ Closing Soon Reminder (days)')->numeric()->minValue(1)->required(),
                    Forms\Components\TextInput::make('contract_expiring_days')
                        ->label('Contract Expiring Reminder (days)')->numeric()->minValue(1)->required(),
                    Forms\Components\TextInput::make('budget_alert_threshold_percent')
                        ->label('Budget Utilization Alert Threshold (%)')->numeric()->minValue(1)->maxValue(100)->required(),
                    Forms\Components\TextInput::make('invoice_match_tolerance_percent')
                        ->label('Three-Way Match Tolerance (%)')->numeric()->minValue(0)->maxValue(20)->required(),
                    Forms\Components\TextInput::make('require_three_quotes_above')
                        ->label('Require 3+ Quotations Above (amount)')->numeric()->minValue(0)->required(),
                ])->columns(3),
                Forms\Components\Section::make('Notification Channels')
                    ->description('Database and Email are fully wired.')
                    ->schema([
                        Forms\Components\Toggle::make('notify_database')->label('In-App (Database)'),
                        Forms\Components\Toggle::make('notify_email')->label('Email'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        ProcurementSetting::setSetting($companyId, 'rfq_closing_soon_days', $state['rfq_closing_soon_days'], 'integer');
        ProcurementSetting::setSetting($companyId, 'contract_expiring_days', $state['contract_expiring_days'], 'integer');
        ProcurementSetting::setSetting($companyId, 'budget_alert_threshold_percent', $state['budget_alert_threshold_percent'], 'integer');
        ProcurementSetting::setSetting($companyId, 'invoice_match_tolerance_percent', $state['invoice_match_tolerance_percent'], 'integer');
        ProcurementSetting::setSetting($companyId, 'require_three_quotes_above', $state['require_three_quotes_above'], 'integer');
        ProcurementSetting::setSetting($companyId, 'notify_email', $state['notify_email'], 'boolean');
        ProcurementSetting::setSetting($companyId, 'notify_database', $state['notify_database'], 'boolean');

        Notification::make()->title('Procurement settings saved')->success()->send();
    }
}
