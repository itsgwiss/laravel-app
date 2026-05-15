@extends('layouts.app')
@section('title', 'Register — RepoHive')

@section('content')
<div class="brand-block">

    <img src="{{ asset('images/open-enrollment.gif') }}" class="brand-gif" alt="Grace">

    <div class="brand-text">

        <h1 class="brand-title">RepoHive</h1>

        <p class="brand-subtitle">
            Create your account and get access to all RepoHive tools — OTP, mailbox, and AI assistant.
        </p>

    </div>

</div>

    <div class="auth-form-panel">
        <div class="card fade-in">
            <div class="brand">Create account</div>

            @if($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <label for="name">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your full name">

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters">

                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">

                <button type="submit" class="btn primary" style="margin-top:0.5rem">Create account</button>
            </form>

            <p class="note">Already registered? <a class="link" href="{{ route('login') }}" style="display:inline">Sign in</a></p>
        </div>
    </div>
</div>
@endsection
