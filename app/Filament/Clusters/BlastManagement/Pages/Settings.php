<?php

namespace App\Filament\Clusters\BlastManagement\Pages;

use App\Filament\Clusters\BlastManagement;
use App\Models\BlastSetting;
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
        return 'blast.manage';
    }

    protected static ?string $cluster = BlastManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Blast Management Settings';

    protected static string $view = 'filament.clusters.blast-management.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'permit_expiry_reminder_days' => BlastSetting::getSetting($companyId, 'permit_expiry_reminder_days', 30),
            'low_explosive_stock_threshold' => BlastSetting::getSetting($companyId, 'low_explosive_stock_threshold', 50),
            'ground_vibration_limit' => BlastSetting::getSetting($companyId, 'ground_vibration_limit', 12),
            'air_overpressure_limit' => BlastSetting::getSetting($companyId, 'air_overpressure_limit', 120),
            'notify_email' => BlastSetting::getSetting($companyId, 'notify_email', true),
            'notify_database' => BlastSetting::getSetting($companyId, 'notify_database', true),
        ]);
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />',
            'title' => 'Settings',
            'subtitle' => 'Alert thresholds and notification channels for Blast Management.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Alert Thresholds')->schema([
                    Forms\Components\TextInput::make('permit_expiry_reminder_days')
                        ->label('Permit Expiry Reminder (days)')->numeric()->minValue(1)->required()
                        ->helperText('Notify when a blast permit expires within this many days.'),
                    Forms\Components\TextInput::make('low_explosive_stock_threshold')
                        ->label('Low Explosive Stock Threshold (kg)')->numeric()->minValue(0)->required()
                        ->helperText('Notify when remaining explosive quantity falls below this.'),
                    Forms\Components\TextInput::make('ground_vibration_limit')
                        ->label('Ground Vibration Limit (mm/s)')->numeric()->minValue(0)->required()
                        ->helperText('Readings above this trigger an environmental threshold alert.'),
                    Forms\Components\TextInput::make('air_overpressure_limit')
                        ->label('Air Overpressure Limit (dB(L))')->numeric()->minValue(0)->required(),
                ])->columns(2),
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

        BlastSetting::setSetting($companyId, 'permit_expiry_reminder_days', $state['permit_expiry_reminder_days'], 'integer');
        BlastSetting::setSetting($companyId, 'low_explosive_stock_threshold', $state['low_explosive_stock_threshold'], 'integer');
        BlastSetting::setSetting($companyId, 'ground_vibration_limit', $state['ground_vibration_limit'], 'float');
        BlastSetting::setSetting($companyId, 'air_overpressure_limit', $state['air_overpressure_limit'], 'float');
        BlastSetting::setSetting($companyId, 'notify_email', $state['notify_email'], 'boolean');
        BlastSetting::setSetting($companyId, 'notify_database', $state['notify_database'], 'boolean');

        Notification::make()->title('Blast Management settings saved')->success()->send();
    }
}
