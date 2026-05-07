@extends('layouts.app')
@section('title', 'Validate OTP — Grace')

@section('content')
<nav class="topnav">
    <a href="{{ route('dashboard') }}" class="brand" style="text-decoration:none">🐝 Grace</a>
</nav>

<div class="center-screen" style="min-height: calc(100vh - 58px)">
    <div class="card fade-in">
        <div class="brand">🔐 OTP Verification</div>
        <h1>Enter your code</h1>
        <p class="muted">
            Enter the 6-digit code sent to
            <strong style="color:var(--text)">{{ session('otp_target', 'your address') }}</strong>.
        </p>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        @if(config('app.debug'))
            <div class="alert info">🧪 Dev mode — check your log for the OTP code.</div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
            @csrf
            {{-- Hidden field assembled by JS --}}
            <input type="hidden" name="otp" id="otpHidden">

            <div class="otp-box">
                @for($i = 0; $i < 6; $i++)
                    <input maxlength="1" class="otp" inputmode="numeric" pattern="[0-9]">
                @endfor
            </div>

            <button type="submit" class="btn primary" id="verifyBtn" onclick="submitOtp(event)">Verify Code</button>
        </form>

        <a class="link" href="{{ route('otp.email') }}">Resend OTP</a>
        <a class="link" href="{{ route('dashboard') }}">← Back to Dashboard</a>
    </div>
</div>

@push('scripts')
<script>
function submitOtp(e) {
    const otp = getOtpValue();
    if (otp.length < 6) {
        e.preventDefault();
        document.getElementById('grace-alert')?.remove();
        const div = document.createElement('div');
        div.id = 'grace-alert';
        div.className = 'alert error';
        div.style.marginTop = '0.5rem';
        div.textContent = 'Please enter all 6 digits.';
        document.getElementById('verifyBtn').before(div);
        return;
    }
    document.getElementById('otpHidden').value = otp;
}
</script>
@endpush
@endsection
