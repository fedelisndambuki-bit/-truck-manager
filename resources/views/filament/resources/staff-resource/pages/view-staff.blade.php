@php
    use App\Filament\Resources\StaffResource;
    $staff = $record;
    $currency = session('currency', 'KES');
@endphp
<x-filament-panels::page>
    <x-slot name="heading"></x-slot>
    <x-slot name="subheading"></x-slot>
    <x-slot name="header"></x-slot>

    @include('filament.construction.partials.styles')
    @include('filament.construction.partials.header', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
        'title' => $staff->full_name,
        'subtitle' => $staff->staff_number . ' — ' . ($staff->position?->title ?? 'No position') . ($staff->department ? ' · ' . $staff->department->name : ''),
    ])

    <style>
        .tab-bar{display:flex;gap:2px;background:#F1F5F9;border-radius:10px;padding:4px;border:1px solid #E2E8F0;overflow-x:auto;margin-bottom:20px}
        .tab{padding:7px 14px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;color:#64748B;transition:all .15s ease;white-space:nowrap}
        .tab.active{background:#FFFFFF;color:#0F172A;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #E2E8F0}
        .ov-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px}
        .ov-label{font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .ov-value{font-size:20px;font-weight:700;color:#0F172A;margin-top:4px}
    </style>

    <div x-data="{ activeTab: 'overview' }">
        <div class="tab-bar">
            @foreach ([
                'overview' => 'Overview',
                'assignments' => 'Project Assignments',
                'equipment' => 'Equipment',
                'safety' => 'Safety',
                'attendance' => 'Attendance',
                'leave' => 'Leave',
                'payroll' => 'Payroll',
                'performance' => 'Performance',
                'training' => 'Training',
                'certifications' => 'Certifications',
                'ppe' => 'PPE',
                'documents' => 'Documents',
            ] as $key => $label)
                <div class="tab" :class="{ 'active': activeTab === '{{ $key }}' }" @click="activeTab = '{{ $key }}'">{{ $label }}</div>
            @endforeach
        </div>

        {{-- OVERVIEW --}}
        <div x-show="activeTab === 'overview'">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <div class="ov-card">
                    <div class="ov-label">Status</div>
                    <div class="mt-1"><x-filament::badge :color="\App\Filament\Resources\StaffResource::getStatusColor($staff->status)">{{ $staff->status }}</x-filament::badge></div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Employment Type</div>
                    <div class="ov-value">{{ $staff->employment_type }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Date Hired</div>
                    <div class="ov-value">{{ $staff->date_hired?->format('d M Y') ?? '—' }}</div>
                </div>
                <div class="ov-card">
                    <div class="ov-label">Supervisor</div>
                    <div class="ov-value">{{ $staff->supervisor?->full_name ?? '—' }}</div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Contact Details</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $staff->phone_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $staff->email ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">County</dt><dd>{{ $staff->county ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">National ID</dt><dd>{{ $staff->national_id ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Statutory & Bank</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">KRA PIN</dt><dd>{{ $staff->kra_pin ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">NSSF No.</dt><dd>{{ $staff->nssf_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">SHA No.</dt><dd>{{ $staff->sha_number ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Bank</dt><dd>{{ $staff->bank_name ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Compensation</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Basic Salary</dt><dd>{{ $currency }} {{ number_format($staff->basic_salary ?? 0, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Allowances</dt><dd>{{ $currency }} {{ number_format($staff->allowances ?? 0, 2) }}</dd></div>
                    </dl>
                </x-filament::section>
                <x-filament::section>
                    <x-slot name="heading">Emergency Contact</x-slot>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd>{{ $staff->emergency_contact_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $staff->emergency_contact_phone ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Next of Kin</dt><dd>{{ $staff->next_of_kin_name ?? '—' }}</dd></div>
                    </dl>
                </x-filament::section>
            </div>
        </div>

        {{-- PROJECT ASSIGNMENTS --}}
        <div x-show="activeTab === 'assignments'">
            <x-filament::section>
                <x-slot name="heading">Site Labour Assignments</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Site</th><th>Role</th><th>Daily Rate</th></tr></thead>
                    <tbody>
                        @forelse ($this->getLabourerAssignments() as $assignment)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $assignment->site?->name ?? '—' }}</td>
                                <td>{{ $assignment->role_on_site }}</td>
                                <td>{{ $currency }} {{ number_format($assignment->daily_rate ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-gray-400">No site labour assignments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>

            <x-filament::section class="mt-4">
                <x-slot name="heading">Project Team Roles</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Project</th><th>Role Slot</th></tr></thead>
                    <tbody>
                        @forelse ($this->getProjectTeamMemberships() as $membership)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $membership->project?->name ?? '—' }}</td>
                                <td>{{ $membership->role_slot }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-gray-400">No project team roles</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>

            <x-filament::section class="mt-4">
                <x-slot name="heading">Site Team Roles</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Site</th><th>Role Slot</th></tr></thead>
                    <tbody>
                        @forelse ($this->getSiteTeamMemberships() as $membership)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $membership->site?->name ?? '—' }}</td>
                                <td>{{ $membership->role_slot }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-gray-400">No site team roles</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- EQUIPMENT --}}
        <div x-show="activeTab === 'equipment'">
            <x-filament::section>
                <x-slot name="heading">Equipment Operated / Supervised</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Equipment</th><th>Site</th><th>From</th><th>To</th><th>Role</th></tr></thead>
                    <tbody>
                        @forelse ($this->getEquipmentOperations() as $allocation)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $allocation->equipment?->name ?? '—' }}</td>
                                <td>{{ $allocation->site?->name ?? '—' }}</td>
                                <td>{{ $allocation->allocated_from?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $allocation->allocated_to?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if ($allocation->operator_employee_id === $staff->id) Operator
                                    @elseif ($allocation->backup_operator_employee_id === $staff->id) Backup Operator
                                    @elseif ($allocation->supervisor_employee_id === $staff->id) Supervisor
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No equipment allocations</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- SAFETY --}}
        <div x-show="activeTab === 'safety'">
            <x-filament::section>
                <x-slot name="heading">Incidents Reported</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Site</th><th>Severity</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getReportedIncidents() as $incident)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $incident->date?->format('d M Y') }}</td>
                                <td>{{ $incident->site?->name ?? '—' }}</td>
                                <td>{{ $incident->severity }}</td>
                                <td>{{ $incident->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No incidents reported</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- ATTENDANCE --}}
        <div x-show="activeTab === 'attendance'">
            <x-filament::section>
                <x-slot name="heading">Recent Attendance</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Site</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Late</th></tr></thead>
                    <tbody>
                        @forelse ($this->getAttendances() as $attendance)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $attendance->date->format('d M Y') }}</td>
                                <td>{{ $attendance->site?->name ?? '—' }}</td>
                                <td>{{ $attendance->status }}</td>
                                <td>{{ $attendance->check_in_at?->format('H:i') ?? '—' }}</td>
                                <td>{{ $attendance->check_out_at?->format('H:i') ?? '—' }}</td>
                                <td>{{ $attendance->late ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4 text-center text-gray-400">No attendance records</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- LEAVE --}}
        <div x-show="activeTab === 'leave'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-4">
                @forelse ($this->getLeaveBalances() as $balance)
                    <div class="ov-card">
                        <div class="ov-label">{{ $balance->leaveType->name }} ({{ $balance->year }})</div>
                        <div class="ov-value">{{ $balance->remaining_days }} / {{ $balance->allocated_days }} days</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-400">No leave balances recorded for {{ now()->year }}</div>
                @endforelse
            </div>
            <x-filament::section>
                <x-slot name="heading">Leave Requests</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getLeaveRequests() as $leave)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $leave->leaveType->name }}</td>
                                <td>{{ $leave->start_date->format('d M Y') }}</td>
                                <td>{{ $leave->end_date->format('d M Y') }}</td>
                                <td>{{ $leave->days }}</td>
                                <td>{{ $leave->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No leave requests</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PAYROLL --}}
        <div x-show="activeTab === 'payroll'">
            <x-filament::section>
                <x-slot name="heading">Payslip History</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Period</th><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPayslips() as $payslip)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ \App\Filament\Resources\PayrollPeriodResource::getMonthOptions()[$payslip->payrollPeriod->month] }} {{ $payslip->payrollPeriod->year }}</td>
                                <td>{{ $currency }} {{ number_format($payslip->gross_pay, 2) }}</td>
                                <td>{{ $currency }} {{ number_format($payslip->total_deductions, 2) }}</td>
                                <td class="font-semibold">{{ $currency }} {{ number_format($payslip->net_pay, 2) }}</td>
                                <td>{{ $payslip->payment_status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No payslips generated yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PERFORMANCE --}}
        <div x-show="activeTab === 'performance'">
            <x-filament::section>
                <x-slot name="heading">Performance Reviews</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Date</th><th>Period</th><th>Rating</th><th>Reviewer</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPerformanceReviews() as $review)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $review->date->format('d M Y') }}</td>
                                <td>{{ $review->period_type }}</td>
                                <td>{{ $review->rating ?? '—' }} / 5</td>
                                <td>{{ $review->reviewer?->full_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No performance reviews</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- TRAINING --}}
        <div x-show="activeTab === 'training'">
            <x-filament::section>
                <x-slot name="heading">Training Records</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Type</th><th>Date</th><th>Expiry</th><th>Provider</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getTrainingRecords() as $training)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $training->training_type }}</td>
                                <td>{{ $training->date->format('d M Y') }}</td>
                                <td>{{ $training->expiry_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $training->provider ?? '—' }}</td>
                                <td>{{ $training->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No training records</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- CERTIFICATIONS --}}
        <div x-show="activeTab === 'certifications'">
            <x-filament::section>
                <x-slot name="heading">Skills & Certifications</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Name</th><th>Category</th><th>Issuing Body</th><th>Expiry</th></tr></thead>
                    <tbody>
                        @forelse ($this->getCertifications() as $cert)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $cert->name }}</td>
                                <td>{{ $cert->category }}</td>
                                <td>{{ $cert->issuing_body ?? '—' }}</td>
                                <td>{{ $cert->expiry_date?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No certifications on file</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- PPE --}}
        <div x-show="activeTab === 'ppe'">
            <x-filament::section>
                <x-slot name="heading">PPE Issued</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Item</th><th>Size</th><th>Issued</th><th>Replacement Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($this->getPpeIssuances() as $ppe)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $ppe->item }}</td>
                                <td>{{ $ppe->size ?? '—' }}</td>
                                <td>{{ $ppe->issue_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $ppe->replacement_due_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $ppe->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400">No PPE issued</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>

        {{-- DOCUMENTS --}}
        <div x-show="activeTab === 'documents'">
            <x-filament::section>
                <x-slot name="heading">Documents</x-slot>
                <table class="fi-ta-table w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="py-2">Title</th><th>Category</th><th>Uploaded</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($this->getDocuments() as $document)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2">{{ $document->title }}</td>
                                <td>{{ $document->category }}</td>
                                <td>{{ $document->created_at->format('d M Y') }}</td>
                                <td>
                                    @if ($document->file_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" class="text-primary-600 hover:underline text-xs font-semibold">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-400">No documents on file</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
