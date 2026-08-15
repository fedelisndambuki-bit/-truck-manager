<?php

namespace App\Filament\SuperAdmin\Resources\PlanResource\Pages;

use App\Filament\SuperAdmin\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected static string $view = 'filament.super-admin.resources.plan-resource.pages.list-plans';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
