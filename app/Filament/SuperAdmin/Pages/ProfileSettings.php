<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Concerns\HasProfileSettingsForm;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasProfileSettingsForm;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $title = 'Profile Settings';
    protected static ?string $slug = 'profile-settings';
    protected static string $view = 'filament.super-admin.pages.profile-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $this->profileSettingsForm($form);
    }
}
