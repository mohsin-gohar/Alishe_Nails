@extends('layouts.admin')
@section('title', $seller->name.' — Seller')
@section('content')
<div class="admin-page-head">
    <div>
        <h1>{{ $seller->name }}</h1>
        <p>{{ $seller->email }} · {{ $seller->instagram_handle ?: 'No Instagram handle' }}</p>
    </div>
    <a href="{{ route('admin.marketplace.sellers.index') }}" class="btn btn-outline">Back to Sellers</a>
</div>

<div class="admin-card">
    <h2>Application details</h2>
    <p style="white-space:pre-line;">{{ $seller->product_details }}</p>
    <p><strong>Status:</strong> {{ ucfirst($seller->status) }}</p>
</div>

{{-- Subscription verification (replaces the removed Subscriptions screen) --}}
<div class="admin-card" style="margin-top:24px;">
    <h2>Subscription</h2>

    @forelse($subscriptions as $subscription)
        <div class="admin-table__row" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-soft);">
            <div>
                <strong>{{ $subscription->plan->name ?? 'Plan removed' }}</strong>
                <small style="display:block;opacity:.7;">
                    PKR {{ number_format((float) $subscription->price, 0) }}
                    · {{ ucfirst(str_replace('_', ' / ', (string) $subscription->payment_method)) }}
                    · {{ $subscription->created_at?->format('d M Y') }}
                </small>
                <small style="display:block;opacity:.7;">Ref: {{ $subscription->transaction_reference ?: '—' }}</small>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="badge">{{ ucfirst($subscription->status) }}</span>
                @if ($subscription->expires_at)
                    <small style="opacity:.7;">Expires {{ $subscription->expires_at->format('d M Y') }}</small>
                @endif
                @if ($subscription->status === 'pending')
                    <form action="{{ route('admin.marketplace.sellers.subscription.activate', $seller) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-primary btn-sm" type="submit">Activate</button>
                    </form>
                    <form action="{{ route('admin.marketplace.sellers.subscription.reject', $seller) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-outline btn-sm" type="submit">Reject</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p style="opacity:.75;">This seller has not submitted a subscription payment.</p>
    @endforelse
</div>

<div class="admin-card" style="margin-top:24px;overflow-x:auto;">
    <h2>Seller orders</h2>
    <table class="admin-table">
        <thead>
            <tr><th>Order</th><th>Product</th><th>Quantity</th><th>Sale total</th></tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>#{{ $item->order->order_number }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>PKR {{ number_format($item->line_total, 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No seller orders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $items->links() }}
</div>
@endsection