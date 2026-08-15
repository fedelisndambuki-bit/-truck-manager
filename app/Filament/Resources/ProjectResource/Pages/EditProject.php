<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, ProjectResource::getTeamFormStateForProject($this->record));
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();

        ProjectResource::syncTeamFromFormState($this->record, $state['team'] ?? []);
    }
}
