<?php

namespace App\Filament\Resources\AiLogResource\Pages;

use App\Filament\Resources\AiLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListAiLogs extends ListRecords
{
    protected static string $resource = AiLogResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cpu-chip',
            'title' => 'AI Logs',
            'subtitle' => 'Review AI assistant questions, responses, and performance history.',
        ]);
    }
}
