<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ProjectUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected array $pendingAssignment = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically scope new users to the creating admin's company.
        // Super Admin can still create users but they also get scoped to company_id=1
        // unless explicitly overridden in the SuperAdmin panel.
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()->company_id;
        }

        // The "External Access" section's fields aren't real users columns —
        // they populate the user's first ProjectUser row after creation.
        $this->pendingAssignment = [
            'project_id' => $data['assignment_project_id'] ?? null,
            'end_date' => $data['assignment_expiry_date'] ?? null,
            'can_view_drawings' => $data['assignment_can_view_drawings'] ?? true,
            'can_download_documents' => $data['assignment_can_download_documents'] ?? true,
            'can_upload_documents' => $data['assignment_can_upload_documents'] ?? false,
            'can_comment' => $data['assignment_can_comment'] ?? true,
            'can_approve' => $data['assignment_can_approve'] ?? false,
            'can_view_financials' => $data['assignment_can_view_financials'] ?? false,
        ];

        unset(
            $data['assignment_project_id'], $data['assignment_expiry_date'],
            $data['assignment_can_view_drawings'], $data['assignment_can_download_documents'],
            $data['assignment_can_upload_documents'], $data['assignment_can_comment'],
            $data['assignment_can_approve'], $data['assignment_can_view_financials'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->user_type !== 'external' || empty($this->pendingAssignment['project_id'])) {
            return;
        }

        ProjectUser::create(array_merge($this->pendingAssignment, [
            'company_id' => $this->record->company_id,
            'user_id' => $this->record->id,
            'role' => $this->record->roles()->first()?->name,
            'status' => 'Active',
            'start_date' => now(),
            'invited_by_id' => Auth::id(),
            'invited_at' => now(),
        ]));
    }
}
