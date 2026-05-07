@extends('layouts.app')
@section('title', 'Login — Grace')

@section('content')
<div class="auth-split">
    <div class="auth-brand-panel">
        <div class="brand" style="font-size:2rem">🐝 Grace</div>
        <div class="tagline">Your <em>unified</em><br>workspace.</div>
        <p>Authentication, OTP verification, mailbox, and AI assistant — all in one place.</p>
    </div>

    <div class="auth-form-panel">
        <div class="card fade-in">
            <div class="brand">Sign in</div>

            @if($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif
            @if(session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com">

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">

                <div style="display:flex;align-items:center;justify-content:space-between;margin:0.25rem 0">
                    <label style="display:flex;align-items:center;gap:0.4rem;text-transform:none;letter-spacing:0;margin:0;font-size:0.85rem;color:var(--muted);cursor:pointer">
                        <input type="checkbox" name="remember" style="width:auto;margin:0"> Remember me
                    </label>
                    @if(Route::has('password.request'))
                        <a class="link" href="{{ route('password.request') }}" style="display:inline;font-size:0.82rem">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn primary" style="margin-top:0.5rem">Sign in</button>
            </form>

            @if(Route::has('register'))
                <p class="note">No account? <a class="link" href="{{ route('register') }}" style="display:inline">Create one</a></p>
            @endif
        </div>
    </div>
</div>
@endsection
