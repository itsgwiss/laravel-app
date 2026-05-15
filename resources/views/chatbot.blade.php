@extends('layouts.app')
@section('title', 'RepoHive AI — Chatbot')

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
                    Hi, I'm <strong>RepoHive AI</strong> — your assistant. I can help with OTP verification,
                    mailbox questions, account support, and more. How can I help you today?
                </div>
            </div>
        </section>

        {{-- Typing indicator --}}
        <div id="typingIndicator" class="chat-typing hidden">
            <div class="avatar">🤖</div>
            <div class="bubble typing-bubble">
                <span></span><span></span><span></span>
            </div>
        </div>

        {{-- Quick prompts --}}
        <div class="quick-prompts" id="quickPrompts">
            <button onclick="quickSend('What is RepoHive?')">What is RepoHive?</button>
            <button onclick="quickSend('How does OTP work?')">How does OTP work?</button>
            <button onclick="quickSend('How do I reset my password?')">Reset password</button>
            <button onclick="quickSend('How do I use the mailbox?')">Mailbox help</button>
        </div>

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

<style>
.chatbot-only-page {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 2rem 1rem;
    min-height: 80vh;
}
.chat-panel {
    width: 100%;
    max-width: 700px;
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.chat-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.chat-header h2 { margin: 0; font-size: 1rem; font-weight: 600; color: #111; }
.chat-header small { color: #22c55e; font-size: 0.75rem; }
.chat-back { margin-left: auto; font-size: 0.8rem; color: #6b7280; text-decoration: none; }
.chat-back:hover { color: #111; }
.ai-orb { font-size: 1.5rem; }

.chat-window {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
    min-height: 380px;
    max-height: 480px;
}
.chat-message {
    display: flex;
    align-items: flex-end;
    gap: 0.625rem;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.chat-message.show { opacity: 1; transform: translateY(0); }
.chat-message.user { flex-direction: row-reverse; }
.avatar { font-size: 1.25rem; flex-shrink: 0; }
.bubble {
    max-width: 75%;
    padding: 0.625rem 0.875rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    line-height: 1.55;
}
.bot .bubble {
    background: #f3f4f6;
    color: #111;
    border-bottom-left-radius: 0.25rem;
}
.user .bubble {
    background: #2563eb;
    color: #fff;
    border-bottom-right-radius: 0.25rem;
}

/* Typing indicator */
.chat-typing {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0 1.25rem 0.5rem;
}
.chat-typing.hidden { display: none; }
.typing-bubble {
    background: #f3f4f6;
    border-radius: 1rem;
    border-bottom-left-radius: 0.25rem;
    padding: 0.625rem 0.875rem;
    display: flex;
    gap: 4px;
    align-items: center;
}
.typing-bubble span {
    width: 6px; height: 6px;
    background: #9ca3af;
    border-radius: 50%;
    animation: bounce 1.2s infinite;
}
.typing-bubble span:nth-child(2) { animation-delay: 0.2s; }
.typing-bubble span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce {
    0%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-6px); }
}

/* Quick prompts */
.quick-prompts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    border-top: 1px solid #f3f4f6;
}
.quick-prompts button {
    font-size: 0.75rem;
    padding: 0.3rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #374151;
    cursor: pointer;
    transition: background 0.15s;
}
.quick-prompts button:hover { background: #e5e7eb; }

/* Input bar */
.chat-input-bar {
    display: flex;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
}
.chat-input-bar input {
    flex: 1;
    padding: 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s;
}
.chat-input-bar input:focus { border-color: #2563eb; }
.chat-input-bar button {
    padding: 0.625rem 1.25rem;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s;
}
.chat-input-bar button:hover { background: #1d4ed8; }
.chat-input-bar button:disabled { background: #93c5fd; cursor: not-allowed; }
</style>

<script>
    const chatWindow     = document.getElementById('chatWindow');
    const chatInput      = document.getElementById('chatInput');
    const sendBtn        = document.getElementById('sendBtn');
    const typingEl       = document.getElementById('typingIndicator');
    const quickPrompts   = document.getElementById('quickPrompts');
    const csrfToken      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Holds conversation history sent to backend each turn
    let history = [];

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.className = 'chat-message ' + (role === 'user' ? 'user' : 'bot');

        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.textContent = role === 'user' ? '👤' : '🤖';

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = escHtml(text).replace(/\n/g, '<br>');

        div.appendChild(avatar);
        div.appendChild(bubble);
        chatWindow.appendChild(div);

        // Trigger animation
        requestAnimationFrame(() => div.classList.add('show'));
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function setLoading(loading) {
        sendBtn.disabled = loading;
        typingEl.classList.toggle('hidden', !loading);
        if (loading) chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function quickSend(text) {
        chatInput.value = text;
        quickPrompts.style.display = 'none';
        sendChat();
    }

    async function sendChat() {
        const text = chatInput.value.trim();
        if (!text || sendBtn.disabled) return;

        chatInput.value = '';
        quickPrompts.style.display = 'none';
        appendMessage('user', text);
        setLoading(true);

        try {
            const res = await fetch('{{ route("chatbot.message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history }),
            });

            const data = await res.json();
            setLoading(false);

            const reply = data.reply || data.error || 'Sorry, something went wrong.';
            appendMessage('bot', reply);

            // Update history (keep last 20 messages to avoid token bloat)
            history.push({ role: 'user',      content: text  });
            history.push({ role: 'assistant', content: reply });
            if (history.length > 20) history = history.slice(-20);

        } catch (e) {
            setLoading(false);
            appendMessage('bot', 'Network error — please try again.');
        }

        chatInput.focus();
    }

    function handleChatKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    }
</script>
@endsection