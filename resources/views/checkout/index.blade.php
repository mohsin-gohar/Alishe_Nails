@extends('layouts.app')
@section('title', 'Checkout — Alishe Nails')

@section('content')

    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a> &nbsp;&gt;&nbsp;
            <a href="{{ route('cart.index') }}">Cart</a> &nbsp;&gt;&nbsp;
            <strong>Checkout</strong>
        </div>

        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf

            <div class="checkout-layout">
                <div>
                    {{-- ---------- Shipping details ---------- --}}
                    <div class="checkout-card">
                        <h3><i class="fa-solid fa-truck"></i> Shipping Details</h3>

                        @guest('web')
                            <p style="font-size:.85rem;background:var(--ivory);padding:12px 14px;border-radius:8px;margin-top:14px;">
                                <a href="{{ route('login') }}" style="text-decoration:underline;font-weight:600;">Log in</a>
                                or <a href="{{ route('register') }}" style="text-decoration:underline;font-weight:600;">create an account</a>
                                to track this order, or continue as a guest below.
                            </p>
                        @endguest

                        <div class="form-grid">
                            <div class="form-field full">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email', $user?->email) }}" required>
                                @error('email') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" placeholder="Jane" value="{{ old('first_name', $user ? explode(' ', $user->name)[0] : '') }}" required>
                                @error('first_name') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" placeholder="Doe" value="{{ old('last_name') }}" required>
                                @error('last_name') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field full">
                                <label for="address">Street Address</label>
                                <input type="text" id="address" name="address" placeholder="123 Blossom Lane, Suite 4B" value="{{ old('address') }}" required>
                                @error('address') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="city">City</label>
                                <div style="position:relative;">
                                    <input type="text" id="city" name="city" value="Karachi" readonly style="background-color:#F5EBE6;cursor:not-allowed;font-weight:600;color:var(--espresso);" required>
                                    <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:0.75rem;background:#E7D5C9;padding:2px 8px;border-radius:12px;color:var(--espresso);font-weight:600;">
                                        <i class="fa-solid fa-location-dot"></i> Karachi Only
                                    </span>
                                </div>
                                <small style="display:block;margin-top:4px;color:#7A6E68;font-size:0.75rem;">
                                    <i class="fa-solid fa-circle-info"></i> Delivering exclusively across Karachi.
                                </small>
                                @error('city') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="area">Karachi Delivery Zone / Area</label>
                                <select id="area" name="area" style="width:100%;padding:10px 14px;border:1px solid #E0D5CE;border-radius:6px;background:#fff;font-size:0.9rem;color:var(--espresso);" required>
                                    <option value="">Select your area (e.g. DHA, Gulshan, Clifton...)</option>
                                    @php
                                        $karachiZones = [
                                            'Korangi / Landhi',
                                            'Shah Faisal Colony',
                                            'Malir / Model Colony',
                                            'Gulshan-e-Iqbal',
                                            'Gulistan-e-Johar',
                                            'PECHS / Tariq Road / Bahadurabad',
                                            'Saddar / Cantt / Garden',
                                            'Federal B Area (F.B Area)',
                                            'DHA (Defence)',
                                            'Clifton',
                                            'Nazimabad / North Nazimabad',
                                            'North Karachi / New Karachi',
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
                                <small style="display:block;margin-top:4px;color:#7A6E68;font-size:0.75rem;">
                                    Select area for exact doorstep shipping rate.
                                </small>
                                @error('area') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="postal_code">Postal Code <span style="font-weight:400;opacity:.65;">(Optional)</span></label>
                                <input type="text" id="postal_code" name="postal_code" placeholder="e.g. 75500" value="{{ old('postal_code') }}">
                                @error('postal_code') <div class="error">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-field">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" placeholder="+92 300 1234567" value="{{ old('phone', $user?->phone) }}" required>
                                @error('phone') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ---------- Payment method ---------- --}}
                    <div class="checkout-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                            <h3 style="margin:0;"><i class="fa-solid fa-wallet" style="color:var(--rose);"></i> Payment Method</h3>
                            <span style="font-size:0.75rem;background:#E7D5C9;color:var(--espresso);padding:2px 8px;border-radius:12px;font-weight:600;">Mobile Wallets</span>
                        </div>
                        <p style="font-size:0.85rem;color:#7A6E68;margin:0 0 16px;">
                            Select your mobile wallet below. Send payment to our official account and enter the Transaction ID (TID) to confirm your order.
                        </p>

                        @php($selectedPayment = old('payment_method', 'jazzcash'))

                        {{-- Option 1: JazzCash --}}
                        <label class="payment-option {{ $selectedPayment === 'jazzcash' ? 'is-selected' : '' }}" style="border-radius:10px;padding:14px 18px;margin-bottom:12px;border:2px solid {{ $selectedPayment === 'jazzcash' ? 'var(--rose)' : '#eee' }};background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:all .2s;">
                            <div class="payment-option__left" style="display:flex;align-items:center;gap:14px;">
                                <input type="radio" name="payment_method" value="jazzcash" {{ $selectedPayment === 'jazzcash' ? 'checked' : '' }} style="accent-color:var(--rose);width:18px;height:18px;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <strong style="font-size:1.02rem;color:var(--espresso);">JazzCash</strong>
                                        <span style="background:#DE1B24;color:#fff;font-size:0.65rem;padding:2px 7px;border-radius:10px;font-weight:700;letter-spacing:0.5px;">INSTANT</span>
                                    </div>
                                    <small style="color:#7A6E68;font-size:0.78rem;">Transfer via JazzCash App or *786#</small>
                                </div>
                            </div>
                            <span style="font-size:1.4rem;color:#DE1B24;"><i class="fa-solid fa-mobile-screen-button"></i></span>
                        </label>

                        {{-- Option 2: EasyPaisa --}}
                        <label class="payment-option {{ $selectedPayment === 'easypaisa' ? 'is-selected' : '' }}" style="border-radius:10px;padding:14px 18px;margin-bottom:16px;border:2px solid {{ $selectedPayment === 'easypaisa' ? 'var(--rose)' : '#eee' }};background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:all .2s;">
                            <div class="payment-option__left" style="display:flex;align-items:center;gap:14px;">
                                <input type="radio" name="payment_method" value="easypaisa" {{ $selectedPayment === 'easypaisa' ? 'checked' : '' }} style="accent-color:var(--rose);width:18px;height:18px;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <strong style="font-size:1.02rem;color:var(--espresso);">EasyPaisa</strong>
                                        <span style="background:#00A859;color:#fff;font-size:0.65rem;padding:2px 7px;border-radius:10px;font-weight:700;letter-spacing:0.5px;">INSTANT</span>
                                    </div>
                                    <small style="color:#7A6E68;font-size:0.78rem;">Transfer via EasyPaisa App or *786#</small>
                                </div>
                            </div>
                            <span style="font-size:1.4rem;color:#00A859;"><i class="fa-solid fa-wallet"></i></span>
                        </label>

                        @error('payment_method') <div class="error" style="color:#b3261e;font-size:0.8rem;margin-bottom:12px;">{{ $message }}</div> @enderror

                        {{-- JazzCash Details Box --}}
                        <div class="wallet-instructions" data-wallet-box="jazzcash" style="background:#FFF9F9;border:1px solid #F5C6CB;border-radius:10px;padding:16px 18px;margin-bottom:20px;{{ $selectedPayment === 'jazzcash' ? '' : 'display:none;' }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #F0D0D4;padding-bottom:8px;margin-bottom:12px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="background:#DE1B24;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;"><i class="fa-solid fa-check"></i></span>
                                    <strong style="color:#DE1B24;font-size:0.92rem;">JazzCash Account Details</strong>
                                </div>
                                <span style="font-size:0.72rem;background:#FDE8E8;color:#DE1B24;padding:2px 8px;border-radius:6px;font-weight:600;">Personal / Merchant</span>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;background:#fff;padding:12px;border-radius:8px;border:1px solid #F1D4D7;">
                                <div>
                                    <small style="display:block;color:#7A6E68;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Account Title</small>
                                    <strong style="font-size:0.92rem;color:var(--espresso);">{{ config('services.payment.jazzcash_account_title', 'Alishe Nails') }}</strong>
                                </div>
                                <div>
                                    <small style="display:block;color:#7A6E68;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">JazzCash Number</small>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <strong style="font-size:1.05rem;color:#DE1B24;font-family:monospace;">{{ config('services.payment.jazzcash_number', '03412126680') }}</strong>
                                        <button type="button" class="btn-copy-number" data-copy-target="{{ config('services.payment.jazzcash_number', '03412126680') }}" title="Copy Number" style="border:none;background:#FDE8E8;color:#DE1B24;padding:3px 8px;border-radius:4px;cursor:pointer;font-size:0.72rem;font-weight:600;">
                                            <i class="fa-regular fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div style="font-size:0.8rem;color:var(--espresso);line-height:1.6;">
                                <strong>Steps to Pay via JazzCash:</strong>
                                <ol style="padding-left:18px;margin:4px 0 0;">
                                    <li>Open JazzCash App or dial <code>*786#</code> &rarr; select <strong>Send Money</strong> &rarr; <strong>Mobile Account</strong>.</li>
                                    <li>Enter Account Number: <strong>{{ config('services.payment.jazzcash_number', '03412126680') }}</strong>.</li>
                                    <li>Enter your Order Total amount (shown on right) and confirm with your MPIN.</li>
                                    <li>You will receive an SMS from <strong>8558</strong> with your <strong>Transaction ID (TID)</strong>.</li>
                                    <li>Enter that TID below to complete checkout.</li>
                                </ol>
                            </div>
                        </div>

                        {{-- EasyPaisa Details Box --}}
                        <div class="wallet-instructions" data-wallet-box="easypaisa" style="background:#F6FBF7;border:1px solid #C3E6CB;border-radius:10px;padding:16px 18px;margin-bottom:20px;{{ $selectedPayment === 'easypaisa' ? '' : 'display:none;' }}">
                            <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #D4EDDA;padding-bottom:8px;margin-bottom:12px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="background:#00A859;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;"><i class="fa-solid fa-check"></i></span>
                                    <strong style="color:#00A859;font-size:0.92rem;">EasyPaisa Account Details</strong>
                                </div>
                                <span style="font-size:0.72rem;background:#E8F7EE;color:#00A859;padding:2px 8px;border-radius:6px;font-weight:600;">Personal / Wallet</span>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;background:#fff;padding:12px;border-radius:8px;border:1px solid #D1E7DD;">
                                <div>
                                    <small style="display:block;color:#7A6E68;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">Account Title</small>
                                    <strong style="font-size:0.92rem;color:var(--espresso);">{{ config('services.payment.easypaisa_account_title', 'Alishe Nails') }}</strong>
                                </div>
                                <div>
                                    <small style="display:block;color:#7A6E68;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;">EasyPaisa Number</small>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <strong style="font-size:1.05rem;color:#00A859;font-family:monospace;">{{ config('services.payment.easypaisa_number', '03412126680') }}</strong>
                                        <button type="button" class="btn-copy-number" data-copy-target="{{ config('services.payment.easypaisa_number', '03412126680') }}" title="Copy Number" style="border:none;background:#E8F7EE;color:#00A859;padding:3px 8px;border-radius:4px;cursor:pointer;font-size:0.72rem;font-weight:600;">
                                            <i class="fa-regular fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div style="font-size:0.8rem;color:var(--espresso);line-height:1.6;">
                                <strong>Steps to Pay via EasyPaisa:</strong>
                                <ol style="padding-left:18px;margin:4px 0 0;">
                                    <li>Open EasyPaisa App or dial <code>*786#</code> &rarr; select <strong>Send Money</strong> &rarr; <strong>EasyPaisa Transfer</strong>.</li>
                                    <li>Enter Account Number: <strong>{{ config('services.payment.easypaisa_number', '03412126680') }}</strong>.</li>
                                    <li>Enter your Order Total amount (shown on right) and confirm with your PIN.</li>
                                    <li>You will receive an SMS from <strong>3737</strong> with your <strong>Transaction ID (TID)</strong>.</li>
                                    <li>Enter that TID below to complete checkout.</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Payment Verification Form Fields --}}
                        <div class="payment-verification-fields" style="background:#FAF8F6;padding:16px;border-radius:10px;border:1px solid #EAE3DE;">
                            <h4 style="font-size:0.92rem;margin:0 0 12px;color:var(--espresso);display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-receipt" style="color:var(--rose);"></i> Payment Verification Details
                            </h4>

                            <div class="form-field" style="margin-bottom:12px;">
                                <label for="transaction_reference" style="font-weight:600;">Transaction ID (TID) / Reference <span style="color:#b3261e;">*</span></label>
                                <input type="text" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" placeholder="e.g. 10492840284 (From 8558 or 3737 SMS)" required style="font-family:monospace;letter-spacing:0.5px;">
                                <small style="display:block;margin-top:4px;color:#7A6E68;font-size:0.75rem;">
                                    <i class="fa-solid fa-circle-info"></i> Enter the Transaction ID from the SMS received after sending payment.
                                </small>
                                @error('transaction_reference') <div class="error" style="color:#b3261e;font-size:0.78rem;margin-top:4px;">{{ $message }}</div> @enderror
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                <div class="form-field" style="margin-bottom:0;">
                                    <label for="sender_number">Sender Account / Mobile No. <span style="font-weight:400;opacity:.65;">(Optional)</span></label>
                                    <input type="text" id="sender_number" name="sender_number" value="{{ old('sender_number') }}" placeholder="e.g. 0341 1234567">
                                    <small style="display:block;margin-top:4px;color:#7A6E68;font-size:0.74rem;">Number you paid from</small>
                                    @error('sender_number') <div class="error" style="color:#b3261e;font-size:0.78rem;margin-top:4px;">{{ $message }}</div> @enderror
                                </div>

                                <div class="form-field" style="margin-bottom:0;">
                                    <label for="payment_notes">Payment Note <span style="font-weight:400;opacity:.65;">(Optional)</span></label>
                                    <input type="text" id="payment_notes" name="payment_notes" value="{{ old('payment_notes') }}" placeholder="e.g. Sent via app">
                                    <small style="display:block;margin-top:4px;color:#7A6E68;font-size:0.74rem;">Any extra detail</small>
                                    @error('payment_notes') <div class="error" style="color:#b3261e;font-size:0.78rem;margin-top:4px;">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-trust" aria-label="Shopping assurances">
                        <div><i class="fa-solid fa-truck-fast"></i><span><strong>Karachi Express (24-48h)</strong><small>Fast doorstep delivery</small></span></div>
                        <div><i class="fa-solid fa-hand-sparkles"></i><span><strong>100% Handmade</strong><small>Crafted locally in Karachi</small></span></div>
                        <div><i class="fa-solid fa-shield-check"></i><span><strong>Instant Verification</strong><small>JazzCash &amp; EasyPaisa Accepted</small></span></div>
                    </div>
                </div>

                <div>
                    {{-- ---------- Order summary ---------- --}}
                    <div class="checkout-card">
                        <h3 style="margin-bottom:20px;">Order Summary</h3>

                        @foreach ($items as $item)
                            <div class="order-summary-item">
                                <div class="order-summary-item__image">
                                    @php($url = $item['image'] && file_exists(public_path('images/products/'.$item['image'])) ? asset('images/products/'.$item['image']) : null)
                                    @if ($url)
                                        <img src="{{ $url }}" alt="{{ $item['name'] }}">
                                    @else
                                        <div class="img-placeholder" style="font-size:.55rem;">No image</div>
                                    @endif
                                </div>
                                <div style="flex:1;">
                                    <div class="order-summary-item__name">{{ $item['name'] }}</div>
                                    <div class="order-summary-item__meta">
                                        @if ($item['size']) Size: {{ $item['size'] }} @endif
                                        @if ($item['shape']) | Shape: {{ $item['shape'] }} @endif
                                    </div>
                                    <div class="order-summary-item__meta">Qty: {{ $item['qty'] }}</div>
                                </div>
                                <strong>PKR {{ number_format($item['price'] * $item['qty'], 0) }}</strong>
                            </div>
                        @endforeach

                        <div class="summary-row" style="border-top:1px solid rgba(43,29,29,.1);padding-top:14px;">
                            <span>Subtotal</span><span>PKR {{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="checkout-shipping-amount">{{ $shipping == 0 ? 'Free' : 'PKR '.number_format($shipping, 0) }}</span>
                        </div>

                        <form action="{{ route('checkout.coupon.apply') }}" method="POST" style="margin-top:18px;">
                            @csrf
                            <label for="promo_code" style="display:block;font-weight:600;margin-bottom:8px;">Have a promo code?</label>
                            <div style="display:flex;gap:8px;">
                                <input id="promo_code" name="code" value="{{ old('code', session('applied_coupon.code')) }}" placeholder="Enter code" style="flex:1;">
                                <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                            </div>
                            @if ($coupon)
                                <div style="margin-top:8px;font-size:.82rem;color:var(--rose-dark);">Applied: <strong>{{ $coupon->code }}</strong> ({{ $coupon->type === 'fixed' ? 'PKR '.number_format($coupon->value, 0) : $coupon->value.'%' }})</div>
                            @endif
                        </form>

                        @if ($coupon)
                            <form action="{{ route('checkout.coupon.remove') }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="margin-top:8px;background:transparent;border:1px solid rgba(43,29,29,.2);color:var(--espresso);">Remove</button>
                            </form>
                        @endif

                        @if ($coupon)
                            <div class="summary-row" style="margin-top:12px;">
                                <span>Discount</span>
                                <span>- PKR {{ number_format($discount, 0) }}</span>
                            </div>
                        @endif
                        <div class="summary-row total">
                            <span>Total</span><span id="checkout-total-amount">PKR {{ number_format($total, 0) }}</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">
                            <i class="fa-solid fa-lock"></i> Place Order Securely
                        </button>
                    </div>

                    <div class="trust-badges">
                        <div class="trust-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            <div>
                                <strong>Secure Checkout</strong>
                                <small>256-bit SSL encrypted connection.</small>
                            </div>
                        </div>
                        <div class="trust-badge">
                            <i class="fa-solid fa-award"></i>
                            <div>
                                <strong>Satisfaction Guarantee</strong>
                                <small>Careful quality check before dispatch.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection
