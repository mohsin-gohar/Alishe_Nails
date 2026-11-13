@extends('layouts.admin')
@section('title', 'Sellers — Alishe Nails Admin')
@section('content')
<div class="admin-page-head">
    <div>
        <h1>Sellers</h1>
        <p>All influencer seller applications.</p>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline">Subscriptions</a>
</div>
<div class="admin-card" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Instagram</th>
                <th>Status</th>
                <th>Products</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sellers as $seller)
                <tr>
                    <td>
                        <strong>{{ $seller->name }}</strong>
                        <small style="display:block;opacity:.65;">{{ $seller->email }}</small>
                    </td>
                    <td>{{ $seller->instagram_handle ?: '—' }}</td>
                    <td><span class="status-pill status-{{ $seller->status }}">{{ ucfirst($seller->status) }}</span></td>
                    <td>{{ $seller->products_count }}</td>
                    <td><a href="{{ route('admin.marketplace.sellers.show', $seller) }}" class="btn btn-outline btn-sm">Details</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:32px 16px;color:rgba(43,29,29,.75);">
                        No seller applications received yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($sellers->hasPages())
        <div style="margin-top:16px;">{{ $sellers->links() }}</div>
    @endif
</div>
@endsection
