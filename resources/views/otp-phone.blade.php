@extends('layouts.app')
@section('title', 'SMS OTP — Grace')

@section('content')
<nav class="topnav">
    <div class="brand">
    <img src="{{ asset('images/computer-security.gif') }}" alt="Icon" class="brand-icon">
    <span>Grace App Hub</span>
</div>
</nav>

<div class="center-screen" style="min-height: calc(100vh - 58px)">
    <div class="card fade-in">
        <h1>
    <span class="page-icon">
        <img src="{{ asset('images/hand-holding-smartphone-smart-home.gif') }}" alt="Icon">
    </span>
    Phone Verification
</h1>
        <h1>Send OTP via SMS</h1>
        <p class="muted">Enter your phone number to receive a 6-digit code.</p>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('otp.sendSms') }}">
            @csrf

            <label for="phone">Phone Number</label>
            <input
                id="phone"
                name="phone"
                type="tel"
                placeholder="e.g. 09123456789"
                value="{{ old('phone', Auth::user()->phone ?? '') }}"
                required
            >

            <button type="submit" class="btn primary">Send OTP via SMS</button>
        </form>

        <a class="link" href="{{ route('dashboard') }}">← Back to Dashboard</a>
    </div>
</div>
@endsection
