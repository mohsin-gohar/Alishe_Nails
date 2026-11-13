@extends('layouts.app')
@section('title', 'Checkout — Alishe Nails')
@section('robots', 'noindex,nofollow')

@section('content')

    <div class="container checkout-page-wrapper">
        {{-- ---------- Checkout Header & Breadcrumbs ---------- --}}
        <div class="checkout-header-bar">
            <div class="checkout-breadcrumbs">
                <a href="{{ route('home') }}"><i class="fa-solid fa-house" style="font-size:0.75rem;"></i> Home</a>
                <span>&gt;</span>
                <a href="{{ route('cart.index') }}"><i class="fa-solid fa-bag-shopping" style="font-size:0.75rem;"></i> Cart</a>
                <span>&gt;</span>
                <span class="active"><strong>Checkout</strong></span>
            </div>
            <div class="checkout-security-pill">
                <i class="fa-solid fa-shield-halved"></i>
                <span>256-Bit SSL Encrypted Checkout</span>
            </div>
        </div>

        <div class="checkout-layout">
            {{-- ======================================================== --}}
            {{-- LEFT COLUMN: Main Checkout Form                           --}}
            {{-- ======================================================== --}}
            <div class="checkout-main">
                <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST">
                    @csrf

                    {{-- ---------------- STEP 1: Shipping & Contact ---------------- --}}
                    <div class="checkout-card">
                        <div class="checkout-card__header">
                            <h2 class="checkout-card__title">
                                <span class="step-badge">1</span>
                                <span>Contact &amp; Delivery Information</span>
                            </h2>
                            <span style="font-size:0.78rem;color:#6b5a5c;font-weight:500;">Step 1 of 2</span>
                        </div>

                        @guest('web')
                            <div class="guest-login-prompt">
                                <i class="fa-regular fa-user" style="color:var(--rose-dark);font-size:1.05rem;"></i>
                                <div>
                                    Already have an Alishe Nails account?
                                    <a href="{{ route('login') }}">Log in</a> or <a href="{{ route('register') }}">Create an account</a> for faster checkout and order tracking.
                                </div>
                            </div>
                        @endguest

                        <div class="form-grid">
                            <div class="form-field full">
                                <label for="email">Email Address <span style="color:#b3261e;">*</span></label>
                                <input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email', $user?->email) }}" required autocomplete="email">
                                <small class="field-hint">We'll send your order confirmation and invoice here.</small>
                                @error('email') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="first_name">First Name <span style="color:#b3261e;">*</span></label>
                                <input type="text" id="first_name" name="first_name" placeholder="Jane" value="{{ old('first_name', $user ? explode(' ', $user->name)[0] : '') }}" required autocomplete="given-name">
                                @error('first_name') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="last_name">Last Name <span style="color:#b3261e;">*</span></label>
                                <input type="text" id="last_name" name="last_name" placeholder="Doe" value="{{ old('last_name') }}" required autocomplete="family-name">
                                @error('last_name') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field full">
                                <label for="address">Street Address / House / Flat <span style="color:#b3261e;">*</span></label>
                                <input type="text" id="address" name="address" placeholder="House/Apartment #, Building, Street, Landmark" value="{{ old('address') }}" required autocomplete="street-address">
                                @error('address') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="city">City</label>
                                <div style="position:relative;">
                                    <input type="text" id="city" name="city" value="Karachi" readonly style="background-color:#F5EBE6;cursor:not-allowed;font-weight:600;color:var(--espresso);padding-right:110px;" required>
                                    <span class="karachi-lock-badge">
                                        <i class="fa-solid fa-location-dot"></i> Karachi Only
                                    </span>
                                </div>
                                <small class="field-hint">
                                    <i class="fa-solid fa-circle-info"></i> Delivering exclusively across Karachi.
                                </small>
                                @error('city') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="area">Karachi Delivery Zone / Area <span style="color:#b3261e;">*</span></label>
                                <select id="area" name="area" required>
                                    <option value="">Select your area (e.g. DHA, Gulshan, Clifton...)</option>
                                    @php
                                        $karachiZones = [
                                            'DHA (Defence)',
                                            'Clifton',
                                            'Gulshan-e-Iqbal',
                                            'Gulistan-e-Johar',
                                            'PECHS / Tariq Road / Bahadurabad',
                                            'Saddar / Cantt / Garden',
                                            'Federal B Area (F.B Area)',
                                            'Nazimabad / North Nazimabad',
                                            'North Karachi / New Karachi',
                                            'Korangi / Landhi',
                                            'Shah Faisal Colony',
                                            'Malir / Model Colony',
                                            'Scheme 33 / Gulshan-e-Maymar',
                                            'Orangi / Site / Baldia',
                                            'Bahria Town Karachi',
                                            'Other Karachi Areas',
                                        ];
                                        $currentArea = old('area', '');
                                    @endphp
                                    @foreach($karachiZones as $kZone)
                                        <option value="{{ $kZone }}" {{ $currentArea === $kZone ? 'selected' : '' }}>{{ $kZone }}</option>
                                    @endforeach
                                </select>
                                <small class="field-hint">Select your area for accurate delivery coordination.</small>
                                @error('area') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="postal_code">Postal Code <span style="font-weight:400;opacity:.65;">(Optional)</span></label>
                                <input type="text" id="postal_code" name="postal_code" placeholder="e.g. 75500" value="{{ old('postal_code') }}" autocomplete="postal-code">
                                @error('postal_code') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="phone">Phone Number <span style="color:#b3261e;">*</span></label>
                                <input type="tel" id="phone" name="phone" placeholder="+92 300 1234567" value="{{ old('phone', $user?->phone) }}" required autocomplete="tel">
                                <small class="field-hint">Courier will contact this number before doorstep delivery.</small>
                                @error('phone') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            <div class="form-field full">
                                <label for="payment_notes">Order Note / Custom Nail Sizing Notes <span style="font-weight:400;opacity:.65;">(Optional)</span></label>
                                <input type="text" id="payment_notes" name="payment_notes" value="{{ old('payment_notes') }}" placeholder="e.g. Nail shape/size notes or delivery instructions">
                                @error('payment_notes') <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ---------------- STEP 2: Delivery & Payment ---------------- --}}
                    <div class="checkout-card">
                        <div class="checkout-card__header">
                            <h2 class="checkout-card__title">
                                <span class="step-badge">2</span>
                                <span>Delivery &amp; Payment</span>
                            </h2>
                            <span style="font-size:0.78rem;color:#6b5a5c;font-weight:500;">Step 2 of 2</span>
                        </div>

                        <div class="shipping-method-card">
                            <div class="shipping-method-card__left">
                                <input type="radio" checked readonly style="accent-color:var(--rose-dark);width:18px;height:18px;">
                                <div>
                                    <div class="shipping-method-card__title">Karachi Express Doorstep Delivery (2–4 Business Days)</div>
                                    <div class="shipping-method-card__sub">Carefully handcrafted, quality-tested &amp; packed in signature gift box.</div>
                                </div>
                            </div>
                            <span class="shipping-method-card__badge">Doorstep</span>
                        </div>

                        <div style="margin-top:22px;">
                            <h3 style="font-size:0.88rem;letter-spacing:0.06em;text-transform:uppercase;color:#6B5C56;font-weight:600;margin-bottom:12px;">
                                Preferred Payment Method
                            </h3>

                            <label class="payment-option {{ old('payment_method', 'sadapay') === 'sadapay' ? 'is-selected' : '' }}">
                                <span class="payment-option__left">
                                    <input type="radio" name="payment_method" value="sadapay" {{ old('payment_method', 'sadapay') === 'sadapay' ? 'checked' : '' }}>
                                    <span>
                                        <span class="payment-brand-badge payment-brand-badge--sadapay">SadaPay</span>
                                        <span style="display:block;font-size:0.8rem;color:#6b5a5c;margin-top:4px;">Transfer via SadaPay app or bank transfer</span>
                                    </span>
                                </span>
                                <i class="fa-solid fa-arrow-right" style="color:#C7B8B0;"></i>
                            </label>

                            <label class="payment-option {{ old('payment_method') === 'jazzcash' ? 'is-selected' : '' }}">
                                <span class="payment-option__left">
                                    <input type="radio" name="payment_method" value="jazzcash" {{ old('payment_method') === 'jazzcash' ? 'checked' : '' }}>
                                    <span>
                                        <span class="payment-brand-badge payment-brand-badge--jazzcash">JazzCash</span>
                                        <span style="display:block;font-size:0.8rem;color:#6b5a5c;margin-top:4px;">Transfer via JazzCash mobile account</span>
                                    </span>
                                </span>
                                <i class="fa-solid fa-arrow-right" style="color:#C7B8B0;"></i>
                            </label>

                            <p style="font-size:0.78rem;color:#6b5a5c;margin:10px 0 0;line-height:1.5;">
                                <i class="fa-solid fa-circle-info" style="color:var(--rose-dark);"></i>
                                Your Order ID and the verified payment account details will be shown on the confirmation screen right after you place the order.
                            </p>
                        </div>
                    </div>

                    {{-- Advance Payment Notice Card --}}
                    <div style="background:#FAF5F2;border:1.5px solid rgba(212,175,55,0.4);border-radius:var(--radius-md);padding:18px 20px;margin-top:20px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <i class="fa-solid fa-lock" style="color:var(--gold);font-size:1.1rem;"></i>
                            <strong style="font-size:0.92rem;color:var(--espresso);">100% Advance Payment Policy</strong>
                        </div>
                        <p style="font-size:0.82rem;color:rgba(43,29,29,.78);margin:0;line-height:1.5;">
                            All sets are custom handcrafted to your selected shape and size. We accept advance payments via <strong>SadaPay</strong> and <strong>JazzCash</strong> (<code>03113925166</code> &middot; Alishba Naz). Your unique Order ID and payment accounts will be provided on the next confirmation screen.
                        </p>
                    </div>

                    {{-- Bottom Trust Assurances --}}
                    <div class="checkout-trust-grid">
                        <div class="checkout-trust-card">
                            <i class="fa-solid fa-truck-fast"></i>
                            <strong>Karachi Express</strong>
                            <small>48-96h doorstep delivery</small>
                        </div>
                        <div class="checkout-trust-card">
                            <i class="fa-solid fa-hand-sparkles"></i>
                            <strong>100% Handcrafted</strong>
                            <small>Custom made for you</small>
                        </div>
                        <div class="checkout-trust-card">
                            <i class="fa-solid fa-shield-halved"></i>
                            <strong>Instant Verification</strong>
                            <small>JazzCash &amp; SadaPay secured</small>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ======================================================== --}}
            {{-- RIGHT COLUMN: Sticky Order Summary & Coupon               --}}
            {{-- ======================================================== --}}
            <div class="checkout-sidebar">
                <div class="checkout-summary-card">
                    <h3>
                        <span>Order Summary</span>
                        <span style="font-size:0.78rem;background:#FAF5F2;padding:4px 10px;border-radius:12px;font-family:var(--font-sans);font-weight:600;color:var(--espresso);border:1px solid #ECE4DF;">
                            {{ count($items) }} {{ count($items) === 1 ? 'item' : 'items' }}
                        </span>
                    </h3>

                    {{-- Products List --}}
                    <div class="order-items-scroll">
                        @foreach ($items as $item)
                            <div class="order-summary-item">
                                <div class="order-summary-item__image-wrap">
                                    <div class="order-summary-item__image">
                                        @php
                                            $url = $item['image'] && file_exists(public_path('images/products/'.$item['image'])) ? asset('images/products/'.$item['image']) : null;
                                        @endphp
                                        @if ($url)
                                            <x-optimized-image :src="$url" :alt="$item['name']" :fallback="true" />
                                        @else
                                            <div class="img-placeholder" style="font-size:.55rem;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;">Alishe</div>
                                        @endif
                                    </div>
                                    <span class="order-summary-item__qty-badge">{{ $item['qty'] }}</span>
                                </div>

                                <div style="flex:1;min-width:0;">
                                    <div class="order-summary-item__name">{{ $item['name'] }}</div>
                                    <div class="order-summary-item__meta">
                                        @if ($item['size'])
                                            <span>Size: <strong>{{ $item['size'] }}</strong></span>
                                        @endif
                                        @if ($item['shape'])
                                            <span>&bull; Shape: <strong>{{ $item['shape'] }}</strong></span>
                                        @endif
                                    </div>
                                    <div style="font-size:0.75rem;color:#6b5a5c;margin-top:2px;">
                                        PKR {{ number_format($item['price'], 0) }} &times; {{ $item['qty'] }}
                                    </div>
                                </div>

                                <div class="order-summary-item__price">
                                    PKR {{ number_format($item['price'] * $item['qty'], 0) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Promo Code --}}
                    <div class="checkout-coupon">
                        @if ($coupon)
                            <div class="checkout-coupon__applied">
                                <span><i class="fa-solid fa-tag"></i> <strong>{{ $coupon->code }}</strong> applied</span>
                                <form action="{{ route('checkout.coupon.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="checkout-coupon__remove">Remove</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('checkout.coupon.apply') }}" method="POST" class="checkout-coupon__form">
                                @csrf
                                <label for="coupon-code" class="sr-only">Promo code</label>
                                <input type="text" id="coupon-code" name="code" placeholder="Promo code (e.g. WELCOME10)" autocomplete="off" required>
                                <button type="submit">Apply</button>
                            </form>
                            @error('code') <div class="error" style="color:#B3261E;font-size:0.76rem;margin-top:6px;font-weight:500;">{{ $message }}</div> @enderror
                        @endif
                    </div>

                    {{-- Cost Calculation Breakdown --}}
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong style="color:var(--espresso);">PKR {{ number_format($subtotal, 0) }}</strong>
                    </div>

                    <div class="summary-row">
                        <span>Delivery (Karachi)</span>
                        <span id="checkout-shipping-amount" style="font-size:0.88rem;color:var(--espresso);font-weight:600;">
                            @if ($shipping > 0)
                                PKR {{ number_format($shipping, 0) }}
                            @else
                                Coordinated directly
                            @endif
                        </span>
                    </div>

                    @if ($coupon && $discount > 0)
                        <div class="summary-row" style="color:#2E7D32;">
                            <span>Discount ({{ $coupon->code }})</span>
                            <strong>- PKR {{ number_format($discount, 0) }}</strong>
                        </div>
                    @endif

                    <div class="summary-row total">
                        <div>
                            <span>Total Amount</span>
                            <small>Inclusive of all delivery coordination</small>
                        </div>
                        <span id="checkout-total-amount" style="font-family:var(--font-serif);font-size:1.35rem;color:var(--espresso);">
                            PKR {{ number_format($total, 0) }}
                        </span>
                    </div>

                    {{-- Primary Checkout Button (Submits #checkout-form) --}}
                    <button type="submit" form="checkout-form" id="btn-place-order" class="btn-checkout-primary">
                        <i class="fa-solid fa-lock"></i>
                        <span>Place Order &bull; <span id="checkout-btn-total">PKR {{ number_format($total, 0) }}</span></span>
                    </button>

                    <div style="text-align:center;margin-top:12px;font-size:0.74rem;color:#6b5a5c;">
                        <i class="fa-solid fa-shield-check" style="color:#2E7D32;"></i> Safe checkout &amp; fast doorstep dispatch across Karachi.
                    </div>

                    {{-- Trust Badges --}}
                    <div class="trust-badges">
                        <div class="trust-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            <div>
                                <strong>Secure Checkout</strong>
                                <small>Safe encrypted order submission.</small>
                            </div>
                        </div>
                        <div class="trust-badge">
                            <i class="fa-solid fa-award"></i>
                            <div>
                                <strong>Satisfaction Guarantee</strong>
                                <small>Handcrafted &amp; inspected before dispatch.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush

@endsection
