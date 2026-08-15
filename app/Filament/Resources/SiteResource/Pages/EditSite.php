<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, SiteResource::getTeamFormStateForProject($this->record));
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();

        SiteResource::syncTeamFromFormState($this->record, $state['team'] ?? []);
    }
}
