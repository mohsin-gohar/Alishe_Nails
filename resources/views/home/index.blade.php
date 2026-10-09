{{-- resources/views/home/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Alishe Nails — Timeless Nails, Made for You')

@section('content')

    {{-- ---------- Hero ---------- --}}
    <section class="hero">
    <div class="container hero__grid">
        <div>
            <h1>Timeless Nails,<br><span class="italic-accent">Made for You</span></h1>
            <p class="lead">Luxury Handmade Press-On Nails &middot; Delivered Exclusively Across Karachi</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary">Shop Now</a>
        </div>
        <div class="hero__image">
            @if (file_exists(public_path('vedios/headervedio.mp4')))
    <video autoplay loop muted playsinline style="width:100%; max-height:500px; object-fit:contain;">
        <source src="{{ asset('vedios/headervedio.mp4') }}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
@else
    <div class="img-placeholder">Video missing: vedios/headervedio.mp4</div>
@endif
        </div>
    </div>
</section>

    {{-- ---------- Value props ---------- --}}
    <div class="container">
        <div class="value-props">
            <div class="value-prop"><i class="fa-solid fa-truck-fast"></i> Karachi Express (24-48h)</div>
            <div class="value-prop"><i class="fa-solid fa-wallet"></i> JazzCash &amp; EasyPaisa</div>
            <div class="value-prop"><i class="fa-regular fa-heart"></i> Handmade in Karachi</div>
            <div class="value-prop"><i class="fa-solid fa-gem"></i> Salon Quality &amp; Reusable</div>
        </div>
    </div>

    {{-- ---------- Shop our collection ---------- --}}
    <section class="container" style="padding-block:64px;">
        <div class="section-heading">
            <span class="eyebrow">Shop Our Collection</span>
            <p style="margin:0;">Find your perfect set</p>
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
                        <a href="{{ route('shop.index', ['category' => [$collection->slug]]) }}" class="btn btn-outline btn-sm">Shop Now</a>
                    </div>
                @endforeach
            @else
                <p>No collections yet — add categories from the admin/database.</p>
            @endif
        </div>
    </section>

    {{-- ---------- Best sellers ---------- --}}
    <section class="best-sellers">
        <div class="container best-sellers__grid">
            <div class="best-sellers__intro">
                <h2>Our Best<br><span class="italic-accent">Sellers</span></h2>
                <p>Discover the styles our customers can't get enough of. Hand-painted perfection delivered right to your doorstep in Karachi.</p>
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

    {{-- ---------- Instagram / socials ---------- --}}
    <section class="container socials">
        <div class="socials__header">
            <div>
                <span class="eyebrow">Socials</span>
                <h3 style="margin:0;">{{ '@' . config('services.instagram.handle', 'alishe_nails') }}</h3>
            </div>
            <a href="https://instagram.com/{{ config('services.instagram.handle', 'alishe_nails') }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Follow Us</a>
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
                {{-- Fallback: no synced posts yet (sync never ran or is not
                     configured) — keep the original static placeholders. --}}
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
