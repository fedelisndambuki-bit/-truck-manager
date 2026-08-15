<?php

use App\Http\Controllers\Auth\PanelLogoutController;
use App\Http\Controllers\Auth\SocialiteLoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CallController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\SuperAdminImpersonationController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\AnnouncementReadController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('landing', [
        'hero' => \App\Models\LandingHero::where('status', true)->first(),
        'stats' => \App\Models\LandingStat::where('status', true)->orderBy('display_order')->get(),
        'features' => \App\Models\LandingFeature::where('status', true)->orderBy('display_order')->get(),
        'featuresSection' => \App\Models\LandingSection::forKey('features'),
        'partners' => \App\Models\Partner::where('status', true)->orderBy('display_order')->get(),
        'partnersSection' => \App\Models\LandingSection::forKey('partners'),
        'previews' => \App\Models\LandingPreview::orderBy('display_order')->get(),
        'mobileApp' => \App\Models\LandingMobileApp::where('status', true)->first(),
        'pricingSection' => \App\Models\LandingSection::forKey('pricing'),
        'cta' => \App\Models\LandingCta::where('status', true)->first(),
        'navigations' => \App\Models\LandingNavigation::where('status', true)->orderBy('display_order')->get(),
        'footer' => \App\Models\LandingFooter::first(),
        'footerLinks' => \App\Models\LandingFooterLink::where('status', true)->orderBy('display_order')->get()->groupBy('group'),
        'seo' => \App\Models\LandingSeo::first(),
        'plans' => \App\Models\Plan::with('modules')->get(),
        'testimonials' => \App\Models\LandingTestimonial::where('status', true)->get(),
        'testimonialsSection' => \App\Models\LandingSection::forKey('testimonials'),
    ]);
});

Route::get('/request-access', \App\Livewire\RequestAccess::class)->name('request-access');

Route::get('/request-access/verify/{token}', function (string $token) {
    try {
        $accessRequest = app(\App\Services\AccessRequestApprovalService::class)->verifyEmail($token);

        return view('pages.access-request-status', [
            'heading' => 'Email verified',
            'message' => "Thank you for your interest in Tanaki Hub, {$accessRequest->requester_name}. Your email has been verified and your organization request is now being reviewed. Tanaki Hub will contact you if additional information is required.",
            'isError' => false,
        ]);
    } catch (\RuntimeException $e) {
        return view('pages.access-request-status', [
            'heading' => 'Verification link expired',
            'message' => $e->getMessage(),
            'isError' => true,
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return view('pages.access-request-status', [
            'heading' => 'Invalid verification link',
            'message' => 'This verification link is not valid. Please check the link in your email or submit a new request.',
            'isError' => true,
        ]);
    }
})->middleware('signed')->name('access-request.verify');

Route::get('/request-access/status/{token}', \App\Livewire\AccessRequestStatus::class)->name('access-request.status');

Route::get('/privacy', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('privacy')]);
});

Route::get('/terms', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('terms')]);
});

Route::get('/cookies', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('cookies')]);
});

Route::get('/about', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('about')]);
});

Route::get('/contact', function () {
    return view('pages.contact');
});

Route::get('/docs', function () {
    return view('pages.coming-soon', ['title' => 'Documentation']);
});

Route::get('/help', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('help')]);
});

Route::get('/blog', function () {
    return view('pages.coming-soon', ['title' => 'Blog']);
});

Route::get('/faq', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('faq')]);
});

Route::get('/careers', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('careers')]);
});

Route::get('/support', function () {
    return view('pages.content', ['page' => \App\Models\ContentPage::forSlug('support')]);
});

Route::get('/login', function () {
    return redirect()->to('/admin/login');
})->name('login');

Route::get('/register', function () {
    return redirect()->to('/request-access');
})->name('register');

Route::get('/portal/invitations/{token}', \App\Livewire\AcceptPortalInvitation::class)->name('portal.invitation.accept');

// ─────────────────────────────────────────────────────────────────────────
// Enterprise SSO (tenant admin panel login — see tanaki-login.blade.php)
// ─────────────────────────────────────────────────────────────────────────
Route::get('/auth/{provider}', [SocialiteLoginController::class, 'redirect'])
    ->whereIn('provider', ['google', 'microsoft'])
    ->name('sso.redirect');
Route::get('/auth/{provider}/callback', [SocialiteLoginController::class, 'callback'])
    ->whereIn('provider', ['google', 'microsoft'])
    ->name('sso.callback');

// 'web' covers tenant company accounts; 'super_admin' covers the super-admin
// panel's own guard (see config/auth.php) — two-factor and impersonation-stop
// need to work no matter which one the current request is authenticated under.
Route::middleware('auth:web,super_admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('payslips/{employee}/generate', [PayslipController::class, 'generate'])->name('payslips.generate');

    // Settlement export (Excel CSV / PDF)
    Route::get('settlements/export', [\App\Http\Controllers\SettlementController::class, 'export'])->name('settlements.export');
    Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::post('/super-admin/impersonation/stop', [SuperAdminImpersonationController::class, 'stop'])->name('superadmin.impersonation.stop');
    Route::post('/logout/{guard}', PanelLogoutController::class)
        ->whereIn('guard', ['web', 'super_admin'])
        ->name('panel-logout');
    Route::post('/admin/announcements/{announcement}/read', [AnnouncementReadController::class, 'store'])->name('announcements.read');
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
    Route::get('/two-factor/totp/setup', [TwoFactorController::class, 'totpSetup'])->name('two-factor.totp.setup');
    Route::post('/two-factor/totp/enable', [TwoFactorController::class, 'totpEnable'])->name('two-factor.totp.enable');
    Route::post('/two-factor/totp/disable', [TwoFactorController::class, 'totpDisable'])->name('two-factor.totp.disable');
});

Route::middleware(['auth:super_admin', 'superadmin'])->group(function () {
    Route::post('/super-admin/impersonation/{user}', [SuperAdminImpersonationController::class, 'start'])->name('superadmin.impersonation.start');
    // GET used by the Filament table action (avoids Livewire session-cookie race)
    Route::get('/super-admin/impersonation/{user}/go', [SuperAdminImpersonationController::class, 'startGet'])->name('superadmin.impersonation.start.get');
});

// Invoice Routes
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');

Route::post('/ai/ask', [AIController::class, 'ask'])
    ->middleware(['auth','throttle:10,1','feature:ai_assistant']);

Route::get('/calls/{room}', [CallController::class, 'room'])->name('calls.room');
Route::get('/calls/{room}/signals', [CallController::class, 'index'])->name('calls.signals.index');
Route::post('/calls/{room}/signals', [CallController::class, 'store'])->name('calls.signals.store');

// Notifications: mark as seen (stores a timestamp in session)
Route::post('/admin/notifications/seen', function () {
    session(['notifications.last_seen' => now()]);
    return response()->noContent();
})->middleware(['auth'])->name('notifications.seen');

// ─────────────────────────────────────────────────────────────────────────
// Subscription Payment Callback (Paystack redirects here after payment)
// ─────────────────────────────────────────────────────────────────────────
Route::get('/subscription/callback', function (\Illuminate\Http\Request $request) {
    $reference = $request->get('reference') ?? $request->get('trxref');

    if ($reference) {
        // Try synchronous activation first; if it fails (e.g. network timeout),
        // the Paystack webhook will activate the subscription when it arrives.
        try {
            $success = app(\App\Services\SubscriptionService::class)->handleCallback($reference);

            if ($success) {
                return redirect('/admin/manage-subscription')
                    ->with('success', 'Payment successful! Your subscription is now active.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Subscription callback sync failed — webhook will activate.', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        // Optimistic redirect: webhook will complete activation within seconds
        return redirect('/admin/manage-subscription')
            ->with('success', 'Payment received! Your subscription will activate shortly — refresh the page in a moment.');
    }

    return redirect('/admin/manage-subscription')
        ->with('error', 'Payment failed or was cancelled. Please try again.');
})->name('subscription.callback');

// Subscription Auto-Renewal Toggle (Livewire calls this via action, kept for fallback)
Route::post('/subscription/auto-renewal/toggle', function () {
    $company = auth()->user()?->company;
    if ($company) {
        app(\App\Services\SubscriptionService::class)->toggleAutoRenewal($company);
    }
    return back();
})->middleware(['auth'])->name('subscription.toggle-renewal');

// ─────────────────────────────────────────────────────────────────────────
// Paystack Invoice Payment Callback
// ─────────────────────────────────────────────────────────────────────────
Route::get('/payments/callback', function (\Illuminate\Http\Request $request) {
    $reference = $request->get('reference') ?? $request->get('trxref');

    if ($reference) {
        // Webhook does the heavy lifting; redirect with optimistic success message
        return redirect()->to('/admin/invoices')->with('success', 'Payment successful! Your invoice has been updated.');
    }

    return redirect()->to('/admin/invoices')->with('error', 'Payment failed or was cancelled.');
})->name('payments.callback');

// Wallet Funding Callback
// Paystack redirects here after the user completes (or cancels) payment.
// We attempt synchronous completion first; if that fails we dispatch the retry job
// so the wallet is still funded even if the Paystack verify API is temporarily slow.
Route::get('/wallets/fund/callback', function (\Illuminate\Http\Request $request) {
    $reference = $request->get('reference') ?? $request->get('trxref');

    if ($reference) {
        try {
            app(\App\Services\WalletFundingService::class)->complete($reference);

            return redirect()->to('/admin/wallet-fundings')
                ->with('success', 'Wallet funded successfully! Your balance has been updated.');
        } catch (\Exception $e) {
            // Synchronous verification failed (e.g. Paystack API timeout).
            // Dispatch the job so it retries in the background (3 attempts: 1m, 5m, 10m).
            \App\Jobs\VerifyFundingTransactionJob::dispatch($reference, $reference);

            \Illuminate\Support\Facades\Log::warning(
                "Wallet callback sync failed for {$reference} — job queued for retry.",
                ['error' => $e->getMessage()]
            );

            return redirect()->to('/admin/wallet-fundings')
                ->with('warning', 'Payment received — verification is still in progress. Your wallet balance will update shortly.');
        }
    }

    return redirect()->to('/admin/wallet-fundings')
        ->with('error', 'Wallet funding was not completed. Please try again or contact support.');
})->middleware(['auth'])->name('wallet.funding.callback');

