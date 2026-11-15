{{-- resources/views/home/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Alishe Nails — Bespoke Couture Press-On Nails | Karachi Atelier')

@section('meta_description', 'Bespoke handcrafted press-on nails from the Alishe Nails Karachi atelier. 7-layer salon gel, zero natural nail damage, reusable up to 5 times, made for your hands.')

@section('og_title', 'Alishe Nails — Bespoke Couture Press-On Nails | Karachi Atelier')

@section('og_description', 'Bespoke 7-layer salon gel press-on nails. Damage-free, reusable up to 5 times, crafted for your hands in the Karachi atelier.')

@push('styles')
    <style>
        /* Hero showreel — homepage only, so it lives here rather than in app.css. */
        .hero-media {
            position: relative;
            flex: 1 1 340px;
            min-width: 310px;
            max-width: 540px;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            border: 2px solid #F0E2D8;
            border-radius: 24px;
            background: #2B231F;
            box-shadow: 0 20px 40px rgba(138, 92, 70, 0.15);
        }

        .hero-video {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-video-label {
            position: absolute;
            bottom: 18px;
            left: 18px;
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            background: rgba(43, 35, 31, 0.75);
            backdrop-filter: blur(8px);
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 12.5px;
            font-weight: 500;
            letter-spacing: 1px;
            color: #F8EFEA;
        }

        @media (max-width: 860px) {
            .hero-media {
                max-width: 100%;
            }
        }

        /* Homepage product rails */
        .home-rail {
            padding-block: 8px;
        }

        .home-rail__head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .home-rail__head h2 {
            margin: 0 0 6px;
            font-size: 2.1rem;
        }

        .home-rail__head p {
            margin: 0;
            color: rgba(43, 29, 29, 0.7);
        }
    </style>
@endpush

@section('content')

    {{-- ---------- 1. Haute Couture Hero ---------- --}}
    <section style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:40px;padding:60px 20px;max-width:1250px;margin:0 auto;">

        <div style="flex:1;min-width:310px;max-width:580px;">
            <span class="hero__badge-luxury">
                <i class="fa-solid fa-crown"></i> Bespoke Press-On Nail Atelier &middot; Karachi
            </span>

            <h1 style="font-family:'Playfair Display',Georgia,serif;font-size:clamp(36px,4.5vw,54px);color:#2B231F;margin:16px 0 16px 0;font-weight:500;line-height:1.15;">
                Couture Nails, <br>
                <span style="font-style:italic;color:#9C6B55;">Sculpted for Elegance</span>
            </h1>

            <p style="font-size:15px;color:#6E5D53;line-height:1.7;margin:0 0 28px 0;">
                Handcrafted in small batches using 7-layer salon gel &amp; fine nail artistry. 100% damage-free, reusable up to 5 times, delivered in signature luxury packaging across Karachi.
            </p>

            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:30px;">
                <a href="{{ route('shop.index') }}" class="btn btn-gold-luxury">
                    <i class="fa-solid fa-gem"></i> Explore The Collection
                </a>
            </div>

            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:13px;color:#6E5D53;border-top:1px solid #ECE0D8;padding-top:20px;">
                <span style="color:#D9A937;font-weight:700;">&#9733;&#9733;&#9733;&#9733;&#9733; <span style="color:#2B231F;">4.9 / 5</span> <span style="font-size:11.5px;color:#6f6053;">(1,200+ Karachi Clients)</span></span>
                <span>&bull;</span>
                <span>&#127873; Complimentary Prep Kit</span>
                <span>&bull;</span>
                <span style="color:#2E7D46;font-weight:600;">&#10003; Zero Damage</span>
            </div>
        </div>

        <div class="hero-media">
            @if ($heroVideoUrl)
                <video
                    class="hero-video"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    @if ($heroPosterUrl) poster="{{ $heroPosterUrl }}" @endif
                    aria-label="Alishe Nails atelier showreel">
                    <source src="{{ $heroVideoUrl }}" type="video/mp4">
                    Your browser does not support HTML5 video.
                </video>
            @elseif ($heroPosterUrl)
                <img class="hero-video"
                     src="{{ $heroPosterUrl }}"
                     alt="Alishe Nails press-on nail collection"
                     width="1080" height="810" fetchpriority="high">
            @else
                <div class="img-placeholder" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#FFF2F5,#F6D6DC);color:var(--rose-dark);">
                    Alishe Nails Atelier
                </div>
            @endif

            <div class="hero-video-label">
                Alishe Nails &bull; Karachi Studio
            </div>
        </div>

    </section>

    {{-- ---------- 2. Haute Couture Value Props (4 Columns) ---------- --}}
    <section class="container">
        <h2 class="sr-only">The Alishe Nails Difference</h2>
        <div class="luxe-value-grid">
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-gem"></i></div>
                <h3>Salon Gel Architecture</h3>
                <p>7-layer reinforced gel structure. Non-bending, chip-resistant glass shine that lasts up to 3+ weeks.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <h3>Karachi White-Glove Courier</h3>
                <p>Doorstep delivery in 48–96 hours across Clifton, DHA, Gulshan, PECHS, and all Karachi zones.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <h3>Zero Natural Nail Damage</h3>
                <p>Safe 10-minute application &amp; gentle warm-water soak off. Keeps your natural nail beds healthy.</p>
            </div>
            <div class="luxe-value-card">
                <div class="luxe-value-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                <h3>Bespoke Tailoring &amp; Sizing</h3>
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
                                $fallback = file_exists(public_path($file))
                                    ? \App\Support\ImageUrl::relativeUrl($file)
                                    : \App\Support\ImageUrl::relativeUrl('images/collections/everyday-elegance.jpg');
                                $imgSrc = $collection->image_url ?: $fallback;
                            @endphp
                            <x-optimized-image :src="$imgSrc" :alt="$collection->name" loading="lazy" />
                        </a>
                        <h3>{{ $collection->name }}</h3>
                        @if ($collection->description)
                            <p style="margin:-6px 0 12px;font-size:.85rem;color:rgba(43,29,29,.65);">{{ $collection->description }}</p>
                        @endif
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
                    <h3>Almond</h3>
                    <span class="shape-length">Medium / Long</span>
                    <p class="shape-desc">Slender sides tapering to a soft curve. Elongates fingers for a timeless, graceful look.</p>
                    <span class="shape-link">Shop Almond &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Coffin']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-coffin"></div>
                    </div>
                    <h3>Coffin / Ballerina</h3>
                    <span class="shape-length">Long / Medium</span>
                    <p class="shape-desc">Tapered edges with a crisp flat tip. The premier choice for contemporary high-glam nail art.</p>
                    <span class="shape-link">Shop Coffin &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Square']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-square"></div>
                    </div>
                    <h3>Square</h3>
                    <span class="shape-length">Short / Medium</span>
                    <p class="shape-desc">Crisp 90-degree parallel edges. Classic, clean, and durable for everyday busy hands.</p>
                    <span class="shape-link">Shop Square &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Stiletto']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-stiletto"></div>
                    </div>
                    <h3>Stiletto</h3>
                    <span class="shape-length">Long</span>
                    <p class="shape-desc">Dramatic, sharp pointed tip. A fierce fashion statement for weddings, galas, and photoshoots.</p>
                    <span class="shape-link">Shop Stiletto &rarr;</span>
                </a>

                <a href="{{ route('shop.index', ['shape' => 'Oval']) }}" class="shape-card">
                    <div class="shape-silhouette">
                        <div class="shape-icon-art shape-art-oval"></div>
                    </div>
                    <h3>Oval / Round</h3>
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

    {{-- ---------- 6. Best Sellers Carousel / Grid ---------- --}}
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

    {{-- ---------- 7. Curated Homepage Lineup (admin-controlled) ---------- --}}
    <section class="container" style="padding-block:48px 64px;">
        <div class="section-heading">
            <span class="eyebrow">Curated By The Atelier</span>
            <h2 style="font-size:2.1rem;margin-bottom:6px;">The Home Edit</h2>
            <p style="margin:0;color:rgba(43,29,29,.7);">Hand-picked favourites from our collection — in the exact order our team loves them.</p>
        </div>

        @if ($showcaseProducts->isNotEmpty())
            <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 24px;">
                @foreach ($showcaseProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @else
            <p style="text-align:center;color:#6E5D53;">No products yet — add your first product in the admin dashboard.</p>
        @endif
    </section>

    @endsection
