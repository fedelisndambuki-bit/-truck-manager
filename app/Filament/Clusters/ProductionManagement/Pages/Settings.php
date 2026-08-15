<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Models\ProductionSetting;
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

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.manage';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Production Management Settings';

    protected static string $view = 'filament.clusters.production-management.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'low_yield_threshold' => ProductionSetting::getSetting($companyId, 'low_yield_threshold', 70),
            'target_shortfall_threshold' => ProductionSetting::getSetting($companyId, 'target_shortfall_threshold', 80),
            'downtime_alert_minutes' => ProductionSetting::getSetting($companyId, 'downtime_alert_minutes', 60),
            'notify_email' => ProductionSetting::getSetting($companyId, 'notify_email', true),
            'notify_database' => ProductionSetting::getSetting($companyId, 'notify_database', true),
        ]);
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />',
            'title' => 'Settings',
            'subtitle' => 'Alert thresholds and notification channels for Production Management.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Alert Thresholds')->schema([
                    Forms\Components\TextInput::make('low_yield_threshold')
                        ->label('Low Yield Alert Threshold (%)')->numeric()->minValue(0)->maxValue(100)->required()
                        ->helperText('Production records with yield % below this trigger a low-yield notification.'),
                    Forms\Components\TextInput::make('target_shortfall_threshold')
                        ->label('Target Shortfall Alert Threshold (%)')->numeric()->minValue(0)->maxValue(100)->required()
                        ->helperText('Achievement % below this triggers a below-target notification.'),
                    Forms\Components\TextInput::make('downtime_alert_minutes')
                        ->label('Downtime Alert Threshold (minutes)')->numeric()->minValue(1)->required()
                        ->helperText('Single downtime events longer than this trigger a notification.'),
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

        ProductionSetting::setSetting($companyId, 'low_yield_threshold', $state['low_yield_threshold'], 'integer');
        ProductionSetting::setSetting($companyId, 'target_shortfall_threshold', $state['target_shortfall_threshold'], 'integer');
        ProductionSetting::setSetting($companyId, 'downtime_alert_minutes', $state['downtime_alert_minutes'], 'integer');
        ProductionSetting::setSetting($companyId, 'notify_email', $state['notify_email'], 'boolean');
        ProductionSetting::setSetting($companyId, 'notify_database', $state['notify_database'], 'boolean');

        Notification::make()->title('Production Management settings saved')->success()->send();
    }
}
