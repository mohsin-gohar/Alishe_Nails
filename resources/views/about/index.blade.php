@extends('layouts.app')

@section('title', 'Our Story & Craftsmanship — Alishe Nails Karachi')

@section('content')

    <div class="page-header">
        <span class="eyebrow" style="color:var(--rose-dark);letter-spacing:0.14em;text-transform:uppercase;font-size:0.8rem;display:block;margin-bottom:6px;">
            The Haute Nail Atelier
        </span>
        <h1 style="font-size:2.6rem;">Couture Craftsmanship, Made for You</h1>
        <p style="font-size:1.05rem;max-width:540px;margin-inline:auto;">Handcrafted with intention, salon-grade durability, and designed for modern Karachi living.</p>
    </div>

    <div class="container">
        <div class="about-story" style="align-items:center;">
            <div>
                <span class="eyebrow" style="display:inline-flex;align-items:center;gap:6px;color:var(--rose-dark);font-size:.8rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:12px;background:var(--blush);padding:4px 12px;border-radius:20px;">
                    <i class="fa-solid fa-gem" style="color:var(--gold);"></i> The Karachi Atelier
                </span>
                <h2 style="font-size:2rem;line-height:1.2;margin-bottom:16px;">
                    A Love Letter to the Ritual of<br><span class="italic-accent">Effortless Manicures</span>
                </h2>
                <p style="font-size:0.95rem;line-height:1.7;color:rgba(43,29,29,.8);">
                    Alishe Nails was founded on a simple realization: modern women love high-fashion salon nails, but hate spending 3 exhausting hours at salons breathing acrylic fumes and suffering drilled, paper-thin natural nails.
                </p>
                <p style="font-size:0.95rem;line-height:1.7;color:rgba(43,29,29,.8);">
                    Working out of our private studio in Karachi, each set is sculpted with 7 layers of premium salon gel, high-density pigment, and hand-embellished accents. With our complimentary 6-piece prep kit, you can apply salon-grade nails in just 10 minutes at home, enjoy 3+ weeks of chip-resistant wear, and gently soak them off without a single trace of natural nail damage.
                </p>

                <div style="display:flex;align-items:center;gap:14px;margin-top:24px;flex-wrap:wrap;">
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">
                        <i class="fa-solid fa-gem"></i> Explore The Collection
                    </a>
                    <a href="https://ig.me/m/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" class="btn btn-outline">
                        <i class="fa-brands fa-instagram"></i> Meet Us On Instagram
                    </a>
                </div>
            </div>

            <div class="about-story__image" style="border:1.5px solid rgba(212,175,55,0.3);border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 16px 40px rgba(43,29,29,0.1);">
                @if (file_exists(public_path('images/brand/about-hands.jpg')))
                    <img src="{{ asset('images/brand/about-hands.jpg') }}" alt="Alishe Nails handmade press-on nails" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <div class="img-placeholder" style="aspect-ratio:4/3.5;background:linear-gradient(135deg,#FFF2F5,#F6D6DC);display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-crown" style="font-size:2.8rem;color:var(--gold);margin-bottom:12px;"></i>
                        <strong style="font-size:1.1rem;color:var(--espresso);">Alishe Nails Atelier</strong>
                        <span style="font-size:0.8rem;color:var(--rose-dark);margin-top:4px;">Karachi, Pakistan</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Value Cards --}}
    <section class="about-values container" style="margin-top:48px;">
        <div class="value-card">
            <i class="fa-solid fa-hand-sparkles" style="color:var(--rose-dark);font-size:1.8rem;margin-bottom:12px;"></i>
            <h4>100% Hand-Painted</h4>
            <p style="font-size:.88rem;color:rgba(43,29,29,.7);">Every single nail is sculpted, buffed, and painted by hand in small artisan batches for bespoke individuality.</p>
        </div>
        <div class="value-card">
            <i class="fa-solid fa-shield-heart" style="color:#2E7D32;font-size:1.8rem;margin-bottom:12px;"></i>
            <h4>Healthy Natural Nails</h4>
            <p style="font-size:.88rem;color:rgba(43,29,29,.7);">Zero drills, zero thinning, and zero toxic salon odors. Safe for everyday wear and gentle on delicate nail beds.</p>
        </div>
        <div class="value-card">
            <i class="fa-solid fa-repeat" style="color:var(--gold);font-size:1.8rem;margin-bottom:12px;"></i>
            <h4>Reusable Up to 5 Times</h4>
            <p style="font-size:.88rem;color:rgba(43,29,29,.7);">Crafted with reinforced salon gel that doesn't bend or crack. Re-wear your favourite set whenever you dress up.</p>
        </div>
    </section>

    {{-- Karachi Atelier Assurance --}}
    <div class="container" style="margin-block:64px;">
        <div style="background:#FAF5F2;border:1px solid var(--border-soft);border-radius:var(--radius-lg);padding:36px;text-align:center;max-width:860px;margin-inline:auto;">
            <span class="eyebrow" style="color:var(--rose-dark);letter-spacing:0.14em;text-transform:uppercase;font-size:0.75rem;">
                <i class="fa-solid fa-truck-fast"></i> Karachi Doorstep Service
            </span>
            <h3 style="font-size:1.6rem;margin:8px 0 12px;">Handcrafted &amp; Delivered Exclusively Across Karachi</h3>
            <p style="font-size:0.92rem;color:rgba(43,29,29,.75);max-width:620px;margin-inline:auto;line-height:1.6;">
                Because each nail set is custom curated to your dimensions, we exclusively deliver across all Karachi neighborhoods within 48 to 96 hours. We operate on a 100% advance payment model via SadaPay and JazzCash (<code>03113925166</code> &middot; Alishba Naz).
            </p>
            <div style="margin-top:20px;display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;">
                <a href="{{ route('how-to-apply.index') }}" class="btn btn-outline btn-sm">Read Sizing &amp; Application Guide</a>
                <a href="{{ route('contact.index') }}" class="btn btn-primary btn-sm">Speak With Our Concierge</a>
            </div>
        </div>
    </div>

@endsection
