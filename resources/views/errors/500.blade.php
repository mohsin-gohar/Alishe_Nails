@extends('layouts.app')

@section('title', 'Server Error — Alishe Nails')

@section('content')
<div class="container" style="padding-block:96px;text-align:center;max-width:640px;margin-inline:auto;">
    <div style="font-family:var(--font-serif);font-size:5rem;color:var(--rose-dark);line-height:1;margin-bottom:16px;">500</div>
    <h1 style="font-size:1.8rem;margin-bottom:12px;">Something Went Wrong</h1>
    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6;margin-bottom:32px;">
        We encountered an unexpected technical issue on our end. Please rest assured that our team has been notified. Please try again shortly.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">Browse Shop</a>
    </div>
</div>
@endsection
