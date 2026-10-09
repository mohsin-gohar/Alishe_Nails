@extends('layouts.app')

@section('title', 'Too Many Requests — Alishe Nails')

@section('content')
<div class="container" style="padding-block:96px;text-align:center;max-width:640px;margin-inline:auto;">
    <div style="font-family:var(--font-serif);font-size:5rem;color:var(--rose-dark);line-height:1;margin-bottom:16px;">429</div>
    <h1 style="font-size:1.8rem;margin-bottom:12px;">Too Many Requests</h1>
    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6;margin-bottom:32px;">
        You've made several requests in a short amount of time. Please pause for a moment before trying again.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">Browse Shop</a>
    </div>
</div>
@endsection
