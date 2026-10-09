@extends('layouts.app')

@section('title', 'Page Expired — Alishe Nails')

@section('content')
<div class="container" style="padding-block:96px;text-align:center;max-width:640px;margin-inline:auto;">
    <div style="font-family:var(--font-serif);font-size:5rem;color:var(--rose-dark);line-height:1;margin-bottom:16px;">419</div>
    <h1 style="font-size:1.8rem;margin-bottom:12px;">Session Expired</h1>
    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6;margin-bottom:32px;">
        Your security session has expired due to inactivity. Please refresh the page and try your submission again.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <button type="button" onclick="window.location.reload();" class="btn btn-primary">Refresh Page</button>
        <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
    </div>
</div>
@endsection
