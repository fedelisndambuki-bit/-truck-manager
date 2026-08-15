<?php

namespace App\Filament\Pages;

use App\Models\MaterialsSetting;
use App\Models\Warehouse;
use App\Traits\HasIndustryAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class MaterialsSettingsPage extends \Filament\Pages\Page implements HasForms
{
    use HasIndustryAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Materials Settings';

    protected static ?string $slug = 'materials-settings';

    protected static string $view = 'filament.pages.materials-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;

        $this->form->fill([
            'default_currency' => MaterialsSetting::getSetting($companyId, 'default_currency', 'KES'),
            'default_warehouse_id' => MaterialsSetting::getSetting($companyId, 'default_warehouse_id'),
            'low_stock_threshold_percent' => MaterialsSetting::getSetting($companyId, 'low_stock_threshold_percent', 20),
            'stock_count_frequency' => MaterialsSetting::getSetting($companyId, 'stock_count_frequency', 'Quarterly'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Procurement & Stock Configuration')->schema([
                    Forms\Components\TextInput::make('default_currency')->required(),
                    Forms\Components\Select::make('default_warehouse_id')
                        ->label('Default Warehouse')
                        ->options(fn () => Warehouse::query()->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\TextInput::make('low_stock_threshold_percent')
                        ->label('Low Stock Alert Threshold (%)')
                        ->numeric()->minValue(1)->maxValue(100)
                        ->helperText('Percentage above reorder level that triggers an early low-stock warning.'),
                    Forms\Components\Select::make('stock_count_frequency')->options([
                        'Monthly' => 'Monthly', 'Quarterly' => 'Quarterly', 'Annually' => 'Annually',
                    ]),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->form->getState();

        MaterialsSetting::setSetting($companyId, 'default_currency', $state['default_currency']);
        MaterialsSetting::setSetting($companyId, 'default_warehouse_id', $state['default_warehouse_id'], 'integer');
        MaterialsSetting::setSetting($companyId, 'low_stock_threshold_percent', $state['low_stock_threshold_percent'], 'integer');
        MaterialsSetting::setSetting($companyId, 'stock_count_frequency', $state['stock_count_frequency']);

        Notification::make()->title('Materials settings saved')->success()->send();
    }
}
