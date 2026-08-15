<?php

namespace App\Filament\Pages;

use App\Models\SafetySetting;
use App\Traits\HasIndustryAccess;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SafetySettings extends \Filament\Pages\Page implements HasForms
{
    use HasIndustryAccess;
    use Forms\Concerns\InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 170;

    protected static ?string $title = 'Safety Settings';

    protected static ?string $slug = 'safety-settings';

    protected static string $view = 'filament.pages.safety-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'inspection_due_reminder_days' => SafetySetting::getSetting($companyId, 'inspection_due_reminder_days', 7),
            'training_expiry_reminder_days' => SafetySetting::getSetting($companyId, 'training_expiry_reminder_days', 30),
            'ppe_reminder_days' => SafetySetting::getSetting($companyId, 'ppe_reminder_days', 14),
            'compliance_reminder_days' => SafetySetting::getSetting($companyId, 'compliance_reminder_days', 30),
            'drill_frequency_months' => SafetySetting::getSetting($companyId, 'drill_frequency_months', 6),
            'notify_incident_reported' => SafetySetting::getSetting($companyId, 'notify_incident_reported', true),
            'notify_high_risk_hazard' => SafetySetting::getSetting($companyId, 'notify_high_risk_hazard', true),
            'notify_inspection_due' => SafetySetting::getSetting($companyId, 'notify_inspection_due', true),
            'notify_corrective_action_overdue' => SafetySetting::getSetting($companyId, 'notify_corrective_action_overdue', true),
            'notify_training_expiring' => SafetySetting::getSetting($companyId, 'notify_training_expiring', true),
            'notify_ppe_replacement_due' => SafetySetting::getSetting($companyId, 'notify_ppe_replacement_due', true),
            'notify_emergency_drill_due' => SafetySetting::getSetting($companyId, 'notify_emergency_drill_due', true),
            'notify_compliance_expiring' => SafetySetting::getSetting($companyId, 'notify_compliance_expiring', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reminder Thresholds')->schema([
                    Forms\Components\TextInput::make('inspection_due_reminder_days')->label('Inspection due reminder (days ahead)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('training_expiry_reminder_days')->label('Training expiry reminder (days ahead)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('ppe_reminder_days')->label('PPE replacement reminder (days ahead)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('compliance_reminder_days')->label('Compliance expiry reminder (days ahead)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('drill_frequency_months')->label('Required emergency drill frequency (months)')->numeric()->minValue(1),
                ])->columns(2),

                Forms\Components\Section::make('Notifications')->schema([
                    Forms\Components\Toggle::make('notify_incident_reported')->label('Incident Reported'),
                    Forms\Components\Toggle::make('notify_high_risk_hazard')->label('High Risk Hazard'),
                    Forms\Components\Toggle::make('notify_inspection_due')->label('Inspection Due'),
                    Forms\Components\Toggle::make('notify_corrective_action_overdue')->label('Corrective Action Overdue'),
                    Forms\Components\Toggle::make('notify_training_expiring')->label('Training Expiring'),
                    Forms\Components\Toggle::make('notify_ppe_replacement_due')->label('PPE Replacement Due'),
                    Forms\Components\Toggle::make('notify_emergency_drill_due')->label('Emergency Drill Due'),
                    Forms\Components\Toggle::make('notify_compliance_expiring')->label('Compliance Expiring'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        foreach (['inspection_due_reminder_days', 'training_expiry_reminder_days', 'ppe_reminder_days', 'compliance_reminder_days', 'drill_frequency_months'] as $key) {
            SafetySetting::setSetting($companyId, $key, $state[$key], 'integer');
        }

        foreach ([
            'notify_incident_reported', 'notify_high_risk_hazard', 'notify_inspection_due',
            'notify_corrective_action_overdue', 'notify_training_expiring', 'notify_ppe_replacement_due',
            'notify_emergency_drill_due', 'notify_compliance_expiring',
        ] as $key) {
            SafetySetting::setSetting($companyId, $key, $state[$key], 'boolean');
        }

        Notification::make()->title('Safety settings saved')->success()->send();
    }
}
