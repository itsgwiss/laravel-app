@extends('layouts.app')
@section('title', 'Mailbox — Grace')

@section('content')
<div class="mailbox">
    <aside class="sidebar">
        <div class="brand">🐝 Grace</div>

        <button class="compose-btn" onclick="openCompose()">+ Compose</button>

        <a class="menu active" id="inboxTab" onclick="showTab('inbox', this)">
            Inbox
            <span>{{ $inbox->count() }}</span>
        </a>
        <a class="menu" id="sentTab" onclick="showTab('sent', this)">
            Sent
            <span>{{ $sent->count() }}</span>
        </a>

        <div style="margin-top:auto;padding-top:1rem">
            <a href="{{ route('dashboard') }}" class="menu">← Dashboard</a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h2 id="mailTitle">Inbox</h2>
                <small style="color:var(--muted)">{{ Auth::user()->email }}</small>
            </div>
            <input id="searchMail" placeholder="Search mail…" onkeyup="filterMail()">
        </header>

        @if(session('success'))
            <div class="alert success" style="margin:0.75rem 1.5rem">{{ session('success') }}</div>
        @endif

        <section class="mail-area">
            {{-- Mail List --}}
            <div id="mailList" class="mail-list">
                {{-- Inbox --}}
                <div id="inboxList">
                    @forelse($inbox as $msg)
                        <div
                            class="mail-item {{ !$msg->read_at ? 'unread' : '' }}"
                            onclick="previewMail(
                                '{{ addslashes($msg->subject) }}',
                                'From: {{ addslashes($msg->from_email) }} · {{ $msg->created_at->diffForHumans() }}',
                                '{{ addslashes($msg->body) }}',
                                this
                            )"
                        >
                            <div class="from">{{ $msg->from_name ?? $msg->from_email }}</div>
                            <div class="subject">{{ $msg->subject }}</div>
                            <div class="date">{{ $msg->created_at->format('M j, g:i A') }}</div>
                        </div>
                    @empty
                        <div style="padding:2rem;text-align:center;color:var(--muted);font-size:0.875rem">
                            No messages in your inbox.
                        </div>
                    @endforelse
                </div>

                {{-- Sent --}}
                <div id="sentList" style="display:none">
                    @forelse($sent as $msg)
                        <div
                            class="mail-item"
                            onclick="previewMail(
                                '{{ addslashes($msg->subject) }}',
                                'To: {{ addslashes($msg->to_email) }} · {{ $msg->created_at->diffForHumans() }}',
                                '{{ addslashes($msg->body) }}',
                                this
                            )"
                        >
                            <div class="from">To: {{ $msg->to_email }}</div>
                            <div class="subject">{{ $msg->subject }}</div>
                            <div class="date">{{ $msg->created_at->format('M j, g:i A') }}</div>
                        </div>
                    @empty
                        <div style="padding:2rem;text-align:center;color:var(--muted);font-size:0.875rem">
                            No sent messages.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Preview Panel --}}
            <div class="preview">
                <h2 id="previewTitle" style="color:var(--muted);font-weight:400">Select an email</h2>
                <p id="previewMeta" class="preview-meta"></p>
                <p id="previewBody" class="preview-body"></p>
            </div>
        </section>
    </main>
</div>

{{-- Compose Modal --}}
<div id="composeModal" class="modal">
    <div class="modal-card">
        <button class="close" onclick="closeCompose()">×</button>
        <h2 style="margin-bottom:0.5rem">New Message</h2>

        <label>To</label>
        <input id="composeTo" type="email" placeholder="recipient@email.com">

        <label>Subject</label>
        <input id="composeSubject" type="text" placeholder="Email subject">

        <label>Message</label>
        <textarea id="composeBody" placeholder="Write your message…"></textarea>

        <button class="btn primary" onclick="sendEmail()" style="margin-top:0.5rem">Send Message</button>
    </div>
</div>

@push('scripts')
<script>
function showTab(tab, el) {
    document.getElementById('inboxList').style.display = tab === 'inbox' ? '' : 'none';
    document.getElementById('sentList').style.display  = tab === 'sent'  ? '' : 'none';
    document.getElementById('mailTitle').textContent   = tab === 'inbox' ? 'Inbox' : 'Sent';
    document.querySelectorAll('.menu').forEach(m => m.classList.remove('active'));
    el.classList.add('active');
    // Reset preview
    document.getElementById('previewTitle').textContent = 'Select an email';
    document.getElementById('previewMeta').textContent = '';
    document.getElementById('previewBody').textContent = '';
}
</script>
@endpush
@endsection
