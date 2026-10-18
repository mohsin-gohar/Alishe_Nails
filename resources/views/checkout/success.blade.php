@extends('layouts.app')
@section('title', 'Order Confirmed — Alishe Nails')

@section('content')
    <div class="container" style="max-width:640px;text-align:center;padding-block:80px;">
        <i class="fa-solid fa-circle-check" style="font-size:3rem;color:var(--rose);"></i>
        <h1 style="margin-top:20px;">Thank you, {{ $order->first_name }}!</h1>
        <p>Your order <strong>#{{ $order->order_number }}</strong> has been placed successfully. A confirmation has been logged and our team will reach out to confirm delivery.</p>

        <div class="summary-card" style="text-align:left;margin-top:32px;">
            @foreach ($order->items as $item)
                <div class="summary-row">
                    <span>{{ $item->product_name }} @if($item->shape)({{ $item->shape }})@endif @if($item->size)({{ $item->size }})@endif &times; {{ $item->quantity }}</span>
                    <span>PKR {{ number_format($item->line_total, 0) }}</span>
                </div>
            @endforeach
            <div class="summary-row total">
                <span>Total</span><span>PKR {{ number_format($order->total, 0) }}</span>
            </div>
        </div>

        <div class="instagram-success-card" style="margin-top:28px;background:linear-gradient(135deg,#FFF5F7 0%,#FFF0EE 100%);border:1.5px solid #FBCFE8;border-radius:12px;padding:24px;text-align:center;">
            <span style="background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);color:#fff;width:48px;height:48px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:12px;box-shadow:0 4px 12px rgba(214,36,159,0.3);">
                <i class="fa-brands fa-instagram"></i>
            </span>
            <h3 style="font-size:1.2rem;margin:0 0 8px;color:var(--espresso);">Final Step: Confirm Sizing on Instagram</h3>
            <p style="font-size:0.88rem;color:#5c4747;line-height:1.6;margin:0 0 18px;max-width:520px;margin-inline:auto;">
                Please send a quick message to our Instagram <strong><a href="https://instagram.com/alishe_nails" target="_blank" rel="noopener" style="color:#C13584;text-decoration:underline;">@alishe_nails</a></strong> with your <strong>Order #{{ $order->order_number }}</strong> and payment screenshot. We will verify your nail sizes and begin handcrafting your custom set!
            </p>
            <a href="https://ig.me/m/alishe_nails" target="_blank" rel="noopener" class="btn" style="background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);color:#fff;padding:12px 28px;border-radius:30px;font-weight:600;display:inline-flex;align-items:center;gap:8px;text-decoration:none;box-shadow:0 4px 14px rgba(214,36,159,0.35);">
                <i class="fa-brands fa-instagram" style="font-size:1.15rem;"></i> Send Order #{{ $order->order_number }} to @alishe_nails
            </a>
        </div>

        <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin-top:28px;">
            <a href="{{ route('shop.index') }}" class="btn btn-outline">Continue Shopping</a>
            <a href="{{ route('track-order.index') }}" class="btn btn-primary">Track Order</a>
        </div>
    </div>
@endsection
