<?php

namespace App\Filament\Resources\EquipmentResource\Pages;

use App\Filament\Resources\EquipmentResource;
use App\Models\Staff;
use App\Models\EquipmentInspection;
use App\Models\Maintenance;
use App\Models\Site;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewEquipment extends ViewRecord
{
    protected static string $resource = EquipmentResource::class;

    protected static string $view = 'filament.resources.equipment-resource.pages.view-equipment';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\EditAction::make(),

            Actions\Action::make('returnEquipment')
                ->label('Return')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $record->currentAllocation() && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('transfer', $record)))
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $allocation = $record->currentAllocation();
                    $allocation?->update(['actual_return_date' => now()]);
                    $record->update(['status' => 'Available']);
                    Notification::make()->title('Equipment returned')->success()->send();
                }),

            Actions\Action::make('scheduleMaintenance')
                ->label('Schedule Maintenance')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray')
                ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('manage', $record))
                ->form([
                    Forms\Components\Select::make('maintenance_type')->options([
                        'Service' => 'Service',
                        'Repair' => 'Repair',
                        'Inspection' => 'Inspection',
                    ])->required(),
                    Forms\Components\Textarea::make('description'),
                    Forms\Components\TextInput::make('service_provider'),
                    Forms\Components\TextInput::make('cost')->numeric(),
                    Forms\Components\DatePicker::make('maintenance_date')->required()->default(now()),
                    Forms\Components\DatePicker::make('next_maintenance_date'),
                ])
                ->action(function (array $data) use ($record) {
                    Maintenance::create([
                        ...$data,
                        'company_id' => $record->company_id,
                        'equipment_id' => $record->id,
                        'status' => 'Pending',
                    ]);
                    $record->update(['status' => 'Under Maintenance']);
                    Notification::make()->title('Maintenance scheduled')->success()->send();
                }),

            Actions\Action::make('generateReport')
                ->label('Generate Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->action(fn () => $this->streamEquipmentPdf()),
        ];
    }

    protected function streamEquipmentPdf()
    {
        $record = $this->record;

        $pdf = Pdf::loadView('reports.equipment-summary', [
            'title' => 'Equipment Summary — ' . $record->name,
            'date' => now()->format('d M Y'),
            'equipment' => $record,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'equipment-summary-' . $record->equipment_code . '.pdf');
    }

    public function getAllocationHistory()
    {
        return $this->record->allocations()->with(['project', 'site', 'operator'])->latest('allocated_from')->get();
    }

    public function getFuelLogs()
    {
        return $this->record->fuelLogs()->with('driver')->latest('date')->get();
    }

    public function getMaintenanceRecords()
    {
        return $this->record->maintenances()->latest('maintenance_date')->get();
    }

    public function getTyres()
    {
        return $this->record->tyres()->get();
    }

    public function getOperators()
    {
        return $this->record->allocations()->with('operator')->whereNotNull('operator_employee_id')->latest('allocated_from')->get();
    }

    public function getInspections()
    {
        return $this->record->inspections()->with('inspector')->latest('date')->get();
    }

    public function getDocuments()
    {
        return $this->record->documents()->latest()->get();
    }

    public function getCurrentSite(): ?Site
    {
        return $this->record->currentAllocation()?->site;
    }

    public function addInspectionAction(): Actions\Action
    {
        return Actions\Action::make('addInspection')
            ->label('Log Inspection')
            ->icon('heroicon-o-clipboard-document-check')
            ->form([
                Forms\Components\Select::make('type')->options([
                    'Daily' => 'Daily Equipment Inspection',
                    'Pre-start' => 'Pre-start Checklist',
                    'Post-operation' => 'Post-operation Checklist',
                ])->default('Daily')->required(),
                Forms\Components\DatePicker::make('date')->default(now())->required(),
                Forms\Components\Select::make('inspector_id')
                    ->label('Inspector')
                    ->options(fn () => Staff::query()->pluck('full_name', 'id'))
                    ->searchable(),
                Forms\Components\Textarea::make('defects'),
                Forms\Components\Toggle::make('supervisor_approved')->label('Supervisor Approved'),
                Forms\Components\Textarea::make('notes'),
            ])
            ->action(function (array $data) {
                EquipmentInspection::create([
                    ...$data,
                    'company_id' => $this->record->company_id,
                    'equipment_id' => $this->record->id,
                ]);
                Notification::make()->title('Inspection logged')->success()->send();
            });
    }
}
