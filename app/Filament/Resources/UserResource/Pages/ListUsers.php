<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ExternalOrganization;
use App\Models\Project;
use App\Services\PortalInvitationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.list-users';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('inviteExternalUser')
                ->label('Invite External User')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->form([
                    Forms\Components\TextInput::make('email')->email()->required(),
                    Forms\Components\Select::make('external_organization_id')
                        ->label('Company')
                        ->options(fn () => ExternalOrganization::query()->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::query()->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('role')
                        ->label('Portal Role')
                        ->options(fn () => Role::where('guard_name', 'web')->where('name', '!=', 'Super Admin')->pluck('name', 'name')),
                ])
                ->action(function (array $data) {
                    app(PortalInvitationService::class)->invite(
                        $data['email'],
                        ExternalOrganization::find($data['external_organization_id'] ?? null),
                        Project::find($data['project_id'] ?? null),
                        $data['role'] ?? null,
                    );

                    Notification::make()->title('Invitation sent to ' . $data['email'])->success()->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
