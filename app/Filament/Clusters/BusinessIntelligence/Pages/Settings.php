<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\BiSetting;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
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
        return 'bi.manage';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Business Intelligence Settings';

    protected static string $view = 'filament.clusters.business-intelligence.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'budget_overrun_threshold' => BiSetting::getSetting($companyId, 'budget_overrun_threshold', 0.9),
            'schedule_slippage_threshold' => BiSetting::getSetting($companyId, 'schedule_slippage_threshold', 0.9),
            'safety_score_alert_threshold' => BiSetting::getSetting($companyId, 'safety_score_alert_threshold', 70),
            'quality_score_alert_threshold' => BiSetting::getSetting($companyId, 'quality_score_alert_threshold', 80),
            'default_export_format' => BiSetting::getSetting($companyId, 'default_export_format', 'pdf'),
            'default_report_frequency' => BiSetting::getSetting($companyId, 'default_report_frequency', 'Weekly'),
            'ai_insights_enabled' => BiSetting::getSetting($companyId, 'ai_insights_enabled', true),
            'notify_kpi_threshold_exceeded' => BiSetting::getSetting($companyId, 'notify_kpi_threshold_exceeded', true),
            'notify_budget_risk' => BiSetting::getSetting($companyId, 'notify_budget_risk', true),
            'notify_safety_risk' => BiSetting::getSetting($companyId, 'notify_safety_risk', true),
            'notify_project_delay' => BiSetting::getSetting($companyId, 'notify_project_delay', true),
            'notify_scheduled_report_ready' => BiSetting::getSetting($companyId, 'notify_scheduled_report_ready', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('KPI Thresholds')
                    ->description('Used by AI Insights and risk notifications.')
                    ->schema([
                        Forms\Components\TextInput::make('budget_overrun_threshold')
                            ->label('Cost Performance Index Alert Threshold')->numeric()->step(0.01)->minValue(0)->maxValue(2)->required(),
                        Forms\Components\TextInput::make('schedule_slippage_threshold')
                            ->label('Schedule Performance Index Alert Threshold')->numeric()->step(0.01)->minValue(0)->maxValue(2)->required(),
                        Forms\Components\TextInput::make('safety_score_alert_threshold')
                            ->label('Safety Score Alert Threshold')->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\TextInput::make('quality_score_alert_threshold')
                            ->label('Quality Score Alert Threshold (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Report Defaults')->schema([
                    Forms\Components\Select::make('default_export_format')->options([
                        'pdf' => 'PDF', 'csv' => 'CSV', 'excel' => 'Excel', 'word' => 'Word', 'json' => 'JSON',
                    ])->required(),
                    Forms\Components\Select::make('default_report_frequency')->options([
                        'Daily' => 'Daily', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Yearly' => 'Yearly',
                    ])->required(),
                    Forms\Components\Toggle::make('ai_insights_enabled')->label('Enable AI Insights Generation'),
                ])->columns(3),
                Forms\Components\Section::make('Notifications')->schema([
                    Forms\Components\Toggle::make('notify_kpi_threshold_exceeded')->label('KPI Threshold Exceeded'),
                    Forms\Components\Toggle::make('notify_budget_risk')->label('Budget Risk'),
                    Forms\Components\Toggle::make('notify_safety_risk')->label('Safety Risk'),
                    Forms\Components\Toggle::make('notify_project_delay')->label('Project Delay'),
                    Forms\Components\Toggle::make('notify_scheduled_report_ready')->label('Scheduled Report Ready'),
                ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        BiSetting::setSetting($companyId, 'budget_overrun_threshold', $state['budget_overrun_threshold'], 'float');
        BiSetting::setSetting($companyId, 'schedule_slippage_threshold', $state['schedule_slippage_threshold'], 'float');
        BiSetting::setSetting($companyId, 'safety_score_alert_threshold', $state['safety_score_alert_threshold'], 'integer');
        BiSetting::setSetting($companyId, 'quality_score_alert_threshold', $state['quality_score_alert_threshold'], 'integer');
        BiSetting::setSetting($companyId, 'default_export_format', $state['default_export_format'], 'string');
        BiSetting::setSetting($companyId, 'default_report_frequency', $state['default_report_frequency'], 'string');
        BiSetting::setSetting($companyId, 'ai_insights_enabled', $state['ai_insights_enabled'], 'boolean');
        BiSetting::setSetting($companyId, 'notify_kpi_threshold_exceeded', $state['notify_kpi_threshold_exceeded'], 'boolean');
        BiSetting::setSetting($companyId, 'notify_budget_risk', $state['notify_budget_risk'], 'boolean');
        BiSetting::setSetting($companyId, 'notify_safety_risk', $state['notify_safety_risk'], 'boolean');
        BiSetting::setSetting($companyId, 'notify_project_delay', $state['notify_project_delay'], 'boolean');
        BiSetting::setSetting($companyId, 'notify_scheduled_report_ready', $state['notify_scheduled_report_ready'], 'boolean');

        Notification::make()->title('Business Intelligence settings saved')->success()->send();
    }
}
