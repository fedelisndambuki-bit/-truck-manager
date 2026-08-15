<?php

namespace App\Filament\Resources\DailySiteDiaryResource\Pages;

use App\Filament\Resources\DailySiteDiaryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDailySiteDiaries extends ListRecords
{
    protected static string $resource = DailySiteDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-book-open',
            'title' => 'Daily Site Diaries',
            'subtitle' => 'Review daily logs of site activity, workforce, and progress.',
        ]);
    }
}
