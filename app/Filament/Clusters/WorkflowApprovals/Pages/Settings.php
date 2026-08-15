<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Models\WorkflowSetting;
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

    public static function getModuleSlug(): ?string
    {
        return 'workflow.manage';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Workflow & Approvals Settings';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_sla_hours' => WorkflowSetting::getSetting($companyId, 'default_sla_hours', 48),
            'default_approver_resolution_mode' => WorkflowSetting::getSetting($companyId, 'default_approver_resolution_mode', 'any_one'),
            'escalation_default_hours' => WorkflowSetting::getSetting($companyId, 'escalation_default_hours', 24),
            'notify_email' => WorkflowSetting::getSetting($companyId, 'notify_email', true),
            'notify_database' => WorkflowSetting::getSetting($companyId, 'notify_database', true),
            'notify_sms' => WorkflowSetting::getSetting($companyId, 'notify_sms', false),
            'notify_push' => WorkflowSetting::getSetting($companyId, 'notify_push', false),
            'notify_teams' => WorkflowSetting::getSetting($companyId, 'notify_teams', false),
            'notify_slack' => WorkflowSetting::getSetting($companyId, 'notify_slack', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Defaults')->schema([
                    Forms\Components\TextInput::make('default_sla_hours')
                        ->label('Default Step SLA (hours)')->numeric()->minValue(1)->required(),
                    Forms\Components\Select::make('default_approver_resolution_mode')->label('Default Resolution Mode')->options([
                        'any_one' => 'Any one approver', 'all_required' => 'All required (consensus)',
                        'majority' => 'Majority vote', 'single' => 'Single (first assigned)',
                    ])->required(),
                    Forms\Components\TextInput::make('escalation_default_hours')
                        ->label('Default Escalation Delay (hours)')->numeric()->minValue(1)->required(),
                ])->columns(3),
                Forms\Components\Section::make('Notification Channels')
                    ->description('Database and Email are fully wired. SMS, Push, Microsoft Teams and Slack are shown for future configuration — no external service is connected yet.')
                    ->schema([
                        Forms\Components\Toggle::make('notify_database')->label('In-App (Database)'),
                        Forms\Components\Toggle::make('notify_email')->label('Email'),
                        Forms\Components\Toggle::make('notify_sms')->label('SMS')->helperText('Coming soon'),
                        Forms\Components\Toggle::make('notify_push')->label('Push Notification')->helperText('Coming soon'),
                        Forms\Components\Toggle::make('notify_teams')->label('Microsoft Teams')->helperText('Coming soon'),
                        Forms\Components\Toggle::make('notify_slack')->label('Slack')->helperText('Coming soon'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        WorkflowSetting::setSetting($companyId, 'default_sla_hours', $state['default_sla_hours'], 'integer');
        WorkflowSetting::setSetting($companyId, 'default_approver_resolution_mode', $state['default_approver_resolution_mode'], 'string');
        WorkflowSetting::setSetting($companyId, 'escalation_default_hours', $state['escalation_default_hours'], 'integer');
        WorkflowSetting::setSetting($companyId, 'notify_email', $state['notify_email'], 'boolean');
        WorkflowSetting::setSetting($companyId, 'notify_database', $state['notify_database'], 'boolean');
        WorkflowSetting::setSetting($companyId, 'notify_sms', $state['notify_sms'], 'boolean');
        WorkflowSetting::setSetting($companyId, 'notify_push', $state['notify_push'], 'boolean');
        WorkflowSetting::setSetting($companyId, 'notify_teams', $state['notify_teams'], 'boolean');
        WorkflowSetting::setSetting($companyId, 'notify_slack', $state['notify_slack'], 'boolean');

        Notification::make()->title('Workflow & Approvals settings saved')->success()->send();
    }
}
