@extends('layouts.app')
@section('title', 'Order Confirmed — Alishe Nails')

@section('content')
    <div class="container" style="max-width:720px;text-align:center;padding-block:60px 80px;">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(212,175,55,0.15);border:2px solid var(--gold);color:var(--gold);display:inline-flex;align-items:center;justify-content:center;font-size:2.2rem;margin-bottom:16px;">
            <i class="fa-solid fa-check"></i>
        </div>

        <span class="eyebrow" style="display:block;color:var(--rose-dark);font-size:0.8rem;letter-spacing:0.12em;text-transform:uppercase;margin-bottom:6px;">
            Order #{{ $order->order_number }} Successfully Placed
        </span>
        <h1 style="font-size:2.2rem;margin-bottom:10px;">Thank You, {{ $order->first_name }}!</h1>
        <p style="color:rgba(43,29,29,.78);font-size:0.95rem;max-width:540px;margin-inline:auto;line-height:1.6;">
            Your bespoke order has been registered in our atelier queue. To begin handcrafting your custom press-on set, please transfer the order total via SadaPay or JazzCash below.
        </p>

        {{-- ---------- Verified Payment Accounts Box ---------- --}}
        <div style="background:#FAF5F2;border:1.5px solid rgba(212,175,55,0.4);border-radius:var(--radius-lg);padding:24px;margin-top:28px;text-align:left;box-shadow:0 8px 24px rgba(43,29,29,0.06);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid var(--border-soft);padding-bottom:14px;margin-bottom:18px;">
                <div>
                    <h3 style="font-size:1.15rem;margin:0 0 4px;color:var(--espresso);">
                        <i class="fa-solid fa-lock" style="color:var(--gold);margin-right:6px;"></i> 100% Advance Payment Details
                    </h3>
                    <small style="color:#7A6E68;font-size:0.8rem;">Please transfer the exact order amount: <strong>PKR {{ number_format($order->total, 0) }}</strong></small>
                </div>
                <div style="background:var(--blush);color:var(--rose-dark);font-weight:700;font-size:0.85rem;padding:6px 14px;border-radius:20px;">
                    Total: PKR {{ number_format($order->total, 0) }}
                </div>
            </div>

            <div class="payment-accounts-grid">
                {{-- SadaPay --}}
                <div class="payment-account-card card-sadapay">
                    <span class="payment-account-badge">SadaPay</span>
                    <div class="payment-account-number">
                        <span>03113925166</span>
                        <button type="button" class="btn btn-sm btn-copy-number" data-copy-target="03113925166" style="padding:4px 10px;font-size:0.75rem;background:#28B99E;color:#fff;border:none;border-radius:4px;cursor:pointer;">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="payment-account-title">
                        Account Title: <strong>Alishba Naz</strong>
                    </div>
                </div>

                {{-- JazzCash --}}
                <div class="payment-account-card card-jazzcash">
                    <span class="payment-account-badge">JazzCash</span>
                    <div class="payment-account-number">
                        <span>03113925166</span>
                        <button type="button" class="btn btn-sm btn-copy-number" data-copy-target="03113925166" style="padding:4px 10px;font-size:0.75rem;background:#EE2B2B;color:#fff;border:none;border-radius:4px;cursor:pointer;">
                            <i class="fa-regular fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="payment-account-title">
                        Account Title: <strong>Alishba Naz</strong>
                    </div>
                </div>
            </div>

            {{-- Instant Verification Step --}}
            <div style="margin-top:20px;background:#ffffff;border-radius:10px;padding:16px;border:1px solid var(--border-soft);">
                <strong style="display:block;font-size:0.88rem;color:var(--espresso);margin-bottom:6px;">
                    <i class="fa-solid fa-camera" style="color:var(--rose);"></i> Next: Send Payment Screenshot &amp; Order #{{ $order->order_number }}
                </strong>
                <p style="font-size:0.82rem;color:rgba(43,29,29,.75);margin:0 0 14px;line-height:1.5;">
                    After completing the transfer, kindly send a screenshot along with your order number via WhatsApp or Instagram. Our team will verify and begin hand-crafting your set immediately!
                </p>

                @php
                    $waMsg = "Assalam-o-Alaikum! Maine Alishe Nails se order place kiya hai.\n\n*Order Number:* #{$order->order_number}\n*Total Amount:* PKR ".number_format($order->total, 0)."\n*Customer:* {$order->first_name} {$order->last_name}\n\nYe raha mera payment screenshot. Baraye meharbani confirmation dein. Shukriya!";
                @endphp

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="https://wa.me/923113925166?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener" class="btn" style="flex:1;background:#25D366;color:#fff;padding:12px 18px;border-radius:8px;font-weight:600;font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                        <i class="fa-brands fa-whatsapp" style="font-size:1.15rem;"></i> Send Screenshot on WhatsApp
                    </a>
                    <a href="https://ig.me/m/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" class="btn" style="flex:1;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);color:#fff;padding:12px 18px;border-radius:8px;font-weight:600;font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                        <i class="fa-brands fa-instagram" style="font-size:1.15rem;"></i> Send on Instagram (@alishe_nails)
                    </a>
                </div>
            </div>
        </div>

        {{-- ---------- Order Items Summary ---------- --}}
        <div class="summary-card" style="text-align:left;margin-top:28px;">
            <h4 style="margin-bottom:14px;font-size:1rem;color:var(--espresso);">Order Summary</h4>
            @foreach ($order->items as $item)
                <div class="summary-row" style="font-size:0.88rem;">
                    <span>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->shape)<span style="color:#7A6E68;"> &bull; {{ $item->shape }}</span>@endif
                        @if($item->size)<span style="color:#7A6E68;"> &bull; Size: {{ $item->size }}</span>@endif
                        <span style="color:var(--rose-dark);font-weight:600;"> &times; {{ $item->quantity }}</span>
                    </span>
                    <span>PKR {{ number_format($item->line_total, 0) }}</span>
                </div>
            @endforeach
            <div class="summary-row total">
                <span>Grand Total</span>
                <span style="color:var(--rose-dark);">PKR {{ number_format($order->total, 0) }}</span>
            </div>
            <div style="margin-top:14px;font-size:0.75rem;color:#7A6E68;line-height:1.4;">
                <i class="fa-solid fa-truck-fast"></i> Exclusively delivering across Karachi (48–96 hrs doorstep delivery).<br>
                <i class="fa-solid fa-ban"></i> Due to handcrafted hygiene standards, press-on nails are strictly non-returnable.
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin-top:28px;flex-wrap:wrap;">
            <a href="{{ route('shop.index') }}" class="btn btn-outline">Continue Shopping</a>
            <a href="{{ route('track-order.index') }}" class="btn btn-primary">Track Order Status</a>
        </div>
    </div>
@endsection
