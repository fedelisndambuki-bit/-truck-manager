<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Models\WeighbridgeSetting;
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
        return 'weighbridge.manage';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Smart Weighbridge Settings';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_tolerance_tonnes' => WeighbridgeSetting::getSetting($companyId, 'default_tolerance_tonnes', 0.20),
            'critical_variance_multiplier' => WeighbridgeSetting::getSetting($companyId, 'critical_variance_multiplier', 2),
            'queue_delay_threshold_minutes' => WeighbridgeSetting::getSetting($companyId, 'queue_delay_threshold_minutes', 45),
            'auto_assign_loading_bay' => WeighbridgeSetting::getSetting($companyId, 'auto_assign_loading_bay', true),
            'auto_assign_loader' => WeighbridgeSetting::getSetting($companyId, 'auto_assign_loader', true),
            'notify_email' => WeighbridgeSetting::getSetting($companyId, 'notify_email', true),
            'notify_database' => WeighbridgeSetting::getSetting($companyId, 'notify_database', true),
        ]);
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />',
            'title' => 'Settings',
            'subtitle' => 'Tolerance, escalation and notification configuration for Smart Weighbridge.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Weighing Tolerance')->schema([
                    Forms\Components\TextInput::make('default_tolerance_tonnes')
                        ->label('Default Tolerance (tonnes)')->numeric()->minValue(0)->required(),
                    Forms\Components\TextInput::make('critical_variance_multiplier')
                        ->label('Critical Variance Multiplier (× tolerance)')->numeric()->minValue(1)->required(),
                    Forms\Components\TextInput::make('queue_delay_threshold_minutes')
                        ->label('Queue Delay Threshold (minutes)')->numeric()->minValue(1)->required(),
                ])->columns(3),
                Forms\Components\Section::make('Smart Assignment')->schema([
                    Forms\Components\Toggle::make('auto_assign_loading_bay')->label('Auto-Assign Loading Bay'),
                    Forms\Components\Toggle::make('auto_assign_loader')->label('Auto-Assign Loader'),
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

        WeighbridgeSetting::setSetting($companyId, 'default_tolerance_tonnes', $state['default_tolerance_tonnes'], 'float');
        WeighbridgeSetting::setSetting($companyId, 'critical_variance_multiplier', $state['critical_variance_multiplier'], 'float');
        WeighbridgeSetting::setSetting($companyId, 'queue_delay_threshold_minutes', $state['queue_delay_threshold_minutes'], 'integer');
        WeighbridgeSetting::setSetting($companyId, 'auto_assign_loading_bay', $state['auto_assign_loading_bay'], 'boolean');
        WeighbridgeSetting::setSetting($companyId, 'auto_assign_loader', $state['auto_assign_loader'], 'boolean');
        WeighbridgeSetting::setSetting($companyId, 'notify_email', $state['notify_email'], 'boolean');
        WeighbridgeSetting::setSetting($companyId, 'notify_database', $state['notify_database'], 'boolean');

        Notification::make()->title('Smart Weighbridge settings saved')->success()->send();
    }
}
