@extends('layouts.app')
@section('title', 'Forgot Password — Alishe Nails')

@section('content')
<div class="container" style="max-width:440px;padding-block:56px;">
    <h1 style="text-align:center;">Reset Your Password</h1>
    <p style="text-align:center;color:rgba(43,29,29,.65);margin-bottom:28px;">Enter the email associated with your customer account and we'll send you a reset link.</p>

    @if (session('direct_reset_url'))
        <div style="background:#fff6f4;border:1px solid var(--rose);border-radius:var(--radius-sm);padding:16px 20px;margin-bottom:24px;text-align:center;">
            <p style="margin:0 0 12px;font-size:.88rem;color:var(--espresso);">
                <i class="fa-solid fa-key" style="color:var(--rose-dark);margin-right:6px;"></i>
                <strong>Reset Link Generated:</strong>
            </p>
            <a href="{{ session('direct_reset_url') }}" class="btn btn-primary btn-sm" style="display:inline-block;padding:8px 18px;">
                Proceed to Reset Password &rarr;
            </a>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" class="checkout-card">
        @csrf
        <div class="form-field" style="margin-bottom:20px;">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="e.g. demo@alishenails.com" required autofocus>
            @error('email') <div class="error" style="color:var(--rose-dark);margin-top:6px;font-size:.82rem;">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
    </form>

    <div style="display:flex;justify-content:space-between;margin-top:24px;font-size:.88rem;">
        <a href="{{ route('login') }}" style="text-decoration:underline;color:var(--rose-dark);"><i class="fa-solid fa-arrow-left" style="font-size:.75rem;"></i> Back to Login</a>
        <a href="{{ route('register') }}" style="text-decoration:underline;">Create Account</a>
    </div>
</div>
@endsection

