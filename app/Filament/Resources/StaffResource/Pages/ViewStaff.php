<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\Attendance;
use App\Models\Certification;
use App\Models\Document;
use App\Models\EquipmentAllocation;
use App\Models\Labourer;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PerformanceReview;
use App\Models\PpeIssuance;
use App\Models\ProjectTeamMember;
use App\Models\SafetyIncident;
use App\Models\SiteTeamMember;
use App\Models\TrainingRecord;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected static string $view = 'filament.resources.staff-resource.pages.view-staff';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getLabourerAssignments()
    {
        return Labourer::query()->where('employee_id', $this->record->id)->with('site')->get();
    }

    public function getProjectTeamMemberships()
    {
        return ProjectTeamMember::query()->where('employee_id', $this->record->id)->with('project')->get();
    }

    public function getSiteTeamMemberships()
    {
        return SiteTeamMember::query()->where('employee_id', $this->record->id)->with('site')->get();
    }

    public function getEquipmentOperations()
    {
        return EquipmentAllocation::query()
            ->where('operator_employee_id', $this->record->id)
            ->orWhere('backup_operator_employee_id', $this->record->id)
            ->orWhere('supervisor_employee_id', $this->record->id)
            ->with(['equipment', 'site'])
            ->latest('allocated_from')
            ->get();
    }

    public function getReportedIncidents()
    {
        return SafetyIncident::query()->where('reported_by_id', $this->record->id)->with('site')->latest('date')->get();
    }

    public function getAttendances()
    {
        return Attendance::query()->where('employee_id', $this->record->id)->with('site')->latest('date')->limit(30)->get();
    }

    public function getLeaveRequests()
    {
        return LeaveRequest::query()->where('staff_id', $this->record->id)->with('leaveType')->latest('start_date')->get();
    }

    public function getLeaveBalances()
    {
        return $this->record->leaveBalances()->with('leaveType')->where('year', now()->year)->get();
    }

    public function getPayslips()
    {
        return Payslip::query()->where('staff_id', $this->record->id)->with('payrollPeriod')->latest('created_at')->limit(12)->get();
    }

    public function getPerformanceReviews()
    {
        return PerformanceReview::query()->where('staff_id', $this->record->id)->latest('date')->get();
    }

    public function getTrainingRecords()
    {
        return TrainingRecord::query()->where('staff_id', $this->record->id)->latest('date')->get();
    }

    public function getCertifications()
    {
        return Certification::query()->where('staff_id', $this->record->id)->orderBy('expiry_date')->get();
    }

    public function getPpeIssuances()
    {
        return PpeIssuance::query()->where('staff_id', $this->record->id)->latest('issue_date')->get();
    }

    public function getDocuments()
    {
        return Document::query()->where('staff_id', $this->record->id)->latest()->get();
    }
}
