@extends('layouts.app')
@section('title', 'Grace App Hub')

@section('content')
<nav class="topnav">
    <div class="brand">
    <img src="{{ asset('images/computer-security.gif') }}" alt="Icon" class="brand-icon">
    <span>Grace App Hub</span>
</div>
    <div class="nav-actions">
        <span class="user-badge">👤 {{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="logout-btn">Log out</button>
        </form>
    </div>
</nav>

<div class="center-screen" style="min-height: calc(100vh - 58px)">
    <div class="card fade-in" style="max-width:480px">
        <div class="brand">
    <img src="{{ asset('images/computer-security.gif') }}" alt="Icon" class="brand-icon">
    <span>Grace App Hub</span>
</div>

        <h1>Welcome back, {{ auth()->user()->name }}.</h1>
        <p class="muted">Access your verification, mailbox, and AI assistant tools.</p>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <div class="app-grid">
            <a class="app-card" href="{{ route('otp.phone') }}">
    <span class="icon">
        <img src="{{ asset('images/hand-holding-smartphone-smart-home.gif') }}" alt="SMS OTP">
    </span>
    <span class="name">SMS OTP</span>
    <span class="sub">Verify</span>
</a>

<a class="app-card" href="{{ route('otp.email') }}">
    <span class="icon">
        <img src="{{ asset('images/message.gif') }}" alt="Email OTP">
    </span>
    <span class="name">Email OTP</span>
    <span class="sub">Verify</span>
</a>

<a class="app-card" href="{{ route('mailbox') }}">
    <span class="icon">
          <img src="{{ asset('images/mail-box.gif') }}" alt="Mailbox">
    </span>
    <span class="name">Mailbox</span>
    <span class="sub">Messages</span>
</a>

<a class="app-card" href="{{ route('chatbot') }}">
    <span class="icon">
        <img src="{{ asset('images/ai-assistant.gif') }}" alt="AI Chatbot">
    </span>
    <span class="name">AI Chatbot</span>
    <span class="sub">Grace AI</span>
</a>
        </div>

        <hr>

        <p class="note">
            Logged in as <strong>{{ auth()->user()->email }}</strong>
            @if(auth()->user()->hasVerifiedEmail())
                · <span style="color:var(--success)">✓ Verified</span>
            @else
                · <span style="color:var(--error)">Email not verified</span>
            @endif
        </p>
    </div>
</div>
@endsection
