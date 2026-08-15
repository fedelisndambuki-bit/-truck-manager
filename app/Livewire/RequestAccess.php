<?php

namespace App\Livewire;

use App\Models\PlatformSetting;
use App\Services\AccessRequestApprovalService;
use App\Services\CaptchaVerificationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Without an explicit layout, Livewire wraps this in the app-wide default
 * layout (config/livewire.php), which is Filament's own shell — this page
 * already IS a complete document, so that produced two nested <html>
 * documents in one response (browsers silently merge them, but the wrong
 * <title>/wrong assets "win"). Same class of pre-existing issue on
 * /portal/invitations/{token}, left alone since it's out of scope here.
 */
#[Layout('components.layouts.tanaki-auth', ['title' => 'Request Access'])]
class RequestAccess extends Component
{
    use WithRateLimiting;

    public string $requester_name = '';
    public string $requester_email = '';
    public string $requester_phone = '';
    public string $organization_name = '';
    public string $organization_country = '';
    public string $organization_type = '';
    public string $organization_size = '';

    /** @var array<int, string> */
    public array $operations_of_interest = [];

    public string $operation_description = '';
    public bool $terms = false;
    public string $captcha_token = '';

    public bool $submitted = false;
    public ?string $errorMessage = null;

    const COUNTRIES = ['Kenya', 'Uganda', 'Tanzania', 'Rwanda', 'Nigeria', 'South Africa', 'Ghana', 'Ethiopia', 'Zambia', 'Other'];

    const COMPANY_TYPES = ['Fleet & Transport', 'Construction', 'Quarry', 'Workshop', 'Multi-Operation', 'Other'];

    const OPERATION_SIZES = ['1–10', '11–50', '51–100', '101–250', '251–500', '500+'];

    const OPERATIONS = [
        'Fleet Management', 'Construction Management', 'Quarry Operations',
        'Workshop Management', 'Weighbridge', 'Fuel Management',
        'Finance & Accounting', 'HR & Payroll', 'Procurement', 'Analytics & AI',
    ];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(filament()->getUrl(), navigate: false);

            return;
        }

        if (! PlatformSetting::getSetting('enable_registration', true)) {
            abort(403, 'New access requests are currently closed. Please contact support.');
        }
    }

    public function submit(CaptchaVerificationService $captcha, AccessRequestApprovalService $service): void
    {
        $this->errorMessage = null;

        try {
            $this->rateLimit(3, decaySeconds: 3600);
        } catch (TooManyRequestsException $exception) {
            $this->errorMessage = 'Too many attempts. Please try again in ' . $exception->secondsUntilAvailable . ' seconds.';

            return;
        }

        $this->validate([
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
            'requester_phone' => 'required|string|max:30',
            'organization_name' => 'required|string|max:255',
            'organization_country' => 'required|string',
            'organization_type' => 'required|string',
            'organization_size' => 'nullable|string',
            'operations_of_interest' => 'nullable|array',
            'operation_description' => 'nullable|string|max:2000',
            'terms' => 'accepted',
        ]);

        if (! $captcha->verify($this->captcha_token ?: null)) {
            $this->errorMessage = 'We couldn\'t verify you\'re not a robot. Please try again.';

            return;
        }

        // Per-email limit in addition to the per-session one above — stops
        // someone cycling browser sessions to spam the same address.
        $emailKey = 'access-request-email:' . strtolower(trim($this->requester_email));

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($emailKey, 3)) {
            $this->errorMessage = 'Too many requests for this email address. Please try again later.';

            return;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($emailKey, 3600);

        try {
            $service->submit([
                'requester_name' => $this->requester_name,
                'requester_email' => $this->requester_email,
                'requester_phone' => $this->requester_phone,
                'organization_name' => $this->organization_name,
                'organization_country' => $this->organization_country,
                'organization_type' => $this->organization_type,
                'organization_size' => $this->organization_size,
                'operations_of_interest' => $this->operations_of_interest,
                'operation_description' => $this->operation_description,
                'terms_policy_version' => 'v1',
                'captcha_verified' => $captcha->isEnabled(),
            ]);

            $this->submitted = true;
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.request-access');
    }
}
