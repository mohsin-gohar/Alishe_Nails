@extends('layouts.app')
@section('title', 'How to Apply & Sizing Guide — Alishe Nails')

@section('content')

    <div class="page-header">
        <span class="eyebrow" style="color:var(--rose-dark);letter-spacing:0.14em;text-transform:uppercase;font-size:0.8rem;display:block;margin-bottom:6px;">
            The Atelier Masterclass
        </span>
        <h1 style="font-size:2.6rem;">How to Apply &amp; Care for Your Press-Ons</h1>
        <p style="font-size:1.05rem;max-width:560px;margin-inline:auto;">A 10-minute guide to achieving flawless salon-durable wear without natural nail damage.</p>
    </div>

    <div class="container" style="max-width:860px;padding-bottom:72px;">

        {{-- Adhesive Choice: Glue vs Tabs --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:40px;">
            <div style="background:#FAF5F2;border:1.5px solid var(--rose);border-radius:var(--radius-md);padding:24px;">
                <span style="background:var(--rose);color:#fff;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:0.08em;display:inline-block;margin-bottom:10px;">
                    Option A &middot; Long Wear
                </span>
                <h3 style="font-size:1.2rem;margin-bottom:8px;">Brush-On Salon Glue</h3>
                <p style="font-size:0.85rem;color:rgba(43,29,29,.75);margin-bottom:12px;line-height:1.5;">
                    Best for weddings, busy routines, and vacations. Bonds securely for <strong>2 to 3 weeks</strong> of uninterrupted salon shine.
                </p>
                <div style="font-size:0.8rem;color:var(--rose-dark);font-weight:600;">
                    <i class="fa-solid fa-check"></i> Waterproof after 1 hour
                </div>
            </div>

            <div style="background:#FAF5F2;border:1.5px solid var(--gold);border-radius:var(--radius-md);padding:24px;">
                <span style="background:var(--gold);color:#fff;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:0.08em;display:inline-block;margin-bottom:10px;">
                    Option B &middot; Event / Weekend
                </span>
                <h3 style="font-size:1.2rem;margin-bottom:8px;">Ultra-Bond Adhesive Tabs</h3>
                <p style="font-size:0.85rem;color:rgba(43,29,29,.75);margin-bottom:12px;line-height:1.5;">
                    Best for weekend events, dinner dates, and photoshoots. Lasts <strong>3 to 5 days</strong> with zero glue residue for instant reusability.
                </p>
                <div style="font-size:0.8rem;color:#8c7324;font-weight:600;">
                    <i class="fa-solid fa-check"></i> 100% painless instant removal
                </div>
            </div>
        </div>

        {{-- Step by step instructions --}}
        <h2 style="font-size:1.8rem;text-align:center;margin-bottom:28px;">Step-by-Step Application Protocol</h2>
        <div class="steps-list" style="margin-inline:auto;">
            @php
                $steps = [
                    'Select & Align Nail Sizes' => 'Lay all 10 press-on nails in front of you. Test each tip against your natural nail bed from sidewall to sidewall before applying any glue. When in between sizes, choose the smaller size or gently file the edges.',
                    'Push Back Cuticles' => 'Using the beveled wooden manicure stick from your prep kit, gently push back cuticles to expose the entire nail plate for an undetectable flush fit.',
                    'Light Surface Buffing' => 'Use the fine-grit side of your crystal file to gently buff the surface shine off your natural nail. This creates micro-texture for maximum adhesive grip.',
                    'Sanitize & Dehydrate' => 'Wipe each nail thoroughly with the included 70% isopropyl alcohol prep pad to eliminate natural oils, moisture, and dust.',
                    'Apply Adhesive' => 'If using glue, apply a thin drop to your natural nail and a small drop to the press-on base. If using jelly tabs, press the tab firmly onto the natural nail and peel the clear backing.',
                    'Press at a 45-Degree Angle' => 'Align the press-on tip right at the cuticle line at a 45-degree angle, then roll down toward the free edge to eliminate air bubbles.',
                    'Hold Firmly for 30 Seconds' => 'Apply firm, continuous pressure across the entire nail for 25 to 30 seconds to lock the molecular bond.',
                ];
            @endphp

            @foreach ($steps as $title => $description)
                <div class="step-item">
                    <div class="step-item__num">{{ $loop->iteration }}</div>
                    <div>
                        <h4 style="margin-bottom:6px;font-size:1.05rem;">{{ $title }}</h4>
                        <p style="margin:0;color:rgba(43,29,29,.78);font-size:0.88rem;line-height:1.6;">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Safe Removal Guide --}}
        <div style="background:#FFF9FA;border:1.5px solid rgba(185,122,135,0.3);border-radius:var(--radius-lg);padding:30px;margin-block:40px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <i class="fa-solid fa-soap" style="color:var(--rose);font-size:1.6rem;"></i>
                <h3 style="margin:0;font-size:1.4rem;">Gentle &amp; Damage-Free Removal Protocol</h3>
            </div>
            <p style="font-size:0.9rem;line-height:1.6;color:rgba(43,29,29,.8);margin-bottom:16px;">
                Never rip, pry, or force press-on nails off — doing so can peel delicate keratin layers from your natural nail beds. Follow this simple 3-step soaking ritual:
            </p>
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;font-size:0.85rem;color:rgba(43,29,29,.85);">
                <div style="background:#fff;border-radius:8px;padding:16px;border:1px solid var(--border-soft);">
                    <strong style="color:var(--rose-dark);display:block;margin-bottom:4px;">1. Warm Water Bath</strong>
                    Fill a bowl with warm water, add a few drops of liquid soap and olive/cuticle oil.
                </div>
                <div style="background:#fff;border-radius:8px;padding:16px;border:1px solid var(--border-soft);">
                    <strong style="color:var(--rose-dark);display:block;margin-bottom:4px;">2. Soak for 10–15 Mins</strong>
                    Submerge hands completely. The warm soapy oil gently softens the adhesive bond.
                </div>
                <div style="background:#fff;border-radius:8px;padding:16px;border:1px solid var(--border-soft);">
                    <strong style="color:var(--rose-dark);display:block;margin-bottom:4px;">3. Gently Lift from Side</strong>
                    Use the wooden stick to nudge the edge. It will slide off effortlessly. Dry and store in satin pouch!
                </div>
            </div>
        </div>

        {{-- Do's and Don'ts --}}
        <div class="dos-donts" style="margin-top:20px;">
            <div class="dos-donts__card dos">
                <h4><i class="fa-solid fa-circle-check" style="color:#2f7d3f;margin-right:6px;"></i> Pro Tips (Do's)</h4>
                <ul>
                    <li><i class="fa-solid fa-check" style="color:#2f7d3f;"></i> Clean and dry your natural nails thoroughly before applying.</li>
                    <li><i class="fa-solid fa-check" style="color:#2f7d3f;"></i> Measure each nail individually — fingers often differ in width.</li>
                    <li><i class="fa-solid fa-check" style="color:#2f7d3f;"></i> Keep nails dry for at least 60 minutes after application.</li>
                    <li><i class="fa-solid fa-check" style="color:#2f7d3f;"></i> Store unused sets in their original luxury box away from sunlight.</li>
                </ul>
            </div>
            <div class="dos-donts__card donts">
                <h4><i class="fa-solid fa-circle-xmark" style="color:#a33;margin-right:6px;"></i> Common Mistakes (Don'ts)</h4>
                <ul>
                    <li><i class="fa-solid fa-xmark" style="color:#a33;"></i> Don't apply hand lotion or cuticle oil right before applying.</li>
                    <li><i class="fa-solid fa-xmark" style="color:#a33;"></i> Don't use pure acetone if you plan to reuse your press-ons.</li>
                    <li><i class="fa-solid fa-xmark" style="color:#a33;"></i> Don't use your nails as tools (e.g. opening soda cans).</li>
                    <li><i class="fa-solid fa-xmark" style="color:#a33;"></i> Don't reuse adhesive tabs without wiping the back clean.</li>
                </ul>
            </div>
        </div>

        {{-- Need Personal Advice --}}
        <div style="text-align:center;margin-top:48px;background:#FAF5F2;border-radius:var(--radius-md);padding:28px;border:1px solid var(--border-soft);">
            <h4 style="font-size:1.2rem;margin-bottom:8px;">Have Questions About Sizing or Custom Fits?</h4>
            <p style="font-size:0.88rem;color:rgba(43,29,29,.75);margin:0 0 16px;">Our Karachi atelier is happy to review your nail measurements and recommend the best shape.</p>
            <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap;">
                <a href="https://ig.me/m/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                    <i class="fa-brands fa-instagram"></i> Message @alishe_nails
                </a>
                <a href="https://wa.me/923113925166" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Concierge
                </a>
            </div>
        </div>

    </div>

@endsection
