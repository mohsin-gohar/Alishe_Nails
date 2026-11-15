@extends('layouts.admin')
@section('title', 'Marketplace — Alishe Nails Admin')
@section('content')
<div class="admin-page-head"><div><h1>Marketplace</h1><p>Seller marketplace billing is now managed through subscriptions.</p></div><a href="{{ route('admin.marketplace.sellers.index') }}" class="btn btn-primary">Manage Sellers</a></div>
<p style="opacity:.8;">Subscription payments are verified on each seller's page (Sellers → Details → Subscription).</p>
@endsection
