<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected static string $view = 'filament.resources.payroll-period-resource.pages.view-payroll-period';

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\Action::make('process')
                ->label('Process Payroll')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will generate payslips for every active employee, replacing any existing payslips for this period.')
                ->action(function () use ($record) {
                    $record->process();
                    Notification::make()->title('Payroll processed')->success()->send();
                })
                ->visible(fn () => $record->status !== 'Paid'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.payroll_access'))),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['status' => 'Approved', 'approved_by_id' => Auth::id(), 'approved_at' => now()]);
                    Notification::make()->title('Payroll approved')->success()->send();
                })
                ->visible(fn () => $record->status === 'Processing'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.approve'))),

            Actions\Action::make('markPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () use ($record) {
                    $record->update(['status' => 'Paid']);
                    $record->payslips()->update(['payment_status' => 'Paid']);
                    Notification::make()->title('Payroll marked as paid')->success()->send();
                })
                ->visible(fn () => $record->status === 'Approved'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.payroll_access'))),
        ];
    }

    public function getPayslips()
    {
        return $this->record->payslips()->with('staff')->get();
    }

    public function streamPayslipPdf(int $payslipId)
    {
        $payslip = Payslip::with(['staff', 'payrollPeriod'])->findOrFail($payslipId);

        $pdf = Pdf::loadView('reports.payslip', [
            'title' => 'Payslip — ' . $payslip->staff->full_name,
            'date' => now()->format('d M Y'),
            'payslip' => $payslip,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'payslip-' . $payslip->staff->staff_number . '-' . $payslip->payrollPeriod->month . '-' . $payslip->payrollPeriod->year . '.pdf');
    }
}
