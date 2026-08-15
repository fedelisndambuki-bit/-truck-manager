<?php

namespace App\Services;

use App\Exceptions\AccessRequestStateConflictException;
use App\Models\AccessRequest;
use App\Models\AccessRequestInformationThread;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\AccessRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AccessRequestApprovalService
{
    // ─────────────────────────────────────────────────────────────
    // Submission
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     * @throws \RuntimeException on hard-blocked duplicate/disposable email
     */
    public function submit(array $data): AccessRequest
    {
        $email = strtolower(trim($data['requester_email']));

        if ($this->isDisposableEmailDomain($email)) {
            throw new \RuntimeException('Please use a permanent work email address to request access.');
        }

        if ($blockReason = $this->checkHardDuplicates($email)) {
            throw new \RuntimeException($blockReason);
        }

        $accessRequest = AccessRequest::create([
            'requester_name'          => $data['requester_name'],
            'requester_email'         => $email,
            'requester_phone'         => $data['requester_phone'] ?? null,
            'organization_name'       => trim($data['organization_name']),
            'organization_country'    => $data['organization_country'] ?? null,
            'organization_type'       => $data['organization_type'] ?? null,
            'organization_size'       => $data['organization_size'] ?? null,
            'operations_of_interest'  => $data['operations_of_interest'] ?? [],
            'operation_description'   => $data['operation_description'] ?? null,
            'status'                  => AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION,
            'terms_accepted_at'       => now(),
            'terms_policy_version'    => $data['terms_policy_version'] ?? 'v1',
            'domain_is_risky'         => $this->isRiskyFreeEmailDomain($email),
            'domain_risk_reason'      => $this->isRiskyFreeEmailDomain($email) ? 'Free/consumer email provider' : null,
            'captcha_verified'        => $data['captcha_verified'] ?? false,
            'email_verification_token' => Str::random(64),
            'email_verification_expires_at' => now()->addHours(48),
        ]);

        $this->notifyApplicant($accessRequest, 'verify_email', [
            'verification_url' => URL::temporarySignedRoute(
                'access-request.verify',
                now()->addHours(48),
                ['token' => $accessRequest->email_verification_token],
            ),
        ]);

        $this->logEvent($accessRequest, 'access_request_created', 'Access request submitted');

        return $accessRequest;
    }

    public function verifyEmail(string $token): AccessRequest
    {
        $accessRequest = AccessRequest::where('email_verification_token', $token)->firstOrFail();

        if ($accessRequest->email_verification_expires_at?->isPast()) {
            throw new \RuntimeException('This verification link has expired. Please submit a new request.');
        }

        if ($accessRequest->status !== AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION) {
            // Already verified (or moved on) — idempotent, just return as-is.
            return $accessRequest;
        }

        // The token is deliberately NOT cleared here — it doubles as the
        // applicant's standing access token for the public status/respond
        // page (see routes/web.php 'access-request.status') for the rest of
        // this request's lifecycle, since no User/session exists yet for
        // them to authenticate with.
        $this->guardedTransition(
            $accessRequest->id,
            AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION,
            [
                'status' => AccessRequest::STATUS_EMAIL_VERIFIED,
                'email_verified_at' => now(),
            ],
        );

        // Auto-advance into the review queue — verification alone doesn't
        // need a separate human step.
        $this->guardedTransition(
            $accessRequest->id,
            AccessRequest::STATUS_EMAIL_VERIFIED,
            ['status' => AccessRequest::STATUS_PENDING_REVIEW],
        );

        $accessRequest->refresh();

        $this->notifyApplicant($accessRequest, 'request_received');
        $this->notifySuperAdmins('new_access_request', $accessRequest);
        $this->logEvent($accessRequest, 'access_request_email_verified', 'Email verified, entered review queue');

        return $accessRequest;
    }

    /**
     * Re-send the verification email for a request still awaiting it,
     * issuing a fresh token/expiry so a stale or already-clicked link
     * can't be confused with the new one.
     */
    public function resendVerification(int $accessRequestId, User $admin): AccessRequest
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);

        $this->guardedTransition(
            $accessRequestId,
            AccessRequest::STATUS_PENDING_EMAIL_VERIFICATION,
            [
                'email_verification_token' => Str::random(64),
                'email_verification_expires_at' => now()->addHours(48),
            ],
        );

        $accessRequest->refresh();

        $this->notifyApplicant($accessRequest, 'verify_email', [
            'verification_url' => URL::temporarySignedRoute(
                'access-request.verify',
                $accessRequest->email_verification_expires_at,
                ['token' => $accessRequest->email_verification_token],
            ),
        ]);

        $this->logEvent($accessRequest, 'access_request_verification_resent', "Verification email resent by {$admin->name}");

        return $accessRequest;
    }

    // ─────────────────────────────────────────────────────────────
    // Review
    // ─────────────────────────────────────────────────────────────

    public function startReview(int $accessRequestId, User $reviewer): AccessRequest
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);

        $this->guardedTransition($accessRequestId, AccessRequest::STATUS_PENDING_REVIEW, [
            'status' => AccessRequest::STATUS_IN_REVIEW,
            'reviewed_by' => $reviewer->id,
        ]);

        $this->logEvent($accessRequest, 'access_request_review_started', "Review started by {$reviewer->name}");

        return $accessRequest->refresh();
    }

    public function requestMoreInformation(int $accessRequestId, User $reviewer, string $message): AccessRequestInformationThread
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);

        $this->guardedTransition($accessRequestId, AccessRequest::STATUS_IN_REVIEW, [
            'status' => AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
            'reviewed_by' => $reviewer->id,
        ]);

        $thread = AccessRequestInformationThread::create([
            'access_request_id' => $accessRequestId,
            'author_type' => AccessRequestInformationThread::AUTHOR_SUPERADMIN,
            'author_id' => $reviewer->id,
            'message' => $message,
            'created_at' => now(),
        ]);

        $accessRequest->refresh();
        $this->notifyApplicant($accessRequest, 'additional_info_required', [
            'message' => $message,
            'status_url' => route('access-request.status', ['token' => $accessRequest->email_verification_token]),
        ]);
        $this->logEvent($accessRequest, 'access_request_info_requested', "More info requested by {$reviewer->name}");

        return $thread;
    }

    /**
     * The applicant has no session/User to authenticate with pre-approval,
     * so their access token (see verifyEmail()'s note) is how the public
     * status/respond page proves this is really them.
     */
    public function findByAccessToken(string $token): ?AccessRequest
    {
        return AccessRequest::where('email_verification_token', $token)->first();
    }

    public function respondToInformationRequest(int $accessRequestId, string $message): AccessRequestInformationThread
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);

        $this->guardedTransition($accessRequestId, AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED, [
            'status' => AccessRequest::STATUS_IN_REVIEW,
        ]);

        $thread = AccessRequestInformationThread::create([
            'access_request_id' => $accessRequestId,
            'author_type' => AccessRequestInformationThread::AUTHOR_APPLICANT,
            'author_id' => null,
            'message' => $message,
            'created_at' => now(),
        ]);

        $accessRequest->refresh();
        $this->notifySuperAdmins('info_submitted', $accessRequest);
        $this->logEvent($accessRequest, 'access_request_info_submitted', 'Applicant responded to information request');

        return $thread;
    }

    // ─────────────────────────────────────────────────────────────
    // Four-eyes control (Enterprise-tier grants)
    // ─────────────────────────────────────────────────────────────

    public function requiresSecondApproval(int $planId): bool
    {
        $plan = Plan::find($planId);

        return $plan && strtolower($plan->name) === 'enterprise';
    }

    /**
     * Records the first Super Admin's sign-off without finalizing the
     * approval. A different Super Admin must call approve() afterward.
     */
    public function recordFirstApproval(int $accessRequestId, User $reviewer, int $planId): AccessRequest
    {
        $affected = AccessRequest::where('id', $accessRequestId)
            ->where('status', AccessRequest::STATUS_IN_REVIEW)
            ->update([
                'first_approved_by' => $reviewer->id,
                'first_approved_at' => now(),
                'first_approved_plan_id' => $planId,
            ]);

        if ($affected === 0) {
            throw new AccessRequestStateConflictException();
        }

        $accessRequest = AccessRequest::findOrFail($accessRequestId);
        $this->logEvent($accessRequest, 'access_request_first_approval', "First approval (Enterprise) by {$reviewer->name}, awaiting second Super Admin");

        return $accessRequest;
    }

    // ─────────────────────────────────────────────────────────────
    // Approval — the big atomic one
    // ─────────────────────────────────────────────────────────────

    public function approve(int $accessRequestId, User $reviewer, int $planId, string $industry): AccessRequest
    {
        $preCheck = AccessRequest::findOrFail($accessRequestId);

        if ($this->requiresSecondApproval($planId)) {
            if (! $preCheck->first_approved_by) {
                throw new \RuntimeException('Enterprise-tier approvals require a first sign-off before this final approval.');
            }

            if ((int) $preCheck->first_approved_by === (int) $reviewer->id) {
                throw new \RuntimeException('A second, different Super Admin must confirm this Enterprise-tier approval.');
            }
        }

        $accessRequest = DB::transaction(function () use ($accessRequestId, $reviewer, $planId, $industry) {
            $affected = AccessRequest::where('id', $accessRequestId)
                ->where('status', AccessRequest::STATUS_IN_REVIEW)
                ->update([
                    'status' => AccessRequest::STATUS_APPROVED,
                    'reviewed_by' => $reviewer->id,
                    'plan_id' => $planId,
                ]);

            if ($affected === 0) {
                throw new AccessRequestStateConflictException();
            }

            /** @var AccessRequest $accessRequest */
            $accessRequest = AccessRequest::findOrFail($accessRequestId);

            $company = Company::create([
                'name'          => $accessRequest->organization_name,
                'email'         => $accessRequest->requester_email,
                'phone'         => $accessRequest->requester_phone,
                'status'        => 'trial',
                'trial_ends_at' => now()->addDays(7),
                'industry'      => $industry,
                'plan_id'       => $planId,
                'country'       => $accessRequest->organization_country,
                'size'          => $accessRequest->organization_size,
            ]);

            $user = User::create([
                'name'     => $accessRequest->requester_name,
                'email'    => $accessRequest->requester_email,
                'password' => Hash::make(Str::random(40)), // unusable — set via the activation link
                'company_id' => $company->id,
                'terms_accepted_at' => $accessRequest->terms_accepted_at,
                'terms_version' => $accessRequest->terms_policy_version,
            ]);

            // Deliberately not mass-assignable (not in $fillable) — set via
            // forceFill at this one controlled, server-side-only call site
            // rather than opening it up as a mass-assignable field anywhere
            // else a User could be created/updated from user input.
            $user->forceFill(['email_verified_at' => $accessRequest->email_verified_at ?? now()])->save();

            $company->update(['owner_user_id' => $user->id]);

            $guard = config('auth.defaults.guard', 'web');
            $user->assignRole(Role::firstOrCreate(['name' => 'Organization Owner', 'guard_name' => $guard]));
            $user->assignRole(Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => $guard]));

            $accessRequest->update(['user_id' => $user->id, 'company_id' => $company->id]);

            $this->logEvent($accessRequest, 'access_request_approved', "Approved by {$reviewer->name}, organization activated", $company, $user);

            return $accessRequest->fresh();
        });

        // Notification dispatch happens AFTER commit — a mail-transport
        // failure must never roll back a real, already-activated tenant.
        // AccessRequestNotification is ShouldQueue, so a failed send lands
        // in failed_jobs automatically (retryable), not silently lost.
        try {
            $activationUrl = $this->generateActivationUrl($accessRequest->user);

            $accessRequest->user->notify(new AccessRequestNotification('approved', [
                'organization_name' => $accessRequest->organization_name,
                'activation_url' => $activationUrl,
            ]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to dispatch access-request approval notification', [
                'access_request_id' => $accessRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $accessRequest;
    }

    protected function generateActivationUrl(User $user): string
    {
        // Reuses Laravel's own password-broker infrastructure (secure,
        // expiring, single-use token; stock reset-password page already
        // exists in the admin panel) instead of inventing a bespoke
        // signed-URL system for "set your first password."
        $token = Password::broker()->createToken($user);

        return url(route('filament.admin.auth.password-reset.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));
    }

    // ─────────────────────────────────────────────────────────────
    // Reject / Cancel
    // ─────────────────────────────────────────────────────────────

    public function reject(int $accessRequestId, User $reviewer, string $reason): AccessRequest
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);

        $affected = AccessRequest::where('id', $accessRequestId)
            ->whereIn('status', [AccessRequest::STATUS_IN_REVIEW, AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED])
            ->update([
                'status' => AccessRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'rejection_reason' => $reason,
            ]);

        if ($affected === 0) {
            throw new AccessRequestStateConflictException();
        }

        $accessRequest->refresh();
        $this->notifyApplicant($accessRequest, 'rejected', [
            'status_url' => route('access-request.status', ['token' => $accessRequest->email_verification_token]),
        ]);
        $this->logEvent($accessRequest, 'access_request_rejected', "Rejected by {$reviewer->name}: {$reason}");

        return $accessRequest;
    }

    public function cancel(int $accessRequestId, ?string $reason = null): AccessRequest
    {
        $accessRequest = AccessRequest::findOrFail($accessRequestId);
        $currentStatus = $accessRequest->status;

        if (in_array($currentStatus, AccessRequest::TERMINAL_STATUSES, true)) {
            throw new AccessRequestStateConflictException('This request has already reached a final state.');
        }

        $affected = AccessRequest::where('id', $accessRequestId)
            ->where('status', $currentStatus)
            ->update(['status' => AccessRequest::STATUS_CANCELLED]);

        if ($affected === 0) {
            throw new AccessRequestStateConflictException();
        }

        $accessRequest->refresh();
        $this->logEvent($accessRequest, 'access_request_cancelled', $reason ?? 'Cancelled');

        return $accessRequest;
    }

    // ─────────────────────────────────────────────────────────────
    // Concurrency guard
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $updates
     * @throws AccessRequestStateConflictException
     */
    protected function guardedTransition(int $accessRequestId, string $expectedFrom, array $updates): void
    {
        $affected = AccessRequest::where('id', $accessRequestId)
            ->where('status', $expectedFrom)
            ->update($updates);

        if ($affected === 0) {
            throw new AccessRequestStateConflictException();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Duplicate detection & risk signals
    // ─────────────────────────────────────────────────────────────

    protected function checkHardDuplicates(string $email): ?string
    {
        if (User::where('email', $email)->exists()) {
            return 'An account with this email already exists. Please ask your organization owner or admin to invite you, or contact support.';
        }

        if (AccessRequest::nonTerminal()->where('requester_email', $email)->exists()) {
            return 'A request with this email is already pending. Please check your inbox to verify your email, or contact support if you need the link resent.';
        }

        return null;
    }

    /**
     * Soft signal only — surfaced to SuperAdmin at review, never blocks.
     */
    public function findSoftDuplicateCompany(string $organizationName): ?Company
    {
        return Company::whereRaw('LOWER(name) = ?', [Str::lower(trim($organizationName))])->first();
    }

    protected function isDisposableEmailDomain(string $email): bool
    {
        $domain = strtolower(Str::after($email, '@'));

        return in_array($domain, config('disposable_email_domains', []), true);
    }

    protected function isRiskyFreeEmailDomain(string $email): bool
    {
        $domain = strtolower(Str::after($email, '@'));

        return in_array($domain, config('free_email_domains', []), true);
    }

    // ─────────────────────────────────────────────────────────────
    // Notification helpers
    // ─────────────────────────────────────────────────────────────

    protected function notifyApplicant(AccessRequest $accessRequest, string $type, array $data = []): void
    {
        // No User exists pre-approval — send to the raw email via an
        // on-demand notifiable route rather than a model.
        $notifiable = $accessRequest->user ?? Notification::route('mail', $accessRequest->requester_email);

        $notifiable->notify(new AccessRequestNotification($type, array_merge([
            'requester_name' => $accessRequest->requester_name,
            'organization_name' => $accessRequest->organization_name,
        ], $data)));
    }

    protected function notifySuperAdmins(string $type, AccessRequest $accessRequest): void
    {
        $superAdmins = User::role('Super Admin')->get();

        foreach ($superAdmins as $admin) {
            $admin->notify(new AccessRequestNotification($type, [
                'organization_name' => $accessRequest->organization_name,
            ]));
        }
    }

    protected function logEvent(AccessRequest $accessRequest, string $action, string $description, ?Company $company = null, ?User $user = null): void
    {
        \App\Models\SystemLog::create([
            'company_id' => $company?->id ?? $accessRequest->company_id,
            'user_id' => $user?->id ?? $accessRequest->user_id,
            'action' => $action,
            'description' => $description,
            'severity' => 'info',
            'metadata' => [
                'access_request_id' => $accessRequest->id,
                'organization_name' => $accessRequest->organization_name,
                'requester_email' => $accessRequest->requester_email,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
