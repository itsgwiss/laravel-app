@extends('layouts.app')
@section('title', 'Email OTP — RepoHive')

@section('content')
<nav class="topnav">
    <div class="brand">
    <img src="{{ asset('images/computer-security.gif') }}" alt="Icon" class="brand-icon">
    <span>RepoHive</span>
</div>
</nav>

<div class="center-screen" style="min-height: calc(100vh - 58px)">
    <div class="card fade-in">
        <h1>
    <span class="page-icon">
        <img src="{{ asset('images/message.gif') }}" alt="Icon">
    </span>
    Email Verification
</h1>
        <h1>Send OTP via Email</h1>
        <p class="muted">A 6-digit code will be sent to your email address.</p>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif
        
@if(session('confirm_reverify'))
<div class="reverify-box">
    <div class="reverify-icon">✅</div>
    <p class="reverify-title">Already Verified</p>
    <p class="reverify-sub">
        <strong>{{ session('reverify_target') }}</strong> has already been verified.
        Do you want to re-verify it?
    </p>
    <form method="POST" action="{{ session('confirm_reverify') === 'email' ? route('otp.sendEmail') : route('otp.sendSms') }}">
        @csrf
        <input type="hidden" name="{{ session('confirm_reverify') === 'email' ? 'email' : 'phone' }}" value="{{ session('reverify_target') }}">
        <input type="hidden" name="force_reverify" value="1">
        <div class="reverify-actions">
            <button type="submit" class="btn primary">Yes, re-verify</button>
            <a href="{{ route('dashboard') }}" class="btn secondary">No, go back</a>
        </div>
    </form>
</div>
@endif

        <form method="POST" action="{{ route('otp.sendEmail') }}">
            @csrf

            <label for="email">Email Address</label>
            <input
                id="email"
                name="email"
                type="email"
                placeholder="you@example.com"
                value="{{ old('email', Auth::user()->email ?? '') }}"
                required
            >

            <button type="submit" class="btn primary">Send OTP via Email</button>
        </form>

        <a class="link" href="{{ route('dashboard') }}">← Back to Dashboard</a>
    </div>
</div>
@endsection
