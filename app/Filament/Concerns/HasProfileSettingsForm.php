<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait HasProfileSettingsForm
{
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => Auth::user()->name,
            'avatar_path' => Auth::user()->avatar_path,
        ]);
    }

    protected function profileSettingsForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Display name')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('avatar_path')
                    ->label('Photo')
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Auth::user();

        if ($user->avatar_path && $user->avatar_path !== ($state['avatar_path'] ?? null)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'name' => $state['name'],
            'avatar_path' => $state['avatar_path'] ?? null,
        ]);

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }

    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->form->fill(['avatar_path' => null]);

        Notification::make()
            ->title('Photo removed')
            ->success()
            ->send();
    }
}
