<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Root redirect ──────────────────────────────────────
Route::get('/', fn () => redirect()->route('dashboard'));

// ── Protected routes (auth + verified) ─────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [OTPController::class, 'dashboard'])->name('dashboard');

    // OTP — views
    Route::get('/otp-phone',    [OTPController::class, 'phone'])->name('otp.phone');
    Route::get('/otp-email',    [OTPController::class, 'email'])->name('otp.email');
    Route::get('/validate-otp', [OTPController::class, 'showValidate'])->name('otp.validate');

    // OTP — actions
    Route::post('/otp/send-email', [OTPController::class, 'sendEmail'])->name('otp.sendEmail');
    Route::post('/otp/send-sms',   [OTPController::class, 'sendSms'])->name('otp.sendSms');
    Route::post('/otp/verify',     [OTPController::class, 'verify'])->name('otp.verify');

    // Mailbox
    Route::get('/mailbox',       [MailboxController::class, 'index'])->name('mailbox');
    Route::post('/mailbox/send', [MailboxController::class, 'send'])->name('mailbox.send');

    // Chatbot
    Route::get('/chatbot',          [ChatbotController::class, 'index'])->name('chatbot');
    Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])->name('chatbot.message');

    // Profile (Breeze default)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Breeze auth routes ─────────────────────────────────
require __DIR__ . '/auth.php';
