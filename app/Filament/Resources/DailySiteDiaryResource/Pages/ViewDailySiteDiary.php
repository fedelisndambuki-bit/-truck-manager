<?php

namespace App\Filament\Resources\DailySiteDiaryResource\Pages;

use App\Filament\Resources\DailySiteDiaryResource;
use App\Models\Document;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewDailySiteDiary extends ViewRecord
{
    protected static string $resource = DailySiteDiaryResource::class;

    protected static string $view = 'filament.resources.daily-site-diary-resource.pages.view-daily-site-diary';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\EditAction::make(),

            Actions\Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $record->status === 'Draft'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.submit')))
                ->action(function () use ($record) {
                    $record->submit();
                    Notification::make()->title('Diary submitted for review')->success()->send();
                }),

            Actions\Action::make('review')
                ->label('Mark Reviewed')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => $record->status === 'Submitted'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.review')))
                ->action(function () use ($record) {
                    $record->markReviewed();
                    Notification::make()->title('Diary marked as reviewed')->success()->send();
                }),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => in_array($record->status, ['Submitted', 'Reviewed'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.approve')))
                ->action(function () use ($record) {
                    $record->approve();
                    Notification::make()->title('Diary approved')->success()->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('reason')->label('Rejection Reason')->required(),
                ])
                ->visible(fn () => in_array($record->status, ['Submitted', 'Reviewed'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.reject')))
                ->action(function (array $data) use ($record) {
                    $record->reject($data['reason']);
                    Notification::make()->title('Diary rejected')->danger()->send();
                }),

            Actions\Action::make('lock')
                ->label('Lock')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => $record->status === 'Approved'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.lock')))
                ->action(function () use ($record) {
                    $record->lock();
                    Notification::make()->title('Diary locked')->success()->send();
                }),

            Actions\Action::make('printDiary')
                ->label('Print Diary')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('site_diaries.export'))
                ->action(fn () => $this->streamDiaryPdf()),
        ];
    }

    protected function streamDiaryPdf()
    {
        $record = $this->record->load(['site', 'project', 'loggedBy', 'activities', 'delays', 'diaryVisitors']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.diary-daily', [
            'title' => 'Daily Site Diary — ' . $record->diary_number,
            'date' => now()->format('d M Y'),
            'diaries' => collect([$record]),
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'daily-diary-' . $record->diary_number . '.pdf');
    }

    public function getActivities()
    {
        return $this->record->activities;
    }

    public function getDelays()
    {
        return $this->record->delays;
    }

    public function getDiaryVisitors()
    {
        return $this->record->diaryVisitors;
    }

    public function getPhotos()
    {
        return $this->record->photos()->latest()->get();
    }

    public function getDocuments()
    {
        return Document::query()
            ->where('daily_site_diary_id', $this->record->id)
            ->where(function ($q) {
                $q->where('category', '!=', 'Site Photos')->orWhereNull('category');
            })
            ->latest()
            ->get();
    }
}
