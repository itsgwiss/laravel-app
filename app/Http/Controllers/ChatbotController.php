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

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message'           => 'required|string|max:2000',
            'history'           => 'nullable|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:4000',
        ]);

        $userMessage = $request->input('message');

        // Build full messages array: history + new user message
        $messages = collect($request->input('history', []))
            ->map(fn($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->push(['role' => 'user', 'content' => $userMessage])
            ->values()
            ->toArray();

        $apiKey = config('services.anthropic.key');

        if ($apiKey) {
            try {
                $response = Http::withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 512,
                    'system'     => $this->systemPrompt(),
                    'messages'   => $messages,
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

        return response()->json(['reply' => $this->fallbackReply(strtolower($userMessage))]);
    }

    /**
     * Edit this method to describe RepoHive to the AI.
     * The more detail you add here, the better it answers.
     */
    private function systemPrompt(): string
    {
        return
            "You are RepoHive AI, the built-in assistant for the RepoHive platform. You are friendly, concise, and helpful.\n\n" .
            "## About RepoHive\n" .
            "RepoHive is a platform that [DESCRIBE WHAT REPOHIVE DOES].\n" .
            "It is designed for [YOUR TARGET USERS].\n\n" .
            "## Features\n" .
            "- Dashboard: Central hub where users access all features after logging in.\n" .
            "- OTP Verification: Users can verify their identity via a one-time password sent by Email or SMS.\n" .
            "- Mailbox: An in-app messaging system to send and receive messages within the platform.\n" .
            "- Profile Management: Users can edit their name, email, and password from the profile page.\n" .
            "- [ADD MORE FEATURES HERE]\n\n" .
            "## Common Questions\n" .
            "Q: How do I send an OTP?\n" .
            "A: From your dashboard, click Email OTP or SMS OTP, enter your contact info, and a code will be sent. Enter the code on the Validate OTP page.\n\n" .
            "Q: How do I reset my password?\n" .
            "A: Go to the login page and click Forgot Password. A reset link will be sent to your email.\n\n" .
            "Q: Where is my inbox?\n" .
            "A: Click Mailbox on the dashboard to open your in-app inbox.\n\n" .
            "[ADD MORE Q&A HERE]\n\n" .
            "## Rules\n" .
            "- Only answer questions related to RepoHive and its features.\n" .
            "- If you do not know something, say: I don't have that info yet — please contact our support team.\n" .
            "- Keep answers short (2-4 sentences) unless the user asks for more detail.\n" .
            "- Never invent features or pricing that are not listed above.\n";
    }

    private function fallbackReply(string $msg): string
    {
        return match (true) {
            str_contains($msg, 'otp')                               => "You can send an OTP via SMS or Email from the Dashboard. Use the 'Validate OTP' page to verify your code.",
            str_contains($msg, 'sms')                               => "To receive an OTP by SMS, click 'SMS OTP' on the dashboard and enter your phone number.",
            str_contains($msg, 'email')                             => "To receive an OTP by email, click 'Email OTP' on the dashboard and enter your email address.",
            str_contains($msg, 'mailbox')                           => "Your mailbox lets you send and receive messages. Click 'Mailbox' on the dashboard to open it.",
            str_contains($msg, 'password')                          => "You can reset your password via the 'Forgot Password' link on the login page.",
            str_contains($msg, 'hello') || str_contains($msg, 'hi') => "Hello! I'm RepoHive AI. How can I help you today?",
            str_contains($msg, 'help')                              => "I can help with OTP verification, mailbox, password resets, and general account questions. What do you need?",
            default                                                  => "I'm RepoHive AI. Could you give me a bit more detail so I can help you better?",
        };
    }
}