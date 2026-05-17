<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Message;
use App\Models\User;


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
    Route::get('/mailbox/unread-count', [MailboxController::class, 'getUnreadCount'])->name('mailbox.unread-count');
    Route::post('/mailbox/bulk-read', [MailboxController::class, 'markBulkAsRead'])->name('mailbox.bulk-read');
    Route::post('/mailbox/bulk-delete', [MailboxController::class, 'bulkDestroy'])->name('mailbox.bulk-destroy');
    Route::post('/mailbox/send', [MailboxController::class, 'send'])->name('mailbox.send')->middleware('throttle:10,1');
    
    // Routes 
    Route::post('/mailbox/reply/{message}', [MailboxController::class, 'reply'])->name('mailbox.reply')->middleware('throttle:10,1');
    Route::put('/mailbox/read/{message}', [MailboxController::class, 'markAsRead'])->name('mailbox.mark-read');
    Route::delete('/mailbox/{message}', [MailboxController::class, 'destroy'])->name('mailbox.destroy');
    
    // View routes - Keep these LAST
    Route::get('/mailbox', [MailboxController::class, 'index'])->name('mailbox');
    Route::get('/mailbox/{message}', [MailboxController::class, 'show'])->name('mailbox.show')->where('message', '[0-9]+'); // Only numeric IDs

    // Chatbot
    Route::get('/chatbot',          [ChatbotController::class, 'index'])->name('chatbot');
    Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])->name('chatbot.message');

    // Profile (Breeze default)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/mailbox/{id}', function($id) {
    $message = Message::find($id);
    
    if (!$message) {
        return response()->json(['body' => 'Message not found.'], 404);
    }
    
    // Check if the authenticated user is the sender or receiver
    $user = auth();
    if ($user && ($message->to_email == $user->email || $message->from_email == $user->email)) {
        return response()->json(['body' => $message->body]);
    }
    
    return response()->json(['body' => 'Unauthorized.'], 403);
})->middleware('auth');

   Route::get('/verify-user/{email}', function ($email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->email_verified_at = now();
        $user->save();
        return "User {$email} has been verified!";
    }
    return "User not found!";
})->middleware('auth'); 
});

// ── Breeze auth routes ─────────────────────────────────
require __DIR__ . '/auth.php';