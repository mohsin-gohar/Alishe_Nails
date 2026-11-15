@extends('layouts.app')
@section('title', 'Your Cart — Alishe Nails')
@section('robots', 'noindex,nofollow')

@section('content')

    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a> &nbsp;&gt;&nbsp; <span>Cart</span>
        </div>

        @if (empty($items))
            <div class="cart-empty">
                <i class="fa-solid fa-bag-shopping" style="font-size:2.4rem;color:var(--rose-dark);"></i>
                <h1 style="margin-top:16px;font-size:1.6rem;">Your cart is empty</h1>
                <p>Looks like you haven't added anything yet.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">Shop the Collection</a>
            </div>
        @else
            <div class="cart-layout">
                <div>
                    <h1 style="font-size:1.6rem;">Shopping Cart</h1>
                    @foreach ($items as $rowId => $item)
                        <div class="cart-item">
                            @php
                                $unitPrice = $prices[$item['product_id']] ?? (float) $item['price'];
                                $maxQty = max(1, min((int) ($stock[$item['product_id']] ?? 20), 20));
                                $url = $item['image'] && file_exists(public_path('images/products/'.$item['image'])) ? \App\Support\ImageUrl::relativeUrl('images/products/'.$item['image']) : null;
                            @endphp
                            <div class="cart-item__image">
                                @if ($url)
                                    <x-optimized-image :src="$url" :alt="$item['name']" :fallback="true" />
                                @else
                                    <div class="img-placeholder" style="font-size:.6rem;">Photo coming soon</div>
                                @endif
                            </div>

                            <div>
                                <div class="cart-item__name">{{ $item['name'] }}</div>
                                <div class="cart-item__meta">
                                    @if ($item['shape']) Shape: {{ $item['shape'] }} @endif
                                    @if ($item['size']) &middot; Size: {{ $item['size'] }} @endif
                                </div>
                                <div class="cart-item__meta">PKR {{ number_format($unitPrice, 0) }}</div>

                                <form action="{{ route('cart.remove', $rowId) }}" method="POST" style="margin-top:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cart-item__remove" aria-label="Remove {{ $item['name'] }} from cart">Remove</button>
                                </form>
                            </div>

                            <form action="{{ route('cart.update', $rowId) }}" method="POST" class="cart-item__qty">
                                @csrf
                                @method('PATCH')
                                <div class="qty-selector" data-qty-selector data-auto-submit="true">
                                    <button type="button" data-action="decrease" aria-label="Decrease quantity for {{ $item['name'] }}">&minus;</button>
                                    <input type="number" name="qty" value="{{ $item['qty'] }}" min="0" max="{{ $maxQty }}" aria-label="Quantity for {{ $item['name'] }}">
                                    <button type="button" data-action="increase" aria-label="Increase quantity for {{ $item['name'] }}">&plus;</button>
                                </div>
                            </form>

                            <div class="cart-item__price" style="font-weight:600;">
                                PKR {{ number_format($unitPrice * $item['qty'], 0) }}
                            </div>
                        </div>
                    @endforeach

                    <a href="{{ route('shop.index') }}" style="display:inline-block;margin-top:20px;text-decoration:underline;">&larr; Continue Shopping</a>
                </div>

                <div class="summary-card">
                    <h3 style="margin-bottom:20px;">Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>PKR {{ number_format($subtotal, 0) }}</span></div>
                    <div class="summary-row">
                        <span>Delivery (Karachi)</span>
                        <span style="font-size:.9rem;text-align:right;color:var(--espresso);font-weight:600;">
                            @if ($shipping > 0)
                                PKR {{ number_format($shipping, 0) }}
                            @else
                                Free
                            @endif
                        </span>
                    </div>
                    <div class="summary-row total">
                        <span>Estimated Total</span>
                        <span>PKR {{ number_format($total, 0) }}</span>
                    </div>
                    <p style="font-size:.8rem;color:rgba(43,29,29,.65);margin:12px 0 0;">
                        <i class="fa-solid fa-truck-fast"></i> Flat Karachi doorstep delivery. Your exact zone is confirmed at checkout.
                    </p>

                    <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-block" style="margin-top:20px;">Proceed to Checkout</a>

                    <a href="https://ig.me/m/alishe_nails" target="_blank" rel="noopener" class="btn btn-block" style="margin-top:10px;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);color:#fff;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:600;padding:12px;border-radius:10px;text-decoration:none;">
                        <i class="fa-brands fa-instagram" style="font-size:1.15rem;"></i> Order / Inquire via Instagram (@alishe_nails)
                    </a>
                </div>
            </div>
        @endif
    </div>

@endsection
