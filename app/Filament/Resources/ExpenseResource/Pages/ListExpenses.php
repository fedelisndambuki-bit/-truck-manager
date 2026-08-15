<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function deleteTableRecord(string $record): void
    {
        $this->getModel()::findOrFail($record)->delete();

        \Filament\Notifications\Notification::make()
            ->title('Expense deleted')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chart-bar',
            'title' => 'Expenses',
            'subtitle' => 'Track and approve fleet operating expenses.',
        ]);
    }
}
