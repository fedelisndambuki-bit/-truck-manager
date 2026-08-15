<?php

namespace App\Filament\Resources\AiLogResource\Pages;

use App\Filament\Resources\AiLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAiLogs extends ListRecords
{
    protected static string $resource = AiLogResource::class;

    protected static string $view = 'filament.resources.ai-log-resource.pages.list-ai-logs';
}
