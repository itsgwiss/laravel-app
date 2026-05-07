<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailboxController extends Controller
{
    /**
     * Show inbox and sent messages.
     */
    public function index()
    {
        $user = Auth::user();

        $inbox = Message::where('to_email', $user->email)
                        ->orderByDesc('created_at')
                        ->get();

        $sent = Message::where('user_id', $user->id)
                       ->where('type', 'sent')
                       ->orderByDesc('created_at')
                       ->get();

        return view('mailbox', compact('inbox', 'sent'));
    }

    /**
     * Send a new message (AJAX or form POST).
     */
    public function send(Request $request)
    {
        $request->validate([
            'to'      => 'required|email',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string|max:5000',
        ]);

        $user = Auth::user();

        // Store in DB as sent
        Message::create([
            'user_id'    => $user->id,
            'from_name'  => $user->name,
            'from_email' => $user->email,
            'to_email'   => $request->to,
            'subject'    => $request->subject,
            'body'       => $request->body,
            'type'       => 'sent',
        ]);

        // Also create inbox record for the recipient (if they're in the system)
        Message::create([
            'user_id'    => null,           // set on read if recipient is authed user
            'from_name'  => $user->name,
            'from_email' => $user->email,
            'to_email'   => $request->to,
            'subject'    => $request->subject,
            'body'       => $request->body,
            'type'       => 'inbox',
        ]);

        // Optionally send real email
        try {
            Mail::raw($request->body, function ($m) use ($request, $user) {
                $m->to($request->to)
                  ->from($user->email, $user->name)
                  ->subject($request->subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Mailbox: Could not send real email — ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Message sent successfully!']);
        }

        return redirect()->route('mailbox')->with('success', 'Message sent!');
    }
}
