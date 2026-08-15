<?php

namespace App\Filament\Pages;

use App\Models\DiarySetting;
use App\Traits\HasIndustryAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class DailySiteDiarySettings extends \Filament\Pages\Page implements HasForms
{
    use HasIndustryAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Diary Settings';

    protected static ?string $slug = 'daily-site-diary-settings';

    protected static string $view = 'filament.pages.daily-site-diary-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'auto_lock_after_days' => DiarySetting::getSetting($companyId, 'auto_lock_after_days', 7),
            'weather_auto_fetch_enabled' => DiarySetting::getSetting($companyId, 'weather_auto_fetch_enabled', true),
            'notify_missing_diary' => DiarySetting::getSetting($companyId, 'notify_missing_diary', true),
            'notify_submitted' => DiarySetting::getSetting($companyId, 'notify_submitted', true),
            'notify_approved' => DiarySetting::getSetting($companyId, 'notify_approved', true),
            'notify_rejected' => DiarySetting::getSetting($companyId, 'notify_rejected', true),
            'notify_severe_weather' => DiarySetting::getSetting($companyId, 'notify_severe_weather', true),
            'notify_high_delay' => DiarySetting::getSetting($companyId, 'notify_high_delay', true),
            'notify_equipment_breakdown' => DiarySetting::getSetting($companyId, 'notify_equipment_breakdown', true),
            'notify_material_shortage' => DiarySetting::getSetting($companyId, 'notify_material_shortage', true),
            'default_approval_chain' => DiarySetting::getSetting($companyId, 'default_approval_chain', 'Site Supervisor,Site Manager,Project Manager,Construction Manager'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Workflow')->schema([
                    Forms\Components\TextInput::make('auto_lock_after_days')
                        ->label('Auto-lock diaries after (days)')
                        ->numeric()->minValue(1)
                        ->helperText('Approved diaries are automatically locked after this many days.'),
                    Forms\Components\TextInput::make('default_approval_chain')
                        ->label('Default Approval Chain')
                        ->helperText('Comma-separated role names, in order.'),
                ])->columns(2),

                Forms\Components\Section::make('Weather')->schema([
                    Forms\Components\Toggle::make('weather_auto_fetch_enabled')
                        ->label('Auto-fetch weather from Open-Meteo when creating a diary'),
                ]),

                Forms\Components\Section::make('Notifications')->schema([
                    Forms\Components\Toggle::make('notify_missing_diary')->label('Diary Missing'),
                    Forms\Components\Toggle::make('notify_submitted')->label('Diary Submitted'),
                    Forms\Components\Toggle::make('notify_approved')->label('Diary Approved'),
                    Forms\Components\Toggle::make('notify_rejected')->label('Diary Rejected'),
                    Forms\Components\Toggle::make('notify_severe_weather')->label('Severe Weather'),
                    Forms\Components\Toggle::make('notify_high_delay')->label('High Impact Delay'),
                    Forms\Components\Toggle::make('notify_equipment_breakdown')->label('Equipment Breakdown'),
                    Forms\Components\Toggle::make('notify_material_shortage')->label('Material Shortage'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        DiarySetting::setSetting($companyId, 'auto_lock_after_days', $state['auto_lock_after_days'], 'integer');
        DiarySetting::setSetting($companyId, 'weather_auto_fetch_enabled', $state['weather_auto_fetch_enabled'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_missing_diary', $state['notify_missing_diary'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_submitted', $state['notify_submitted'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_approved', $state['notify_approved'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_rejected', $state['notify_rejected'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_severe_weather', $state['notify_severe_weather'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_high_delay', $state['notify_high_delay'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_equipment_breakdown', $state['notify_equipment_breakdown'], 'boolean');
        DiarySetting::setSetting($companyId, 'notify_material_shortage', $state['notify_material_shortage'], 'boolean');
        DiarySetting::setSetting($companyId, 'default_approval_chain', $state['default_approval_chain']);

        Notification::make()->title('Diary settings saved')->success()->send();
    }
}
