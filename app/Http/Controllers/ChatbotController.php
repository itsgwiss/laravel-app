<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot');
    }

    /**
     * Forward user message to Anthropic Claude API and return reply.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $userMessage = $request->input('message');

        // ── Try Anthropic API if key is configured ──────────────
        $apiKey = config('services.anthropic.key');

        if ($apiKey) {
            try {
                $response = Http::withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 512,
                    'system'     => 'You are RepoHive AI, a friendly and helpful AI assistant built into the RepoHive platform. You help users with OTP verification, mailbox management, account support, and general questions. Be concise, warm, and professional.',
                    'messages'   => [
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                ]);

                if ($response->successful()) {
                    $content = $response->json('content.0.text');
                    if ($content) {
                        return response()->json(['reply' => $content]);
                    }
                }

                Log::warning('Anthropic API error: ' . $response->body());
            } catch (\Throwable $e) {
                Log::error('Chatbot API exception: ' . $e->getMessage());
            }
        }

        // ── Fallback: rule-based responses ──────────────────────
        $reply = $this->fallbackReply(strtolower($userMessage));

        return response()->json(['reply' => $reply]);
    }

    private function fallbackReply(string $msg): string
    {
        return match (true) {
            str_contains($msg, 'otp')        => "You can send an OTP via SMS or Email from the Dashboard. Use the 'Validate OTP' page to verify your code.",
            str_contains($msg, 'sms')        => "To receive an OTP by SMS, click 'SMS OTP' on the dashboard and enter your phone number.",
            str_contains($msg, 'email')      => "To receive an OTP by email, click 'Email OTP' on the dashboard and enter your email address.",
            str_contains($msg, 'mailbox')    => "Your mailbox lets you send and receive messages. Click 'Mailbox' on the dashboard to open it.",
            str_contains($msg, 'password')   => "You can reset your password via the 'Forgot Password' link on the login page.",
            str_contains($msg, 'hello')
                || str_contains($msg, 'hi')  => "Hello! I'm RepoHive AI, your AI assistant. How can I help you today?",
            str_contains($msg, 'help')       => "I can help with OTP verification, mailbox management, password resets, and general account questions. What do you need?",
            default                          => "I'm RepoHive AI, your assistant. Could you provide more details so I can help you better?",
        };
    }
}
