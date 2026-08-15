<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\EdmsSetting;
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
        return 'documents.manage';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'Document Management Settings';

    protected static string $view = 'filament.clusters.document-management.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_retention_period_days' => EdmsSetting::getSetting($companyId, 'default_retention_period_days', 2555),
            'review_reminder_days' => EdmsSetting::getSetting($companyId, 'review_reminder_days', 14),
            'contract_expiry_alert_days' => EdmsSetting::getSetting($companyId, 'contract_expiry_alert_days', 30),
            'rfi_due_alert_days' => EdmsSetting::getSetting($companyId, 'rfi_due_alert_days', 2),
            'default_approver_chain' => EdmsSetting::getSetting($companyId, 'default_approver_chain', 'Site Engineer,Project Manager,Construction Manager'),
            'notify_document_uploaded' => EdmsSetting::getSetting($companyId, 'notify_document_uploaded', true),
            'notify_approval_required' => EdmsSetting::getSetting($companyId, 'notify_approval_required', true),
            'notify_revision_submitted' => EdmsSetting::getSetting($companyId, 'notify_revision_submitted', true),
            'notify_rfi_due' => EdmsSetting::getSetting($companyId, 'notify_rfi_due', true),
            'notify_contract_expiring' => EdmsSetting::getSetting($companyId, 'notify_contract_expiring', true),
            'notify_drawing_updated' => EdmsSetting::getSetting($companyId, 'notify_drawing_updated', true),
            'notify_approval_completed' => EdmsSetting::getSetting($companyId, 'notify_approval_completed', true),
            'notify_document_shared' => EdmsSetting::getSetting($companyId, 'notify_document_shared', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Defaults')->schema([
                    Forms\Components\TextInput::make('default_retention_period_days')
                        ->label('Default Retention Period (days)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('review_reminder_days')
                        ->label('Review Reminder Lead Time (days)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('default_approver_chain')
                        ->label('Default Approver Chain')
                        ->helperText('Comma-separated roles used to pre-fill new approval workflows.'),
                ])->columns(2),
                Forms\Components\Section::make('Alert Thresholds')->schema([
                    Forms\Components\TextInput::make('contract_expiry_alert_days')
                        ->label('Contract Expiry Alert (days)')->numeric()->minValue(1),
                    Forms\Components\TextInput::make('rfi_due_alert_days')
                        ->label('RFI Due Alert (days)')->numeric()->minValue(1),
                ])->columns(2),
                Forms\Components\Section::make('Notifications')->schema([
                    Forms\Components\Toggle::make('notify_document_uploaded')->label('Document Uploaded'),
                    Forms\Components\Toggle::make('notify_approval_required')->label('Approval Required'),
                    Forms\Components\Toggle::make('notify_revision_submitted')->label('Revision Submitted'),
                    Forms\Components\Toggle::make('notify_rfi_due')->label('RFI Due'),
                    Forms\Components\Toggle::make('notify_contract_expiring')->label('Contract Expiring'),
                    Forms\Components\Toggle::make('notify_drawing_updated')->label('Drawing Updated'),
                    Forms\Components\Toggle::make('notify_approval_completed')->label('Approval Completed'),
                    Forms\Components\Toggle::make('notify_document_shared')->label('Document Shared'),
                ])->columns(4),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        EdmsSetting::setSetting($companyId, 'default_retention_period_days', $state['default_retention_period_days'], 'integer');
        EdmsSetting::setSetting($companyId, 'review_reminder_days', $state['review_reminder_days'], 'integer');
        EdmsSetting::setSetting($companyId, 'default_approver_chain', $state['default_approver_chain']);
        EdmsSetting::setSetting($companyId, 'contract_expiry_alert_days', $state['contract_expiry_alert_days'], 'integer');
        EdmsSetting::setSetting($companyId, 'rfi_due_alert_days', $state['rfi_due_alert_days'], 'integer');
        EdmsSetting::setSetting($companyId, 'notify_document_uploaded', $state['notify_document_uploaded'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_approval_required', $state['notify_approval_required'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_revision_submitted', $state['notify_revision_submitted'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_rfi_due', $state['notify_rfi_due'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_contract_expiring', $state['notify_contract_expiring'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_drawing_updated', $state['notify_drawing_updated'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_approval_completed', $state['notify_approval_completed'], 'boolean');
        EdmsSetting::setSetting($companyId, 'notify_document_shared', $state['notify_document_shared'], 'boolean');

        Notification::make()->title('Document Management settings saved')->success()->send();
    }
}
