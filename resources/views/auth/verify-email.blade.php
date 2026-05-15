@extends('layouts.app')
@section('title', 'Verify Email — RepoHive')

@section('content')
<div class="center-screen">
    <div class="card fade-in">
        <div class="brand">📧 Email Verification</div>
        <h1>Verify your email</h1>
        <p class="muted">Thanks for signing up! Please verify your email address by clicking the link we just sent you.</p>

        @if(session('status') === 'verification-link-sent')
            <div class="alert success">A new verification link has been sent to your email address.</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn primary">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn light">Log out</button>
        </form>
    </div>
</div>
@endsection
