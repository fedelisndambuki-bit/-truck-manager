<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Models\LoadingInstruction;
use App\Models\Staff;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Each loader operator's dedicated dashboard — current/waiting/completed
 * jobs + quick actions. Responsive (Tailwind breakpoints) so it also serves
 * as the "Loader Mobile Terminal" on tablets/phones, per the session's
 * confirmed scoping (no separate native app).
 */
class LoaderDashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?string $navigationLabel = 'Loader Dashboard';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Loader Dashboard';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.loader-dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />',
            'title' => 'Loader Dashboard',
            'subtitle' => 'Your current, waiting and completed loading jobs.',
        ]);
    }

    protected function loaderStaff(): ?Staff
    {
        $email = Auth::user()?->email;

        return $email ? Staff::withoutGlobalScope('company')->where('email', $email)->first() : null;
    }

    public function getCurrentJobs()
    {
        $staff = $this->loaderStaff();

        if (! $staff) {
            return collect();
        }

        return LoadingInstruction::query()
            ->where('loader_operator_id', $staff->id)
            ->whereIn('status', ['Accepted', 'Loading', 'Paused'])
            ->with(['dispatchOrder', 'stockpile', 'loadingBay'])
            ->latest('id')
            ->get();
    }

    public function getWaitingJobs()
    {
        $staff = $this->loaderStaff();

        if (! $staff) {
            return collect();
        }

        return LoadingInstruction::query()
            ->where('loader_operator_id', $staff->id)
            ->where('status', 'Waiting')
            ->with(['dispatchOrder', 'stockpile', 'loadingBay'])
            ->latest('id')
            ->get();
    }

    public function getCompletedJobs()
    {
        $staff = $this->loaderStaff();

        if (! $staff) {
            return collect();
        }

        return LoadingInstruction::query()
            ->where('loader_operator_id', $staff->id)
            ->where('status', 'Completed')
            ->with(['dispatchOrder', 'stockpile', 'loadingBay'])
            ->latest('loading_completed_at')
            ->limit(10)
            ->get();
    }

    public function acceptJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->accept();
        Notification::make()->title('Job accepted')->success()->send();
    }

    public function startJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->startLoading();
        Notification::make()->title('Loading started')->success()->send();
    }

    public function pauseJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->pause();
        Notification::make()->title('Loading paused')->success()->send();
    }

    public function resumeJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->resume();
        Notification::make()->title('Loading resumed')->success()->send();
    }

    public function completeJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->complete();
        Notification::make()->title('Loading completed — truck moved to Awaiting Gross Weight')->success()->send();
    }

    public function rejectJob(int $id): void
    {
        LoadingInstruction::findOrFail($id)->reject('Rejected by loader operator');
        Notification::make()->title('Job rejected')->danger()->send();
    }
}
