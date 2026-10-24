{{-- resources/views/home/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Alishe Nails — Bespoke Couture Press-On Nails | Karachi Atelier')

@section('content')

    {{-- ---------- 1. Haute Couture Hero ---------- --}}
    <section class="hero">
        <div class="container hero__grid">
            <div>
                <span class="hero__badge-luxury">
                    <i class="fa-solid fa-crown"></i> Bespoke Press-On Nail Atelier &middot; Karachi
                </span>
                <h1 style="font-size:2.8rem;line-height:1.15;margin-bottom:16px;">
                    Couture Nails,<br><span class="italic-accent">Sculpted for Elegance</span>
                </h1>
                <p class="lead" style="font-size:1.05rem;line-height:1.6;color:rgba(43,29,29,.8);margin-bottom:24px;">
                    Handcrafted in small batches using 7-layer salon gel &amp; fine nail artistry. 100% damage-free, reusable up to 5 times, delivered in signature luxury packaging across Karachi.
                </p>

                <div class="hero__actions">
                    <a href="{{ route('shop.index') }}" class="btn btn-gold-luxury">
                        <i class="fa-solid fa-gem"></i> Explore The Collection
                    </a>
                    <a href="{{ route('custom-design.create') }}" class="btn btn-outline">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Custom Bespoke Design
                    </a>
                </div>

                <div class="hero__trust-strip">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="hero__trust-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
                        <strong>4.9 / 5</strong>
                        <span style="opacity:.75;">(1,200+ Karachi Clients)</span>
                    </div>
                    <span>&bull;</span>
                    <div><i class="fa-solid fa-box-open" style="color:var(--rose);"></i> Complimentary Prep Kit</div>
                    <span>&bull;</span>
                    <div><i class="fa-solid fa-shield-halved" style="color:#2E7D32;"></i> Zero Damage</div>
                </div>
            </div>

            <div class="hero__image" style="border:1.5px solid rgba(212,175,55,0.3);box-shadow:0 16px 40px rgba(43,29,29,0.12);">
                @if (file_exists(public_path('vedios/headervedio.mp4')))
                    <video autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover;">
                        <source src="{{ asset('vedios/headervedio.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <div class="img-placeholder" style="aspect-ratio:4/3.2;background:linear-gradient(135deg,#FFF2F5,#F6D6DC);">
                        <i class="fa-solid fa-gem" style="font-size:2.5rem;color:var(--rose);margin-bottom:10px;"></i>
                        <br>Alishe Nails Atelier
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ---------- 2. Haute Couture Value Props (4 Columns) ---------- --}}
    <section class="container">
        <div class="luxe-value-grid">
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-gem"></i></div>
                <h4>Salon Gel Architecture</h4>
                <p>7-layer reinforced gel structure. Non-bending, chip-resistant glass shine that lasts up to 3+ weeks.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <h4>Karachi White-Glove Courier</h4>
                <p>Doorstep delivery in 48–96 hours across Clifton, DHA, Gulshan, PECHS, and all Karachi zones.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <h4>Zero Natural Nail Damage</h4>
                <p>Safe 10-minute application &amp; gentle warm-water soak off. Keeps your natural nail beds healthy.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                <h4>Bespoke Tailoring &amp; Sizing</h4>
                <p>Custom millimeter measurements or standard XS–L sizes for an undetectable salon-quality fit.</p>
            </div>
        </div>
    </section>

    {{-- ---------- 3. Shop Our Collections ---------- --}}
    <section class="container" style="padding-block:48px 64px;">
        <div class="section-heading">
            <span class="eyebrow">Curated Collections</span>
            <h2 style="font-size:2.1rem;margin-bottom:6px;">Find Your Signature Aesthetic</h2>
            <p style="margin:0;color:rgba(43,29,29,.7);">Designed to transition effortlessly from daily elegance to bridal glamour.</p>
        </div>

        <div class="collections-grid">
            @if(isset($collections) && $collections->count() > 0)
                @foreach ($collections as $collection)
                    <div class="collection-card">
                        <a href="{{ route('shop.index', ['category' => [$collection->slug]]) }}" class="collection-card__image" style="display:block;">
                            @php
                                $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $collection->name);
                                $slug = strtolower(trim($slug, '-'));
                                $file = 'images/collections/' . $slug . '.jpg';
                                $imgSrc = file_exists(public_path($file)) ? asset($file) : asset('images/collections/everyday-elegance.jpg');
                            @endphp
                            <img src="{{ $imgSrc }}" alt="{{ $collection->name }}" loading="lazy">
                        </a>
                        <h4>{{ $collection->name }}</h4>
                        <a href="{{ route('shop.index', ['category' => [$collection->slug]]) }}" class="btn btn-outline btn-sm">Shop Collection</a>
                    </div>
                @endforeach
            @else
                <p>No collections yet — add categories from the admin/database.</p>
            @endif
        </div>
    </section>

    {{-- ---------- 4. Interactive Nail Shape & Length Visual Guide ---------- --}}
    <section class="shape-guide-section">
        <div class="container">
            <div class="section-heading" style="margin-bottom:20px;">
                <span class="eyebrow">Silhouette Guide</span>
                <h2 style="font-size:2.1rem;margin-bottom:6px;">Discover Your Ideal Nail Shape</h2>
                <p style="margin:0;color:rgba(43,29,29,.7);">Every shape is meticulously sculpted for comfort, durability, and hand elongation.</p>
            </div>

            <div class="shape-grid">
                <a href="{{ route('shop.index', ['shape' => 'Almond']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-almond"></div>
                    </div>
                    <h4>Almond</h4>
                    <span class="shape-length">Medium / Long</span>
                    <p class="shape-desc">Slender sides tapering to a soft curve. Elongates fingers for a timeless, graceful look.</p>
                    <span class="shape-link">Shop Almond &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Coffin']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-coffin"></div>
                    </div>
                    <h4>Coffin / Ballerina</h4>
                    <span class="shape-length">Long / Medium</span>
                    <p class="shape-desc">Tapered edges with a crisp flat tip. The premier choice for contemporary high-glam nail art.</p>
                    <span class="shape-link">Shop Coffin &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Square']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-square"></div>
                    </div>
                    <h4>Square</h4>
                    <span class="shape-length">Short / Medium</span>
                    <p class="shape-desc">Crisp 90-degree parallel edges. Classic, clean, and durable for everyday busy hands.</p>
                    <span class="shape-link">Shop Square &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Stiletto']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-stiletto"></div>
                    </div>
                    <h4>Stiletto</h4>
                    <span class="shape-length">Long</span>
                    <p class="shape-desc">Dramatic, sharp pointed tip. A fierce fashion statement for weddings, galas, and photoshoots.</p>
                    <span class="shape-link">Shop Stiletto &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Oval']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-oval"></div>
                    </div>
                    <h4>Oval / Round</h4>
                    <span class="shape-length">Short / Medium</span>
                    <p class="shape-desc">Soft, natural contours hugging the finger. Low-maintenance beauty for effortless daily wear.</p>
                    <span class="shape-link">Shop Oval &rarr;</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ---------- 5. Atelier Press-Ons vs. Salon Acrylics Comparison Matrix ---------- --}}
    <section class="container comparison-section">
        <div class="section-heading" style="margin-bottom:20px;">
            <span class="eyebrow">The Smart Luxury Choice</span>
            <h2 style="font-size:2.1rem;margin-bottom:6px;">Alishe Press-Ons vs. Salon Acrylics</h2>
            <p style="margin:0;color:rgba(43,29,29,.7);">Why modern Karachi women are switching to couture handcrafted press-on nails.</p>
        </div>

        <div class="comparison-wrapper">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th style="width:28%;">Feature</th>
                        <th class="col-alishe" style="width:36%;">
                            <i class="fa-solid fa-crown" style="color:var(--gold);margin-right:6px;"></i> Alishe Nails Atelier
                        </th>
                        <th style="width:36%;">Traditional Salon Acrylics</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Application Time</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> 10 Minutes at Home</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-clock"></i> 2 to 3 Hours at Salon</span></td>
                    </tr>
                    <tr>
                        <td><strong>Natural Nail Health</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> Zero Damage (100% Breathable)</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-circle-xmark"></i> Electric drill thinning &amp; damage</span></td>
                    </tr>
                    <tr>
                        <td><strong>Cost &amp; Longevity</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> PKR 1,500 – 3,500 (Reusable 5x)</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-circle-xmark"></i> PKR 8,000 – 15,000 + Bi-weekly refills</span></td>
                    </tr>
                    <tr>
                        <td><strong>Flexibility &amp; Convenience</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> Swap styles for any dress/event</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-circle-xmark"></i> Locked into one style for 4 weeks</span></td>
                    </tr>
                    <tr>
                        <td><strong>Removal Process</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> Gentle soak in warm water</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-circle-xmark"></i> Harsh pure acetone soaking &amp; scraping</span></td>
                    </tr>
                    <tr>
                        <td><strong>Hygiene Standards</strong></td>
                        <td class="col-alishe"><span class="badge-check"><i class="fa-solid fa-circle-check"></i> 100% Personal &amp; Sterile in Sealed Box</span></td>
                        <td><span class="badge-cross"><i class="fa-solid fa-triangle-exclamation"></i> Shared tools &amp; nail salon dust</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ---------- 6. What's Inside Every Luxury Box (The 6-Piece Prep Kit) ---------- --}}
    <section class="unboxing-section">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Complimentary With Every Order</span>
                <h2 style="font-size:2.1rem;margin-bottom:6px;">The Signature 6-Piece Prep Kit</h2>
                <p style="margin:0;color:rgba(43,29,29,.7);">Everything required for a flawless, salon-durable 10-minute application at home.</p>
            </div>

            <div class="unboxing-grid">
                <div class="unboxing-card">
                    <div class="unboxing-number">01</div>
                    <div>
                        <h4>Salon Brush-On Nail Glue</h4>
                        <p>Medical-grade adhesive formulated for 2 to 3 weeks of secure, gap-free wear.</p>
                    </div>
                </div>
                <div class="unboxing-card">
                    <div class="unboxing-number">02</div>
                    <div>
                        <h4>24x Ultra-Bond Adhesive Tabs</h4>
                        <p>Perfect for weekend wear, weddings, or quick photoshoots with zero glue residue.</p>
                    </div>
                </div>
                <div class="unboxing-card">
                    <div class="unboxing-number">03</div>
                    <div>
                        <h4>Dual-Grit Crystal Nail File</h4>
                        <p>Precision shaping and gentle surface buffing to maximize adhesive adhesion.</p>
                    </div>
                </div>
                <div class="unboxing-card">
                    <div class="unboxing-number">04</div>
                    <div>
                        <h4>Birchwood Cuticle Stick</h4>
                        <p>Beveled tip designed to gently push back cuticles for an undetectable flush fit.</p>
                    </div>
                </div>
                <div class="unboxing-card">
                    <div class="unboxing-number">05</div>
                    <div>
                        <h4>2x Alcohol Prep Pads</h4>
                        <p>Medical-grade 70% isopropyl wipes to cleanse natural nail oils before bonding.</p>
                    </div>
                </div>
                <div class="unboxing-card">
                    <div class="unboxing-number">06</div>
                    <div>
                        <h4>Satin Pouch &amp; Step Guide</h4>
                        <p>Protective keepsake pouch and full illustrated instruction card for safe re-use.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ---------- 7. Best Sellers Carousel / Grid ---------- --}}
    <section class="best-sellers">
        <div class="container best-sellers__grid">
            <div class="best-sellers__intro">
                <h2>Our Best<br><span class="italic-accent">Sellers</span></h2>
                <p>Discover the couture styles our Karachi clients love most. Hand-painted perfection delivered directly to your doorstep.</p>
                <a href="{{ route('shop.index') }}" style="text-decoration:underline;font-weight:600;">View All Best Sellers &rarr;</a>
            </div>

            <div class="best-sellers__products">
                @if(isset($bestSellers) && $bestSellers->count() > 0)
                    @foreach ($bestSellers as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                @else
                    <p>Mark products as "best seller" in the database to feature them here.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ---------- 8. Verified Karachi Client Testimonials ---------- --}}
    <section class="container testimonials-section">
        <div class="section-heading">
            <span class="eyebrow">Client Love</span>
            <h2 style="font-size:2.1rem;margin-bottom:6px;">Loved Across Karachi</h2>
            <p style="margin:0;color:rgba(43,29,29,.7);">Read authentic reviews from women who elevated their manicure routine.</p>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                <p class="testimonial-quote">"I was skeptical about press-ons, but Alishe Nails completely blew me away! The fit is so seamless everyone at the wedding thought I spent 4 hours at a high-end salon. Stayed solid for 2.5 weeks!"</p>
                <div class="testimonial-client">
                    <strong>Syeda Ayesha K.</strong>
                    <span class="testimonial-location"><i class="fa-solid fa-location-dot"></i> DHA Phase 6, Karachi</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                <p class="testimonial-quote">"The quality of the gel and packaging is pure luxury. Sizing was spot on after measuring my nails in mm. Sent my payment via SadaPay and got my order in 48 hours. 10/10 service!"</p>
                <div class="testimonial-client">
                    <strong>Mahnoor Tariq</strong>
                    <span class="testimonial-location"><i class="fa-solid fa-location-dot"></i> Clifton Block 4, Karachi</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                <p class="testimonial-quote">"No more ruining my natural nails with salon drills. These are thick, durable, and don't bend at all. I have already re-worn my French Ombre set 3 times using the adhesive tabs!"</p>
                <div class="testimonial-client">
                    <strong>Zainab Farooq</strong>
                    <span class="testimonial-location"><i class="fa-solid fa-location-dot"></i> Gulshan-e-Iqbal, Karachi</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ---------- 9. Bespoke Custom Inspo Concierge Banner ---------- --}}
    <div class="container">
        <div class="concierge-banner">
            <div>
                <span style="display:inline-block;color:var(--gold);font-size:0.75rem;letter-spacing:0.12em;text-transform:uppercase;font-weight:600;margin-bottom:8px;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Bespoke Custom Atelier
                </span>
                <h3>Have a Dream Nail Design in Mind?</h3>
                <p>
                    Found a design on Pinterest or Instagram that you love? Send us the reference picture! Our master nail artist will handcraft your custom set to your exact shape, length, and mm measurements.
                </p>
            </div>
            <div class="concierge-buttons">
                <a href="https://ig.me/m/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" class="btn-concierge-ig">
                    <i class="fa-brands fa-instagram" style="font-size:1.2rem;"></i> Send Inspo on Instagram (@alishe_nails)
                </a>
                <a href="https://wa.me/923113925166?text={{ urlencode('Assalam-o-Alaikum! Mujhe custom nail design banwana hai. Ye raha reference picture.') }}" target="_blank" rel="noopener" class="btn-concierge-wa">
                    <i class="fa-brands fa-whatsapp" style="font-size:1.2rem;"></i> WhatsApp Consultation (+92 311 3925166)
                </a>
            </div>
        </div>
    </div>

    {{-- ---------- 10. Instagram / Socials Showcase ---------- --}}
    <section class="container socials" style="padding-top:20px;">
        <div class="socials__header">
            <div>
                <span class="eyebrow">The Atelier Journal</span>
                <h3 style="margin:0;">{{ '@' . config('services.instagram.handle', 'alishe_nails') }}</h3>
            </div>
            <a href="https://instagram.com/{{ config('services.instagram.handle', 'alishe_nails') }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Follow On Instagram</a>
        </div>

        <div class="socials-grid">
            @forelse ($instagramPosts as $post)
                <a href="{{ $post->permalink }}"
                   class="socials-grid__item socials-grid__link"
                   target="_blank"
                   rel="noopener"
                   aria-label="Open this post on Instagram">
                    <img src="{{ $post->display_url }}"
                         alt="{{ \Illuminate\Support\Str::limit($post->caption ?? 'Alishe Nails on Instagram', 80) }}"
                         loading="lazy">
                    @if ($post->isVideo())
                        <span class="socials-grid__badge"><i class="fa-solid fa-play"></i></span>
                    @endif
                </a>
            @empty
                @php
                    $instaImages = ['coffee-moment.jpg', 'hand-nails.jpg', 'gift-box.jpg', 'flat-lay.jpg'];
                @endphp
                @foreach ($instaImages as $img)
                    <div class="socials-grid__item">
                        @if (file_exists(public_path('images/instagram/'.$img)))
                            <img src="{{ asset('images/instagram/'.$img) }}" alt="Alishe Nails on Instagram">
                        @else
                            <div class="img-placeholder">Alishe Nails<br>Social image unavailable</div>
                        @endif
                    </div>
                @endforeach
            @endforelse
        </div>
    </section>

@endsection
