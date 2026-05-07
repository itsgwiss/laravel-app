@extends('layouts.app')
@section('title', 'Grace App Hub')

@section('content')
<nav class="topnav">
    <span class="brand">🐝 Grace</span>
    <div class="nav-actions">
        <span class="user-badge">👤 {{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="logout-btn">Log out</button>
        </form>
    </div>
</nav>

<div class="center-screen" style="min-height: calc(100vh - 58px)">
    <div class="card fade-in" style="max-width:480px">
        <div class="brand">🐝 Grace App Hub</div>

        <h1>Welcome back, {{ Auth::user()->name }}.</h1>
        <p class="muted">Access your verification, mailbox, and AI assistant tools.</p>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <div class="app-grid">
            <a class="app-card" href="{{ route('otp.phone') }}">
                <span class="icon">📱</span>
                <span>SMS OTP</span>
                <span class="label">Verify</span>
            </a>
            <a class="app-card" href="{{ route('otp.email') }}">
                <span class="icon">📧</span>
                <span>Email OTP</span>
                <span class="label">Verify</span>
            </a>
            <a class="app-card" href="{{ route('mailbox') }}">
                <span class="icon">📬</span>
                <span>Mailbox</span>
                <span class="label">Messages</span>
            </a>
            <a class="app-card" href="{{ route('chatbot') }}">
                <span class="icon">🤖</span>
                <span>AI Chatbot</span>
                <span class="label">Grace AI</span>
            </a>
        </div>

        <hr>

        <p class="note">
            Logged in as <strong>{{ Auth::user()->email }}</strong>
            @if(Auth::user()->hasVerifiedEmail())
                · <span style="color:var(--success)">✓ Verified</span>
            @else
                · <span style="color:var(--error)">Email not verified</span>
            @endif
        </p>
    </div>
</div>
@endsection
