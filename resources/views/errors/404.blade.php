@extends('layouts.app')

@section('title', 'Page Not Found — Alishe Nails')

@section('content')
<div class="container" style="padding-block:96px;text-align:center;max-width:640px;margin-inline:auto;">
    <div style="font-family:var(--font-serif);font-size:5rem;color:var(--rose-dark);line-height:1;margin-bottom:16px;">404</div>
    <h1 style="font-size:1.8rem;margin-bottom:12px;">Page Not Found</h1>
    <p style="color:var(--muted);font-size:1.05rem;line-height:1.6;margin-bottom:32px;">
        The nail set or page you are looking for may have moved or is no longer available. Explore our curated collections to find your perfect match.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">Browse Shop &rarr;</a>
    </div>
</div>
@endsection
