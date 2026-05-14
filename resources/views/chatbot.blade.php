@extends('layouts.app')
@section('title', 'RepoHive  AI — Chatbot')

@section('content')
<div class="chatbot-only-page">
    <main class="chat-panel">
        <header class="chat-header">
            <div class="ai-orb">🤖</div>
            <div>
                <h2>RepoHive AI Assistant</h2>
                <small>Online · Ready to help</small>
            </div>
            <a href="{{ route('dashboard') }}" class="chat-back">← Dashboard</a>
        </header>

        <section class="chat-window" id="chatWindow">
            <div class="chat-message bot show">
                <div class="avatar">🤖</div>
                <div class="bubble">
                    Hi, I'm <strong>RepoHive AI</strong> — your AI assistant. I can help with OTP verification, mailbox questions, account support, and more. How can I help you today?
                </div>
            </div>
        </section>

        <footer class="chat-input-bar">
            <input
                id="chatInput"
                placeholder="Type your message…"
                onkeydown="handleChatKey(event)"
                autocomplete="off"
            >
            <button id="sendBtn" onclick="sendChat()">Send</button>
        </footer>
    </main>
</div>
@endsection
