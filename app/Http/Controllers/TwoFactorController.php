<?php

namespace App\Http\Controllers;

use App\Models\TwoFactorCode;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp)
    {
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->two_factor_enabled) {
            return redirect('/admin');
        }

        if ($request->session()->get('two_factor_passed') === true) {
            return redirect()->intended('/admin');
        }

        return view('auth.two-factor-challenge', [
            'email' => $user->email,
            'method' => $user->two_factor_method ?: 'email',
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        $user = $request->user();
        $method = $user->two_factor_method ?: 'email';

        if ($method === 'totp') {
            $secret = (string) ($user->totp_secret ?? '');

            if (! $secret || ! $this->totp->verifyCode($secret, (string) $request->string('code'))) {
                return back()->withErrors(['code' => 'Invalid TOTP code.']);
            }

            $request->session()->put('two_factor_passed', true);
            $request->session()->put('two_factor_user_id', $user->id);

            return redirect()->intended('/admin');
        }

        $code = TwoFactorCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $code || ! hash_equals((string) $code->code, (string) $request->string('code'))) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        $code->update(['used_at' => now()]);
        $request->session()->put('two_factor_passed', true);
        $request->session()->put('two_factor_user_id', $user->id);

        return redirect()->intended('/admin');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (($user->two_factor_method ?: 'email') === 'totp') {
            return back()->with('status', 'Use your authenticator app to generate a new code.');
        }

        $this->issueCode($user->id, $user->email);

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function totpSetup(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/admin/login');
        }

        $secret = $request->session()->get('totp_setup_secret');
        if (! $secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('totp_setup_secret', $secret);
        }

        $issuer = config('app.name', 'Truck Manager');
        $uri = $this->totp->otpAuthUri($issuer, $user->email, $secret);

        return view('auth.totp-setup', [
            'secret' => $secret,
            'otpAuthUri' => $uri,
            'qrUrl' => 'https://chart.googleapis.com/chart?cht=qr&chs=220x220&chl=' . rawurlencode($uri),
        ]);
    }

    public function totpEnable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $secret = (string) $request->session()->get('totp_setup_secret', '');

        if (! $secret) {
            return redirect()->route('two-factor.totp.setup')->withErrors(['code' => 'Setup session expired.']);
        }

        if (! $this->totp->verifyCode($secret, (string) $request->string('code'))) {
            return back()->withErrors(['code' => 'Invalid TOTP code.']);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => 'totp',
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
        ]);

        $request->session()->forget('totp_setup_secret');
        $request->session()->forget(['two_factor_passed', 'two_factor_code_sent_at', 'two_factor_user_id']);

        return redirect('/admin')->with('status', 'TOTP authenticator enabled successfully.');
    }

    public function totpDisable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'two_factor_method' => 'email',
            'totp_secret' => null,
            'totp_confirmed_at' => null,
        ]);

        return back()->with('status', 'TOTP authenticator disabled.');
    }

    public static function issueCode(int $userId, string $email): void
    {
        $code = (string) random_int(100000, 999999);

        TwoFactorCode::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        TwoFactorCode::create([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::raw("Your Truck Manager verification code is {$code}. It expires in 10 minutes.", function ($message) use ($email) {
            $message->to($email)->subject('Truck Manager 2FA Verification Code');
        });
    }
}
