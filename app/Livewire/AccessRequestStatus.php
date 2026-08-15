<?php

namespace App\Livewire;

use App\Models\AccessRequest;
use App\Services\AccessRequestApprovalService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.tanaki-auth', ['title' => 'Request Status'])]
class AccessRequestStatus extends Component
{
    public string $token;
    public ?AccessRequest $accessRequest = null;
    public string $reply = '';
    public bool $invalid = false;
    public bool $justSubmitted = false;

    public function mount(string $token, AccessRequestApprovalService $service): void
    {
        $this->token = $token;
        $this->accessRequest = $service->findByAccessToken($token);

        if (! $this->accessRequest) {
            $this->invalid = true;
        }
    }

    public function submitReply(AccessRequestApprovalService $service): void
    {
        if (! $this->accessRequest || $this->accessRequest->status !== AccessRequest::STATUS_ADDITIONAL_INFORMATION_REQUIRED) {
            return;
        }

        $key = 'access-request-reply:' . $this->token;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('reply', 'Too many attempts. Please try again later.');

            return;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);

        $this->validate(['reply' => 'required|string|max:2000']);

        $service->respondToInformationRequest($this->accessRequest->id, $this->reply);

        $this->accessRequest = $this->accessRequest->fresh();
        $this->reply = '';
        $this->justSubmitted = true;
    }

    public function render()
    {
        return view('livewire.access-request-status');
    }
}
