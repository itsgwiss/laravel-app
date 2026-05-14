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

    // ── Send OTP via EMAIL (real Gmail SMTP) ──────────────

    public function sendEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $code  = $this->generateOtp($email);

        try {
            Mail::raw(
                "Your Grace verification code is: {$code}\n\nThis code expires in 10 minutes.",
                function ($m) use ($email) {
                    $m->to($email)->subject('Your Grace OTP Code');
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

    // ── Send OTP via SMS (real Semaphore API) ─────────────

    public function sendSms(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $phone = $request->input('phone');
        $code  = $this->generateOtp($phone);

        try {
            $response = Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
                'apikey'     => config('services.semaphore.key'),
                'number'     => $phone,
                'message'    => "Your Grace code: {$code}. Expires in 10 minutes.",
                'sendername' => config('services.semaphore.sender'),
            ]);

            if (! $response->successful()) {
                Log::error('Semaphore SMS failed: ' . $response->body());
                return redirect()->back()
                    ->withErrors(['phone' => 'Failed to send SMS. Check your Semaphore API key.']);
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

        // Get target from session OR hidden input fallback
        $target = session('otp_target') ?? $request->input('otp_target');

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

        return redirect()->route('dashboard')
            ->with('success', "✓ {$target} verified successfully!");
    }
}