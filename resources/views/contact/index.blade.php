@extends('layouts.app')
@section('title', 'Contact — Alishe Nails')

@section('meta_description', 'Talk to the Alishe Nails concierge for sizing help, order updates and bespoke design enquiries — WhatsApp, Instagram or the contact form, answered within a few hours.')

@section('content')

    <div class="page-header">
        <h1>Get in Touch</h1>
        <p>Questions about sizing, an order, or a custom set? We'd love to hear from you.</p>
    </div>

    <div class="container">
        <div class="contact-layout">
            {{-- ---------- Contact form ---------- --}}
            <div>
                <h2 style="margin-bottom:20px;">Send us a message</h2>

                <form action="{{ route('contact.store') }}" method="POST">
                    {{-- Honeypot: hidden from real visitors via CSS; ContactRequest rejects any submission that fills this in. --}}
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">
                    @csrf

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <div class="error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-field">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone') <div class="error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-field full">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                            @error('email') <div class="error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-field full">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                            @error('message') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            {{-- ---------- Contact info ---------- --}}
            <div>
                <div class="contact-info-card">
                    <h3>Instagram</h3>
                    <p style="margin:8px 0 12px;">Follow along for new drops and behind-the-scenes.</p>
                    <a href="https://instagram.com/{{ config('services.instagram.handle') }}"
                       class="btn btn-outline btn-sm" target="_blank" rel="noopener">
                        <i class="fa-brands fa-instagram"></i> {{ '@'.config('services.instagram.handle') }}
                    </a>
                </div>

                <div class="contact-info-card">
                    <h3>WhatsApp</h3>
                    <p style="margin:8px 0 12px;">Chat with us directly for instant support and sizing guidance.</p>
                    <a href="https://wa.me/923113925166"
                       class="btn btn-outline btn-sm" target="_blank" rel="noopener" style="color:#14713c;border-color:#14713c;">
                        <i class="fa-brands fa-whatsapp"></i> +92 311 3925166
                    </a>
                </div>

                <div class="contact-info-card">
                    <h3>Contact Information</h3>
                    <p style="margin:8px 0 4px;"><i class="fa-solid fa-envelope"></i> hello@alishenails.com</p>
                    <p style="margin:0 0 4px;"><i class="fa-solid fa-location-dot"></i> Karachi, Pakistan</p>
                    <p style="margin:4px 0 0;font-size:0.85rem;color:#6b5a5c;"><i class="fa-solid fa-truck-fast"></i> Exclusively Delivering Across Karachi (48-96 hrs)</p>
                </div>
            </div>
        </div>
    </div>

@endsection
