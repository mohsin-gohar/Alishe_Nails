@extends('layouts.app')
@section('title', $product->meta_title ?: $product->name.' — Alishe Nails')
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: 'Shop '.$product->name.' from Alishe Nails.'), 155))
@section('meta_keywords', $product->tags ?: $product->name.', press-on nails, Alishe Nails')
@section('og_type', 'product')
@section('og_title', $product->meta_title ?: $product->name.' — Alishe Nails')
@section('og_description', $product->meta_description ?: $product->short_description ?: 'Shop '.$product->name.' from Alishe Nails.')
@section('og_image', $product->cover_image_url ?: asset('images/logo.jpeg'))

@push('head')
    <x-breadcrumb-schema :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Shop All', 'url' => route('shop.index')],
        ['name' => $product->name, 'url' => route('products.show', $product)],
    ]" />
@endpush

@section('content')

    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a> &nbsp;&gt;&nbsp;
            <a href="{{ route('shop.index') }}">Shop All</a> &nbsp;&gt;&nbsp;
            <span>{{ strtoupper($product->name) }}</span>
        </div>

        <div class="product-detail">
            {{-- ---------- Media gallery (images + videos, ordered) ---------- --}}
            <div class="product-gallery" data-media-gallery>
                @php
                    $mediaUrls = $product->media_urls;
                @endphp

                <div class="product-gallery__main" data-media-stage tabindex="0" role="button" aria-label="Zoom product media">
                    @forelse ($mediaUrls as $i => $media)
                        <div class="product-gallery__slide {{ $i === 0 ? 'is-active' : '' }}" data-media-slide data-index="{{ $i }}">
                            @if ($media['type'] === 'video')
                                <video src="{{ $media['url'] }}" controls muted loop playsinline preload="metadata"></video>
                            @else
                                <x-optimized-image :src="$media['url']" :alt="$product->name.' media '.($i + 1)" loading="eager" fetchpriority="high" />
                            @endif
                        </div>
                    @empty
                        <div class="img-placeholder">
                            Alishe Nails<br>Image unavailable
                        </div>
                    @endforelse
                </div>

                {{-- One thumb per ACTUAL media item — never an empty slot --}}
                @if (count($mediaUrls) > 1)
                    <div class="product-gallery__thumbs">
                        @foreach ($mediaUrls as $i => $media)
                            <button type="button" class="product-gallery__thumb {{ $i === 0 ? 'is-active' : '' }}"
                                    data-media-thumb data-index="{{ $i }}" aria-label="View media {{ $i + 1 }}">
                                @if ($media['type'] === 'video')
                                    <video src="{{ $media['url'] }}" muted preload="metadata"></video>
                                    <span class="product-gallery__thumb-play"><i class="fa-solid fa-play"></i></span>
                                @else
                                    <x-optimized-image :src="$media['url']" :alt="$product->name.' thumbnail '.($i + 1)" loading="lazy" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Info ---------- --}}
            <div class="product-info">
                <!-- WhatsApp Order Button -->
                @php
                    $waHeaderDigits = preg_replace('/\D/', '', (string) config('services.whatsapp.number', '923113925166'));
                    if (str_starts_with($waHeaderDigits, '0')) {
                        $waHeaderDigits = '92'.substr($waHeaderDigits, 1);
                    }
                @endphp
                <a href="https://wa.me/{{ $waHeaderDigits }}?text={{ urlencode('Assalam-o-Alaikum! Mujhe ' . $product->name . ' khareedna hai. Damme: PKR ' . $product->price . ' Product URL: ' . route('products.show', $product)) }}" target="_blank" rel="noopener" class="btn btn-whatsapp-wameline">
                    <i class="fa-brands fa-whatsapp"></i> Order via WhatsApp
                </a>
                
                @if ($product->badge)
                    <span class="product-card__badge" style="position:static;display:inline-block;margin-bottom:12px;">{{ $product->badge }}</span>
                @endif

                <div class="product-info__rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-{{ $i <= round($product->average_rating) ? 'solid' : 'regular' }} fa-star"></i>
                    @endfor
                    <span style="color:rgba(43,29,29,.75);">({{ $product->reviews_count }} Reviews)</span>
                </div>

                <h1>{{ $product->name }}</h1>
                @if ($product->seller)
                    <p class="seller-byline"><i class="fa-solid fa-sparkles"></i> Sold by {{ $product->seller->name }}@if($product->seller->instagram_handle) <span>{{ '@'.$product->seller->instagram_handle }}</span>@endif</p>
                @endif
                @php
                    $saved = auth('web')->check() && auth('web')->user()->wishlist()->where('products.id', $product->id)->exists();
                @endphp

                <div class="product-info__price">PKR {{ number_format($product->price, 0) }}</div>

                @auth('web')
                    <form action="{{ route('account.wishlist.toggle', $product) }}" method="POST" style="margin-bottom:16px;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="{{ $saved ? 'fa-solid fa-heart' : 'fa-regular fa-heart' }}"></i>
                            {{ $saved ? 'Saved to Wishlist' : 'Save to Wishlist' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:8px;margin-bottom:16px;">
                        <i class="fa-regular fa-heart"></i> Save to Wishlist
                    </a>
                @endauth

                @if ($product->isOutOfStock())
                    <p style="color:#b3261e;font-weight:600;font-size:.9rem;"><i class="fa-solid fa-circle-exclamation"></i> Out of Stock</p>
                @elseif ($product->isLowStock())
                    <p style="color:#b3261e;font-weight:600;font-size:.9rem;"><i class="fa-solid fa-triangle-exclamation"></i> Only {{ $product->stock }} left in stock</p>
                @endif

                @if ($product->description)
                    <p>{{ $product->description }}</p>
                @endif

                @if ($product->isOutOfStock())
                    <button type="button" class="btn btn-outline btn-block" disabled style="opacity:.5;cursor:not-allowed;">Out of Stock</button>
                @else
                <form action="{{ route('cart.add', $product) }}" method="POST" id="add-to-cart-form">
                    @csrf

                    @if ($product->shape)
                        <div class="option-group" data-option-group>
                            <div class="option-group__head"><h2>Shape &amp; Length</h2></div>
                            <div class="option-pills">
                                <div class="option-pill is-selected" data-value="{{ $product->shape }} {{ $product->length }}">{{ $product->length }} {{ $product->shape }}</div>
                                <div class="option-pill" data-value="Short Square">Short Square</div>
                                <div class="option-pill" data-value="Long Coffin">Long Coffin</div>
                            </div>
                            <input type="hidden" name="shape" value="{{ $product->shape }} {{ $product->length }}">
                        </div>
                    @endif

                    <div class="option-group" data-option-group id="size-option-group">
                        <div class="option-group__head">
                            <h2>Size</h2>
                            <button type="button" class="text-button" data-modal-open="size-guide-modal">Sizing Guide</button>
                        </div>
                        <div class="option-pills">
                            @foreach (['XS', 'S', 'M', 'L', 'Custom'] as $i => $size)
                                <div class="option-pill {{ $size === 'S' ? 'is-selected' : '' }}" data-value="{{ $size }}">{{ $size }}</div>
                            @endforeach
                        </div>
                        <input type="hidden" name="size" id="product-size-input" value="S">

                        {{-- Custom Size Millimeter Inputs --}}
                        <div class="custom-size-box" id="custom-size-box">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <strong style="font-size:0.82rem;color:var(--espresso);"><i class="fa-solid fa-ruler-combined" style="color:var(--rose-dark);"></i> Custom Nail Measurements (mm):</strong>
                                <small style="font-size:0.72rem;color:var(--rose-dark);font-weight:600;">Width across widest point</small>
                            </div>
                            <div class="custom-size-grid">
                                <div class="custom-size-field">
                                    <label for="custom-thumb">Thumb</label>
                                    <input type="number" id="custom-thumb" class="custom-mm-input" data-finger="T" value="16" min="8" max="25" step="0.5">
                                </div>
                                <div class="custom-size-field">
                                    <label for="custom-index">Index</label>
                                    <input type="number" id="custom-index" class="custom-mm-input" data-finger="I" value="12" min="8" max="25" step="0.5">
                                </div>
                                <div class="custom-size-field">
                                    <label for="custom-middle">Middle</label>
                                    <input type="number" id="custom-middle" class="custom-mm-input" data-finger="M" value="13" min="8" max="25" step="0.5">
                                </div>
                                <div class="custom-size-field">
                                    <label for="custom-ring">Ring</label>
                                    <input type="number" id="custom-ring" class="custom-mm-input" data-finger="R" value="11" min="8" max="25" step="0.5">
                                </div>
                                <div class="custom-size-field">
                                    <label for="custom-pinky">Pinky</label>
                                    <input type="number" id="custom-pinky" class="custom-mm-input" data-finger="P" value="9" min="8" max="25" step="0.5">
                                </div>
                            </div>
                            <small style="display:block;margin-top:6px;font-size:0.72rem;color:#6b5a5c;">
                                <i class="fa-solid fa-circle-info"></i> Enter the millimeter width for each natural nail bed.
                            </small>
                        </div>
                    </div>

                    <div class="add-to-cart-row">
                        <div class="qty-selector" data-qty-selector>
                            <button type="button" data-action="decrease" aria-label="Decrease quantity">&minus;</button>
                            <input type="number" id="product-qty-input" name="qty" value="1" min="1" max="{{ $product->stock }}" aria-label="Quantity">
                            <button type="button" data-action="increase" aria-label="Increase quantity">&plus;</button>
                        </div>

                        <button type="submit" class="btn btn-outline" style="flex:1;">
                            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                        </button>
                    </div>

                    <div class="product-live-subtotal-box">
                        <span class="subtotal-label"><i class="fa-solid fa-calculator" style="color:var(--rose-dark);margin-right:6px;"></i> Live subtotal:</span>
                        <span class="subtotal-amount" id="product-live-subtotal" data-unit-price="{{ $product->price }}">
                            PKR {{ number_format($product->price, 0) }}
                        </span>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="submit" name="buy_now" value="1" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-bolt"></i> Buy Now
                        </button>
                    </div>

                    @php
                        $waRaw = config('services.whatsapp.number', '923113925166');
                        $waDigits = preg_replace('/[^0-9]/', '', $waRaw);
                        if (str_starts_with($waDigits, '0')) {
                            $waDigits = '92'.substr($waDigits, 1);
                        }
                        $initialWaMsg = "Assalam-o-Alaikum! Mujhe Alishe Nails se ye product order karna hai:\n\n*Product:* {$product->name}\n*Price:* PKR ".number_format($product->price, 0)."\n*Quantity:* 1\n*Link:* ".url()->current()."\n\nBaraye meharbani availability confirm karein. Shukriya!";
                    @endphp

                    <a href="https://ig.me/m/alishe_nails" target="_blank" rel="noopener" class="btn-instagram-product">
                        <i class="fa-brands fa-instagram"></i>
                        <span>Order / Inquire on Instagram (@alishe_nails)</span>
                    </a>

                    <a href="https://wa.me/{{ $waDigits }}?text={{ urlencode($initialWaMsg) }}"
                       target="_blank" rel="noopener" id="btn-product-whatsapp"
                       class="btn-whatsapp-product"
                       data-product-name="{{ $product->name }}"
                       data-unit-price="{{ $product->price }}"
                       data-product-url="{{ url()->current() }}"
                       data-wa-base="https://wa.me/{{ $waDigits }}">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>Order on WhatsApp</span>
                    </a>

                    <div class="product-trust-grid">
                        <div class="product-trust-item">
                            <i class="fa-solid fa-truck-fast"></i>
                            <div>
                                <strong>Karachi Delivery</strong>
                                <small>48–96 hrs doorstep</small>
                            </div>
                        </div>
                        <div class="product-trust-item">
                            <i class="fa-solid fa-shield-check"></i>
                            <div>
                                <strong>Secure Payment</strong>
                                <small>SadaPay &amp; JazzCash</small>
                            </div>
                        </div>
                        <div class="product-trust-item">
                            <i class="fa-solid fa-hand-sparkles"></i>
                            <div>
                                <strong>100% Handcrafted</strong>
                                <small>Salon gel finish quality</small>
                            </div>
                        </div>
                        <div class="product-trust-item">
                            <i class="fa-brands fa-instagram" style="color:#C13584;"></i>
                            <div>
                                <strong>Instagram Support</strong>
                                <small>DM @alishe_nails for sizing</small>
                            </div>
                        </div>
                    </div>
                </form>
                @endif

                @if (!empty($product->whats_included))
                    <details class="accordion-item" open>
                        <summary>What's Included <i class="fa-solid fa-chevron-down chevron"></i></summary>
                        <ul>
                            @foreach ($product->whats_included as $item)
                                <li><i class="fa-solid fa-check"></i> {{ $item }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                <details class="accordion-item">
                    <summary>How to Apply <i class="fa-solid fa-chevron-down chevron"></i></summary>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> Select the correct nail size for each finger.</li>
                        <li><i class="fa-solid fa-check"></i> Prep natural nails and apply the included adhesive.</li>
                        <li><i class="fa-solid fa-check"></i> Press and hold for 20–30 seconds per nail.</li>
                    </ul>
                    <p style="font-size:.85rem;margin-top:10px;">
                        <a href="{{ route('how-to-apply.index') }}" style="text-decoration:underline;">See the full guide &rarr;</a>
                    </p>
                </details>

                <details class="accordion-item">
                    <summary>Shipping &amp; Returns (Karachi Delivery &amp; Policies) <i class="fa-solid fa-chevron-down chevron"></i></summary>
                    <ul>
                        <li><i class="fa-solid fa-truck-fast"></i> Exclusively delivering across Karachi (48&ndash;96 hrs doorstep delivery).</li>
                        <li><i class="fa-solid fa-check"></i> Delivery charges are coordinated directly upon order confirmation.</li>
                        <li><i class="fa-solid fa-wallet"></i> 100% Advance Payment via <strong>JazzCash</strong> &amp; <strong>SadaPay</strong> (No COD).</li>
                        <li><i class="fa-solid fa-ban"></i> Orders are non-returnable due to custom handcrafted hygiene standards.</li>
                        <li><a href="{{ route('policies.index') }}" style="text-decoration:underline;">Read our Karachi Shipping &amp; Privacy Policy</a></li>
                    </ul>
                </details>
            </div>
        </div>

        {{-- ---------- Reviews ---------- --}}
        <section style="padding-block:40px 64px;max-width:760px;">
            <h3>Customer Reviews</h3>

            @forelse ($product->reviews as $review)
                <div style="border-top:1px solid var(--border-soft);padding:16px 0;">
                    <strong>{{ $review->customer_name }}</strong>
                    @if ($review->is_verified_purchase)
                        <span class="status-pill status-completed" style="margin-left:8px;">Verified Purchase</span>
                    @endif
                    <div style="color:var(--gold);font-size:.8rem;margin:4px 0;">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="fa-{{ $i <= $review->rating ? 'solid' : 'regular' }} fa-star"></i>
                        @endfor
                    </div>
                    <p style="margin:0;">{{ $review->comment }}</p>
                </div>
            @empty
                <p style="color:rgba(43,29,29,.75);">No reviews yet — be the first to share your experience.</p>
            @endforelse

            @auth('web')
                @if (! $userHasReviewed)
                    <div style="border-top:1px solid var(--border-soft);padding-top:20px;margin-top:8px;">
                        <h4 style="margin-bottom:12px;">Write a Review</h4>
                        <form action="{{ route('reviews.store', $product) }}" method="POST">
                            @csrf
                            <div class="form-field" style="margin-bottom:14px;max-width:200px;">
                                <label for="rating">Rating</label>
                                <select id="rating" name="rating" required>
                                    <option value="">Select...</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                                @error('rating') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-field" style="margin-bottom:14px;">
                                <label for="comment">Your Review</label>
                                <textarea id="comment" name="comment" rows="4" required minlength="10" maxlength="1000"></textarea>
                                @error('comment') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                @else
                    <p style="font-size:.85rem;color:rgba(43,29,29,.75);margin-top:16px;">You've already reviewed this product.</p>
                @endif
            @else
                <p style="font-size:.85rem;margin-top:16px;">
                    <a href="{{ route('login') }}" style="text-decoration:underline;">Log in</a> to write a review.
                </p>
            @endauth
        </section>

        {{-- ---------- Related products ---------- --}}
        @if ($related->isNotEmpty())
            <section style="padding-block:20px 64px;">
                <div class="section-heading">
                    <span class="eyebrow">You May Also Like</span>
                </div>
                <div class="product-grid">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="modal" id="size-guide-modal" data-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="size-guide-title">
        <div class="modal__backdrop" data-modal-close></div>
        <div class="modal__panel" style="max-width:580px;">
            <button type="button" class="modal__close" data-modal-close aria-label="Close size guide"><i class="fa-solid fa-xmark"></i></button>
            <span class="eyebrow"><i class="fa-solid fa-crown" style="color:var(--gold);"></i> Atelier Precision Fit</span>
            <h2 id="size-guide-title" style="margin-bottom:8px;">How to Find Your Exact Size</h2>
            <p style="font-size:0.88rem;color:rgba(43,29,29,.75);margin-bottom:20px;">
                Our press-on nails are sculpted with salon gel. For the most comfortable and durable wear, measure each natural nail bed across its widest point in millimeters (mm).
            </p>

            <div style="background:#FAF5F2;border:1px solid var(--border-soft);border-radius:10px;padding:16px;margin-bottom:20px;">
                <h3 style="font-size:0.9rem;margin-bottom:12px;color:var(--espresso);"><i class="fa-solid fa-tape" style="color:var(--rose-dark);"></i> The 3-Step Clear Tape Method:</h3>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;font-size:0.78rem;color:rgba(43,29,29,.8);">
                    <div>
                        <strong style="display:block;color:var(--rose-dark);margin-bottom:2px;">1. Apply Tape</strong>
                        Press clear Scotch tape firmly across the widest point of your nail.
                    </div>
                    <div>
                        <strong style="display:block;color:var(--rose-dark);margin-bottom:2px;">2. Mark Edges</strong>
                        Use a fine pen to mark both outer sidewalls of your nail bed.
                    </div>
                    <div>
                        <strong style="display:block;color:var(--rose-dark);margin-bottom:2px;">3. Measure (mm)</strong>
                        Peel off tape and lay flat against a ruler to count millimeters.
                    </div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;text-align:center;border:1px solid var(--border-soft);border-radius:8px;">
                    <thead style="background:var(--blush);font-family:var(--font-serif);color:var(--espresso);">
                        <tr>
                            <th style="padding:10px;text-align:left;">Size</th>
                            <th style="padding:10px;">Thumb</th>
                            <th style="padding:10px;">Index</th>
                            <th style="padding:10px;">Middle</th>
                            <th style="padding:10px;">Ring</th>
                            <th style="padding:10px;">Pinky</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid var(--border-soft);">
                            <td style="padding:10px;text-align:left;font-weight:600;">XS (Petite)</td>
                            <td>14mm</td><td>11mm</td><td>12mm</td><td>10mm</td><td>8mm</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border-soft);background:rgba(255,246,241,0.6);">
                            <td style="padding:10px;text-align:left;font-weight:600;color:var(--rose-dark);">S (Standard)</td>
                            <td>15mm</td><td>12mm</td><td>13mm</td><td>11mm</td><td>9mm</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border-soft);">
                            <td style="padding:10px;text-align:left;font-weight:600;">M (Medium)</td>
                            <td>16mm</td><td>13mm</td><td>14mm</td><td>12mm</td><td>10mm</td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border-soft);background:rgba(255,246,241,0.6);">
                            <td style="padding:10px;text-align:left;font-weight:600;">L (Wide)</td>
                            <td>18mm</td><td>14mm</td><td>15mm</td><td>13mm</td><td>11mm</td>
                        </tr>
                        <tr>
                            <td style="padding:10px;text-align:left;font-weight:700;color:var(--gold);">Custom</td>
                            <td colspan="5" style="padding:10px;font-style:italic;">Select "Custom" on product page and type your exact mm values.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#FFF9FA;border:1px dashed #E1306C;border-radius:8px;padding:12px 16px;">
                <span style="font-size:0.8rem;color:var(--espresso);">
                    <i class="fa-brands fa-instagram" style="color:#C13584;"></i> Need personal sizing help? Send a photo of your hands to our concierge.
                </span>
                <a href="https://ig.me/m/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" class="btn btn-sm" style="background:#C13584;color:#fff;padding:6px 14px;border-radius:20px;font-size:0.75rem;">
                    DM @alishe_nails
                </a>
            </div>
        </div>
    </div>

    @php
        $schemaData = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => collect($product->media_urls)->pluck('url')->all(),
            'description' => $product->short_description ?: $product->name,
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Alishe Nails',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('products.show', $product),
                'priceCurrency' => 'PKR',
                'price' => (string) $product->price,
                'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];
        if ($product->reviews_count > 0) {
            $schemaData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->average_rating,
                'reviewCount' => (string) $product->reviews_count,
            ];
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

@endsection
