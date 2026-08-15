<?php

namespace App\Filament\Pages;

use App\Models\SubcontractorSetting;
use App\Traits\HasIndustryAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SubcontractorSettingsPage extends \Filament\Pages\Page implements HasForms
{
    use HasIndustryAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Subcontractor Settings';

    protected static ?string $slug = 'subcontractor-settings';

    protected static string $view = 'filament.pages.subcontractor-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_retention_percent' => SubcontractorSetting::getSetting($companyId, 'default_retention_percent', 10),
            'default_payment_terms' => SubcontractorSetting::getSetting($companyId, 'default_payment_terms', 'Net 30 days'),
            'compliance_alert_lead_days' => SubcontractorSetting::getSetting($companyId, 'compliance_alert_lead_days', 30),
            'performance_review_frequency' => SubcontractorSetting::getSetting($companyId, 'performance_review_frequency', 'Quarterly'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contract & Compliance Defaults')->schema([
                    Forms\Components\TextInput::make('default_retention_percent')
                        ->label('Default Retention (%)')
                        ->numeric()->minValue(0)->maxValue(100)->required(),
                    Forms\Components\TextInput::make('default_payment_terms')
                        ->label('Default Payment Terms')
                        ->required(),
                    Forms\Components\TextInput::make('compliance_alert_lead_days')
                        ->label('Compliance Alert Lead Time (days)')
                        ->numeric()->minValue(1)
                        ->helperText('How many days before insurance/license/NCA expiry to raise a compliance alert.'),
                    Forms\Components\Select::make('performance_review_frequency')->options([
                        'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Annually' => 'Annually',
                    ])->required(),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        SubcontractorSetting::setSetting($companyId, 'default_retention_percent', $state['default_retention_percent'], 'integer');
        SubcontractorSetting::setSetting($companyId, 'default_payment_terms', $state['default_payment_terms']);
        SubcontractorSetting::setSetting($companyId, 'compliance_alert_lead_days', $state['compliance_alert_lead_days'], 'integer');
        SubcontractorSetting::setSetting($companyId, 'performance_review_frequency', $state['performance_review_frequency']);

        Notification::make()->title('Subcontractor settings saved')->success()->send();
    }
}
