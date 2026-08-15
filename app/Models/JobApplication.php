<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends BaseModel
{
    protected $guarded = [];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'vacancy_id');
    }

    public function hiredStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hired_staff_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id');
    }

    /**
     * Hiring bridges Recruitment into HR: create the Staff record from the
     * candidate's application details, link it back, and close out the
     * vacancy's opening count.
     */
    public function hire(array $additionalStaffData = []): Staff
    {
        $staff = Staff::create(array_merge([
            'company_id' => $this->company_id,
            'full_name' => $this->candidate_name,
            'email' => $this->email,
            'phone_number' => $this->phone,
            'department_id' => $this->vacancy?->department_id,
            'position_id' => $this->vacancy?->position_id,
            'date_hired' => now(),
            'status' => 'Active',
        ], $additionalStaffData));

        $this->update([
            'status' => 'Hired',
            'hired_staff_id' => $staff->id,
        ]);

        return $staff;
    }
}
