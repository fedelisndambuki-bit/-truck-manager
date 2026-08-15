<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Document;
use App\Models\Staff;
use App\Models\SiteZone;
use App\Models\Visitor;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.view-site';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\EditAction::make(),

            Actions\Action::make('assignTeam')
                ->label('Assign Team')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('assignResources', $record))
                ->fillForm(fn () => SiteResource::getTeamFormStateForProject($record))
                ->form(SiteResource::teamSchema())
                ->action(function (array $data) use ($record) {
                    SiteResource::syncTeamFromFormState($record, $data['team'] ?? []);
                    Notification::make()->title('Site team updated')->success()->send();
                }),

            Actions\Action::make('suspend')
                ->label(fn () => $record->status === 'Suspended' ? 'Resume' : 'Suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn () => ! in_array($record->status, ['Completed', 'Closed'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('update', $record)))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['status' => $record->status === 'Suspended' ? 'Active' : 'Suspended']);
                    Notification::make()->title('Site status updated')->success()->send();
                }),

            Actions\Action::make('close')
                ->label('Close Site')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => ! in_array($record->status, ['Completed', 'Closed'])
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('approve', $record)))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['status' => 'Closed', 'progress_percent' => 100]);
                    Notification::make()->title('Site closed')->success()->send();
                }),

            Actions\Action::make('printSummary')
                ->label('Print Summary')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('manageReports', $record))
                ->action(fn () => $this->streamSitePdf()),
        ];
    }

    protected function streamSitePdf()
    {
        $record = $this->record;

        $pdf = Pdf::loadView('reports.site-summary', [
            'title' => 'Site Summary — ' . $record->name,
            'date' => now()->format('d M Y'),
            'site' => $record,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'site-summary-' . $record->site_code . '.pdf');
    }

    public function getLabourers()
    {
        return $this->record->labourers()->with('employee')->get();
    }

    public function getAttendanceToday()
    {
        return $this->record->attendances()->whereDate('date', today())->with('employee')->get();
    }

    public function getEquipmentAllocations()
    {
        return $this->record->equipmentAllocations()->with('equipment')->get();
    }

    public function getMaterialDeliveries()
    {
        return $this->record->materialDeliveries()->with(['material', 'receivedBy'])->latest('delivered_at')->get();
    }

    public function getDiaries()
    {
        return $this->record->diaries()->latest('date')->get();
    }

    public function getVisitors()
    {
        return $this->record->visitors()->with('host')->latest('check_in_at')->get();
    }

    public function getSubcontractors()
    {
        return $this->record->subcontractors()->get();
    }

    public function getSafetyIncidents()
    {
        return $this->record->incidents()->latest('date')->get();
    }

    public function getQualityInspections()
    {
        return $this->record->qualityInspections()->latest('date')->get();
    }

    public function getSiteInspections()
    {
        return $this->record->inspections()->with('inspector')->latest('date')->get();
    }

    public function getDocuments()
    {
        return Document::query()->where('site_id', $this->record->id)->latest()->get();
    }

    public function getPhotos()
    {
        return Document::query()->where('site_id', $this->record->id)->where('category', 'Site Photos')->latest()->get();
    }

    public function getZones()
    {
        return $this->record->zones()->get();
    }

    public function getLatestWeather(): ?string
    {
        return $this->record->diaries()->latest('date')->value('weather');
    }

    public function checkInVisitorAction(): Actions\Action
    {
        return Actions\Action::make('checkInVisitor')
            ->label('Check In Visitor')
            ->icon('heroicon-o-user-plus')
            ->form([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('company_name')->label('Company'),
                Forms\Components\TextInput::make('purpose'),
                Forms\Components\Select::make('host_employee_id')
                    ->label('Host')
                    ->options(fn () => Staff::query()->pluck('full_name', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('vehicle'),
                Forms\Components\TextInput::make('badge_number'),
            ])
            ->action(function (array $data) {
                Visitor::create([
                    ...$data,
                    'company_id' => $this->record->company_id,
                    'site_id' => $this->record->id,
                    'check_in_at' => now(),
                ]);
                Notification::make()->title('Visitor checked in')->success()->send();
            });
    }

    public function checkOutVisitor(int $visitorId): void
    {
        $visitor = Visitor::query()->where('site_id', $this->record->id)->findOrFail($visitorId);
        $visitor->update(['check_out_at' => now()]);

        Notification::make()->title('Visitor checked out')->success()->send();
    }

    public function addZoneAction(): Actions\Action
    {
        return Actions\Action::make('addZone')
            ->label('Add Zone')
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('type')->options([
                    'Building' => 'Building',
                    'Restricted Area' => 'Restricted Area',
                    'Storage Yard' => 'Storage Yard',
                    'Equipment Parking' => 'Equipment Parking',
                    'Material Storage' => 'Material Storage',
                    'Emergency Assembly' => 'Emergency Assembly',
                    'Office' => 'Office',
                    'Entry Gate' => 'Entry Gate',
                    'Exit Gate' => 'Exit Gate',
                ])->required(),
                Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric()->required(),
                Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric()->required(),
            ])
            ->action(function (array $data) {
                SiteZone::create([
                    ...$data,
                    'company_id' => $this->record->company_id,
                    'site_id' => $this->record->id,
                ]);
                Notification::make()->title('Zone added')->success()->send();
            });
    }
}
