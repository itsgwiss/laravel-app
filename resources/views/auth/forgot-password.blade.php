@extends('layouts.app')
@section('title', 'Forgot Password — Grace')

@section('content')
<div class="center-screen">
    <div class="card fade-in">
        <div class="brand">🔑 Password Reset</div>
        <h1>Forgot password?</h1>
        <p class="muted">Enter your email and we'll send you a reset link.</p>

        @if(session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            <button type="submit" class="btn primary">Send Reset Link</button>
        </form>

        <a class="link" href="{{ route('login') }}">← Back to login</a>
    </div>
</div>
@endsection
