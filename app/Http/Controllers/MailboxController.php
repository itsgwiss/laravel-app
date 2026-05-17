<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class MailboxController extends Controller
{
    /**
     * Show inbox and sent messages with pagination.
     */
    public function index(): View
    {
        $user = Auth::user();

        $inbox = Message::where('to_email', $user->email)
                        ->orderByDesc('created_at')
                        ->paginate(20, ['*'], 'inbox_page');

        $sent = Message::where('user_id', $user->id)
                       ->where('type', 'sent')
                       ->orderByDesc('created_at')
                       ->paginate(20, ['*'], 'sent_page');

        // Get unread count for inbox
        $unreadCount = Message::where('to_email', $user->email)
                              ->whereNull('read_at')
                              ->count();

        return view('mailbox', compact('inbox', 'sent', 'unreadCount'));
    }

    /**
     * Send a new message (AJAX or form POST).
     */
    public function send(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'to'      => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Prevent sending to self
        if ($request->to === $user->email) {
            return back()->withErrors(['to' => 'You cannot send a message to yourself.'])->withInput();
        }

        try {
            // Create sent message
            Message::create([
                'user_id'    => $user->id,
                'from_name'  => $user->name,
                'from_email' => $user->email,
                'to_email'   => $request->to,
                'subject'    => $request->subject,
                'body'       => $request->body,
                'type'       => 'sent',
                'read_at'    => null,
            ]);

            // Create inbox message for recipient
            Message::create([
                'user_id'    => null,
                'from_name'  => $user->name,
                'from_email' => $user->email,
                'to_email'   => $request->to,
                'subject'    => $request->subject,
                'body'       => $request->body,
                'type'       => 'inbox',
                'read_at'    => null,
            ]);

            // *** SEND REAL EMAIL VIA GMAIL SMTP ***
            try {
                $this->sendRealEmail($request, $user);
                $emailStatus = 'Email sent to the recipient\'s.';
            } catch (\Exception $e) {
                Log::error('Email sending failed: ' . $e->getMessage());
                $emailStatus = 'Message saved but email delivery failed.';
            }

            return redirect()->route('mailbox')->with('success', 'Message sent successfully! ' . ($emailStatus ?? ''));
            
        } catch (\Exception $e) {
            Log::error('Mail send error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to send message. Please try again.'])->withInput();
        }
    }

    /**
     * Send a real email using Gmail SMTP.
     */
    private function sendRealEmail(Request $request, User $user): void
    {
        // Prepare email content with HTML formatting
        $emailContent = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #7C3AED; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    .message-box { background: white; padding: 15px; border-left: 4px solid #7C3AED; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>📧 New Message from RepoHive</h2>
                    </div>
                    <div class='content'>
                        <p><strong>From:</strong> {$user->name} ({$user->email})</p>
                        <p><strong>Subject:</strong> {$request->subject}</p>
                        <div class='message-box'>
                            <strong>Message:</strong><br>
                            <p style='margin-top: 10px;'>{$request->body}</p>
                        </div>
                        <p>To reply to this message, log in to your RepoHive account.</p>
                    </div>
                    <div class='footer'>
                        <p>This message was sent from RepoHive. Please do not reply to this email directly.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        Mail::send([], [], function ($message) use ($request, $user, $emailContent) {
            $message->to($request->to)
                    ->subject($request->subject)
                    ->from($user->email, $user->name)
                    ->replyTo($user->email, $user->name)
                    ->html($emailContent);
        });
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        // Check if user is the recipient
        if ($message->to_email !== Auth::user()->email) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        // Only update if not already read
        if (is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Marked as read.']);
        }

        return back()->with('success', 'Message marked as read.');
    }

    /**
     * Mark multiple messages as read.
     */
    public function markBulkAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        $user = Auth::user();

        $count = Message::whereIn('id', $request->message_ids)
                        ->where('to_email', $user->email)
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} message(s) marked as read."
            ]);
        }

        return back()->with('success', "{$count} message(s) marked as read.");
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, Message $originalMessage): JsonResponse|RedirectResponse
    {
        try {
            // Check if user is the recipient
            if ($originalMessage->to_email !== Auth::user()->email) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'You can only reply to messages sent to you.'], 403);
                }
                abort(403);
            }

            $request->validate([
                'body' => 'required|string|max:5000',
            ]);

            $user = Auth::user();

            // Prepare reply subject (add Re: if not already there)
            $subject = $originalMessage->subject;
            if (!str_starts_with($subject, 'Re:')) {
                $subject = 'Re: ' . $subject;
            }

            // Create sent message
            $replyMessage = Message::create([
                'user_id'    => $user->id,
                'from_name'  => $user->name,
                'from_email' => $user->email,
                'to_email'   => $originalMessage->from_email,
                'subject'    => $subject,
                'body'       => $this->sanitizeBody($request->body),
                'type'       => 'sent',
                'read_at'    => null,
            ]);

            // Create inbox message for original sender
            Message::create([
                'user_id'    => null,
                'from_name'  => $user->name,
                'from_email' => $user->email,
                'to_email'   => $originalMessage->from_email,
                'subject'    => $subject,
                'body'       => $this->sanitizeBody($request->body),
                'type'       => 'inbox',
                'read_at'    => null,
            ]);

            // Send email notification for reply
            try {
                $this->sendReplyEmail($request, $user, $originalMessage, $subject);
            } catch (\Exception $e) {
                Log::warning('Mailbox: Failed to send reply email - ' . $e->getMessage());
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Reply sent successfully!',
                    'data' => $replyMessage
                ]);
            }

            return redirect()->route('mailbox')->with('success', 'Reply sent successfully!');
        } catch (\Exception $e) {
            Log::error('Reply error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to send reply']);
        }
    }

    /**
     * Send reply email.
     */
    private function sendReplyEmail(Request $request, User $user, Message $originalMessage, string $subject): void
    {
        $emailContent = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #f53b8f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    .message-box { background: white; padding: 15px; border-left: 4px solid #7C3AED; margin: 20px 0; }
                    .original { background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 13px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>📧 Reply to your message</h2>
                    </div>
                    <div class='content'>
                        <p><strong>From:</strong> {$user->name} ({$user->email})</p>
                        <p><strong>Subject:</strong> {$subject}</p>
                        <div class='message-box'>
                            <strong>Reply:</strong><br>
                            <p style='margin-top: 10px;'>{$request->body}</p>
                        </div>
                        <div class='original'>
                            <strong>Original message:</strong><br>
                            <p>{$originalMessage->body}</p>
                        </div>
                        <p>To continue the conversation, log in to your RepoHive account.</p>
                    </div>
                    <div class='footer'>
                        <p>This message was sent from RepoHive. Please do not reply to this email directly.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        Mail::send([], [], function ($message) use ($originalMessage, $user, $subject, $emailContent) {
            $message->to($originalMessage->from_email)
                    ->subject($subject)
                    ->from($user->email, $user->name)
                    ->replyTo($user->email, $user->name)
                    ->html($emailContent);
        });
    }

    /**
     * Delete a message.
     */
    public function destroy(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        // Check if user has permission to delete this message
        $canDelete = ($message->user_id === $user->id)
                  || ($message->to_email === $user->email);

        if (! $canDelete) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized to delete this message.'], 403);
            }
            abort(403);
        }

        // Store message info for response
        $messageType = $message->type;
        
        // Delete the message
        $message->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully.',
                'type' => $messageType
            ]);
        }

        return redirect()->route('mailbox')->with('success', 'Message deleted successfully.');
    }

    /**
     * Delete multiple messages (bulk delete).
     */
    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        $user = Auth::user();

        // Get messages that belong to the user (either sent or received)
        $messages = Message::whereIn('id', $request->message_ids)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('to_email', $user->email);
            })
            ->get();

        $count = $messages->count();
        
        // Delete each message
        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $message->delete();
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} message(s) deleted successfully."
            ]);
        }

        return back()->with('success', "{$count} message(s) deleted successfully.");
    }

    /**
     * Get unread message count (for AJAX polling).
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $unreadCount = Message::where('to_email', $user->email)
                              ->whereNull('read_at')
                              ->count();

        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Show a single message.
     */
    public function show(Request $request, Message $message): JsonResponse|View
    {
        $user = Auth::user();

        // Check if user is allowed to view this message
        $canView = ($message->user_id === $user->id)
                || ($message->to_email === $user->email);

        if (! $canView) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            abort(403);
        }

        // Mark as read if user is the recipient and message is unread
        if ($message->to_email === $user->email && is_null($message->read_at)) {
            $message->update(['read_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        }

        return view('message-show', compact('message'));
    }

    /**
     * Sanitize message body to prevent XSS attacks.
     * 
     * @param string $body
     * @return string
     */
    private function sanitizeBody(string $body): string
    {
        // Allow basic HTML tags for formatting, remove dangerous ones
        $allowedTags = '<p><br><b><i><strong><em><u><ul><ol><li><a><div><span>';
        return strip_tags($body, $allowedTags);
    }
}