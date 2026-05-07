<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OTPController extends Controller
{
    // ── Views ────────────────────────────────────────────

    public function dashboard()
    {
        return view('dashboard');
    }

    public function phone()
    {
        return view('otp-phone');
    }

    public function email()
    {
        return view('otp-email');
    }

    public function showValidate()
    {
        return view('validate-otp');
    }

    // ── Actions ──────────────────────────────────────────

    /**
     * Generate and store a 6-digit OTP in cache (10 min TTL).
     */
    private function generateOtp(string $key): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("otp:{$key}", $code, now()->addMinutes(10));
        return $code;
    }

    /**
     * Send OTP via email.
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        $code  = $this->generateOtp($email);

        // Send the OTP email
        Mail::raw(
            "Your Grace verification code is: {$code}\n\nThis code expires in 10 minutes.",
            function ($m) use ($email) {
                $m->to($email)->subject('Your Grace OTP Code');
            }
        );

        Log::info("OTP for {$email}: {$code}"); // visible in storage/logs/laravel.log

        return redirect()
            ->route('otp.validate')
            ->with('success', "OTP sent to {$email}.")
            ->with('otp_target', $email);
    }

    /**
     * Send OTP via SMS (stub — integrate Twilio/Vonage here).
     */
    public function sendSms(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $phone = $request->input('phone');
        $code  = $this->generateOtp($phone);

        // TODO: Replace with real SMS provider (Twilio, Vonage, etc.)
        // Example (Twilio):
        //   $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        //   $twilio->messages->create($phone, ['from' => env('TWILIO_FROM'), 'body' => "Your code: {$code}"]);

        Log::info("SMS OTP for {$phone}: {$code}");

        return redirect()
            ->route('otp.validate')
            ->with('success', "OTP sent to {$phone}.")
            ->with('otp_target', $phone);
    }

    /**
     * Verify the submitted OTP.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $submitted = $request->input('otp');
        $target    = session('otp_target');

        if (!$target) {
            return redirect()
                ->route('otp.validate')
                ->withErrors(['otp' => 'Session expired. Please request a new OTP.']);
        }

        $cached = Cache::get("otp:{$target}");

        if (!$cached) {
            return redirect()
                ->route('otp.validate')
                ->withErrors(['otp' => 'OTP has expired. Please request a new one.'])
                ->with('otp_target', $target);
        }

        if ($submitted !== $cached) {
            return redirect()
                ->route('otp.validate')
                ->withErrors(['otp' => 'Invalid code. Please try again.'])
                ->with('otp_target', $target);
        }

        // OTP is valid — clear it
        Cache::forget("otp:{$target}");

        return redirect()
            ->route('dashboard')
            ->with('success', "✓ {$target} successfully verified.");
    }
}
