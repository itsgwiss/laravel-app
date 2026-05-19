<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OTPController extends Controller
{
    // ── Views ────────────────────────────────────────────

    public function dashboard()    { return view('dashboard'); }
    public function phone()        { return view('otp-phone'); }
    public function email()        { return view('otp-email'); }
    public function showValidate() { return view('validate-otp'); }

    // ── Generate + cache a 6-digit OTP (10 min) ──────────

    private function generateOtp(string $key): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('otp:' . $key, $code, now()->addMinutes(10));
        return $code;
    }

    // ── Check if already verified ─────────────────────────

    private function isEmailVerified(string $email): bool
    {
        return Cache::has('verified:email:' . $email);
    }

    private function isPhoneVerified(string $phone): bool
    {
        return Cache::has('verified:phone:' . $phone);
    }

    // ── Send OTP via EMAIL ────────────────────────────────

    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email       = $request->input('email');
        $forceResend = $request->boolean('force_reverify');

        // Already verified — ask for confirmation unless forced
        if ($this->isEmailVerified($email) && ! $forceResend) {
            return back()
                ->withInput()
                ->with('confirm_reverify', 'email')
                ->with('reverify_target', $email);
        }

        $code = $this->generateOtp($email);

        try {
            Mail::raw(
                "Welcome to RepoHive! 🎉\n\n" .
                "To complete your verification, use the one-time code below:\n\n" .
                "  🔐 Your OTP Code: {$code}\n\n" .
                "This code expires in 10 minutes.\n\n" .
                "⚠️  Please do not share this code with anyone — RepoHive staff will never ask for your OTP.\n\n" .
                "If you did not request this code, you can safely ignore this message.\n\n" .
                "— The RepoHive Team",
                function ($m) use ($email) {
                    $m->to($email)->subject('Your RepoHive Verification Code');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Email OTP failed: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['email' => 'Failed to send email. Check your MAIL settings in .env.']);
        }

        return redirect()
            ->route('otp.validate')
            ->with('success', "OTP sent to {$email}.")
            ->with('otp_target', $email);
    }

    // ── Send OTP via SMS ──────────────────────────────────

    public function sendSms(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $phone       = $request->input('phone');
        $forceResend = $request->boolean('force_reverify');

        // Already verified — ask for confirmation unless forced
        if ($this->isPhoneVerified($phone) && ! $forceResend) {
            return back()
                ->withInput()
                ->with('confirm_reverify', 'phone')
                ->with('reverify_target', $phone);
        }

        $code = $this->generateOtp($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer RDfcDtc9R4c4vZDQUhtIRkzwDzO7hjdfHaZsI1c1de4ca007',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post('https://repohive.com/api/messages', [
                'phone'   => $phone,
                'message' => "[RepoHive] Welcome! Your OTP is: {$code}. Expires in 10 mins. Do not share this code.",
            ]);

            if (! $response->successful()) {
                Log::error('RepoHive SMS failed: ' . $response->body());
                return redirect()->back()
                    ->withErrors(['phone' => 'Failed to send SMS. ' . $response->json('message', 'Check your API key.')]);
            }

        } catch (\Throwable $e) {
            Log::error('SMS OTP exception: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['phone' => 'SMS service unavailable. Try again later.']);
        }

        return redirect()
            ->route('otp.validate')
            ->with('success', "OTP sent to {$phone}.")
            ->with('otp_target', $phone);
    }

    // ── Verify the submitted OTP ──────────────────────────

    public function verify(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $submitted = $request->input('otp');
        $target    = session('otp_target') ?? $request->input('otp_target');

        if (! $target) {
            return redirect()->route('otp.validate')
                ->withErrors(['otp' => 'Session expired. Please request a new OTP.']);
        }

        $cached = Cache::get('otp:' . $target);

        if (! $cached) {
            return redirect()->route('otp.validate')
                ->withErrors(['otp' => 'OTP has expired. Please request a new one.'])
                ->with('otp_target', $target);
        }

        if ($submitted !== $cached) {
            return redirect()->route('otp.validate')
                ->withErrors(['otp' => 'Wrong code. Please try again.'])
                ->with('otp_target', $target);
        }

        Cache::forget('otp:' . $target);

        // Mark as verified in cache for 30 days
        $prefix = filter_var($target, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        Cache::put('verified:' . $prefix . ':' . $target, true, now()->addDays(30));

        // Send confirmation SMS if the verified target is a phone number
        if ($prefix === 'phone') {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer RDfcDtc9R4c4vZDQUhtIRkzwDzO7hjdfHaZsI1c1de4ca007',
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])->post('https://repohive.com/api/messages', [
                    'phone'   => $target,
                    'message' => "You're verified! Welcome to RepoHive!\n\nYour account has been successfully verified. We're glad to have you with us.\n\n- RepoHive Team",
                ]);
            } catch (\Throwable $e) {
                Log::error('Confirmation SMS failed: ' . $e->getMessage());
                // Non-blocking — verification still succeeds even if this SMS fails
            }
        }

        // Send confirmation email if the verified target is an email address
        if ($prefix === 'email') {
            try {
                Mail::raw(
                    "You're verified! Welcome to RepoHive! 🎉\n\n" .
                    "Your email address has been successfully verified.\n\n" .
                    "We're glad to have you with us. You can now enjoy full access to RepoHive.\n\n" .
                    "If you have any questions, feel free to reach out to our support team.\n\n" .
                    "— The RepoHive Team",
                    function ($m) use ($target) {
                        $m->to($target)->subject("✅ You're Verified — Welcome to RepoHive!");
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Confirmation email failed: ' . $e->getMessage());
                // Non-blocking — verification still succeeds even if this email fails
            }
        }

        return redirect()->route('dashboard')
            ->with('success', "✓ {$target} verified successfully!");
    }
}