<?php

namespace App\Filament\Pages;

use App\Models\HrSetting;
use App\Traits\HasIndustryAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class HrSettingsPage extends \Filament\Pages\Page implements HasForms
{
    use HasIndustryAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 19;

    protected static ?string $title = 'HR Settings';

    protected static ?string $slug = 'hr-settings';

    protected static string $view = 'filament.pages.hr-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_currency' => HrSetting::getSetting($companyId, 'default_currency', 'KES'),
            'pay_day' => HrSetting::getSetting($companyId, 'pay_day', 28),
            'leave_year_start_month' => HrSetting::getSetting($companyId, 'leave_year_start_month', 1),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payroll & Leave Configuration')->schema([
                    Forms\Components\TextInput::make('default_currency')->required(),
                    Forms\Components\TextInput::make('pay_day')->numeric()->minValue(1)->maxValue(31)->helperText('Day of the month payroll is typically processed.'),
                    Forms\Components\Select::make('leave_year_start_month')->options([
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
                        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                    ])->helperText('Month the annual leave allocation cycle resets.'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        HrSetting::setSetting($companyId, 'default_currency', $state['default_currency']);
        HrSetting::setSetting($companyId, 'pay_day', $state['pay_day'], 'integer');
        HrSetting::setSetting($companyId, 'leave_year_start_month', $state['leave_year_start_month'], 'integer');

        Notification::make()->title('HR settings saved')->success()->send();
    }
}
