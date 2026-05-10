@extends('layouts.app')
@section('title', 'Sign in — Grace')

@php
    use Illuminate\Support\Facades\Route;
@endphp

@section('content')
<div class="auth-split">
    <div class="auth-brand-panel">
        <div class="brand">Grace App Hub</div>
        <div class="tagline">Your unified<br>workspace.</div>
        <p>Authentication, OTP verification, mailbox, and AI assistant — all in one place.</p>
    </div>

    <div class="auth-form-panel">
        <div class="card fade-in">
            <div class="brand" style="font-size:1.3rem; margin-bottom:.25rem">Sign in</div>
            <p class="muted" style="margin-bottom:.5rem">Welcome back. Enter your credentials to continue.</p>

            @if($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif
            @if(session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label for="email">Email address</label>
                <input id="email" type="email" name="email"
                    value="{{ old('email') }}"
                    required autofocus autocomplete="email"
                    placeholder="you@example.com">

                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                    required autocomplete="current-password"
                    placeholder="••••••••">

                <div style="display:flex;align-items:center;justify-content:space-between;margin:.5rem 0">
                    <label style="display:flex;align-items:center;gap:.4rem;text-transform:none;letter-spacing:0;margin:0;font-size:.8rem;color:var(--muted);cursor:pointer;font-weight:400">
                        <input type="checkbox" name="remember" style="width:auto;margin:0;accent-color:var(--ink)"> Remember me
                    </label>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link" style="display:inline;font-size:.78rem">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn primary">Continue</button>
            </form>

            <hr>
            @if(Route::has('register'))
                <p class="note">Don't have an account? <a href="{{ route('register') }}" class="link" style="display:inline;color:var(--ink);font-weight:500">Create one</a></p>
            @endif
        </div>
    </div>
</div>
@endsection