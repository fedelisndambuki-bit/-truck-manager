<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\PlatformSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.super-admin.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'super_admin_platform_name' => PlatformSetting::getSetting('super_admin_platform_name', 'Tanaki Control Room'),
            'tenant_default_platform_name' => PlatformSetting::getSetting('tenant_default_platform_name', 'Truck Manager'),
            'footer_text' => PlatformSetting::getSetting('footer_text', '© 2026 Truck Manager'),
            'contact_email' => PlatformSetting::getSetting('contact_email', 'support@tanakihub.com'),
            'contact_phone' => PlatformSetting::getSetting('contact_phone', '+254...'),
            'enable_registration' => (bool) PlatformSetting::getSetting('enable_registration', true),
            'maintenance_mode' => (bool) PlatformSetting::getSetting('maintenance_mode', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-m-globe-alt')
                            ->schema([
                                Section::make('Branding')
                                    ->description('Manage platform identification and footer.')
                                    ->schema([
                                        TextInput::make('super_admin_platform_name')
                                            ->label('Control Room Name')
                                            ->helperText('Name displayed on the Tanaki Control Room panel.')
                                            ->required(),
                                        TextInput::make('tenant_default_platform_name')
                                            ->label('Tenant Default Platform Name')
                                            ->helperText('Default name for company panels (if they haven\'t set their own).')
                                            ->required(),
                                        TextInput::make('footer_text')
                                            ->label('Footer Text')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),
                        Tabs\Tab::make('Contact')
                            ->icon('heroicon-m-envelope')
                            ->schema([
                                Section::make('Support Contact')
                                    ->description('Public contact information for companies.')
                                    ->schema([
                                        TextInput::make('contact_email')
                                            ->label('Support Email')
                                            ->email()
                                            ->required(),
                                        TextInput::make('contact_phone')
                                            ->label('Support Phone')
                                            ->tel(),
                                    ])->columns(2),
                            ]),
                        Tabs\Tab::make('System')
                            ->icon('heroicon-m-cpu-chip')
                            ->schema([
                                Section::make('Global Controls')
                                    ->description('Control platform-wide features and availability.')
                                    ->schema([
                                        Toggle::make('enable_registration')
                                            ->label('Allow New Company Registrations')
                                            ->helperText('When disabled, the registration page will be hidden.')
                                            ->default(true),
                                        Toggle::make('maintenance_mode')
                                            ->label('Maintenance Mode')
                                            ->helperText('Put the entire platform into maintenance mode (simulated).')
                                            ->default(false),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            PlatformSetting::setSetting(
                $key, 
                $value, 
                is_bool($value) ? 'boolean' : 'string',
                $this->getGroupForKey($key)
            );
        }

        Notification::make()
            ->title('Settings Saved')
            ->body('Platform configurations have been updated successfully.')
            ->success()
            ->send();
    }

    private function getGroupForKey(string $key): string
    {
        return match ($key) {
            'super_admin_platform_name', 'tenant_default_platform_name', 'footer_text' => 'branding',
            'contact_email', 'contact_phone' => 'contact',
            'enable_registration', 'maintenance_mode' => 'system',
            default => 'general',
        };
    }
}
