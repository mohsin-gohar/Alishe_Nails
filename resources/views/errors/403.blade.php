@extends('layouts.app')

@section('title', 'Access Forbidden — Alishe Nails')

@section('content')
<div class="container" style="padding-block:96px;text-align:center;max-width:640px;margin-inline:auto;">
    <div style="font-family:var(--font-serif);font-size:5rem;color:var(--rose-dark);line-height:1;margin-bottom:16px;">403</div>
    <h1 style="font-size:1.8rem;margin-bottom:12px;">Access Restricted</h1>
    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6;margin-bottom:32px;">
        You don't have permission to access this page or resource. If you believe this is in error, please sign in with the appropriate account.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
        <a href="{{ route('login') }}" class="btn btn-primary">Sign In &rarr;</a>
    </div>
</div>
@endsection
