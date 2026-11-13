<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="navbar__brand" style="margin-bottom:14px;display:inline-flex;align-items:center;gap:10px;">
                <x-optimized-image src="images/logo.jpeg" alt="Alishe Nails Logo" class="footer__logo" loading="lazy" />
                <span style="font-size:1.2rem;font-weight:700;">Alishe Nails</span>
            </a>
            <p style="font-size:0.88rem;line-height:1.6;color:rgba(43,29,29,.75);margin-bottom:14px;">
                Couture handmade press-on nails crafted for elegance, zero damage, and reusability. Handcrafted with love in Karachi and delivered exclusively across the city.
            </p>
            <div class="footer-social" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                <a href="https://instagram.com/{{ config('services.instagram.handle', 'alishe_nails') }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:0.85rem;">
                    <i class="fa-brands fa-instagram" style="color:#C13584;font-size:1.1rem;"></i>
                    {{ '@'.config('services.instagram.handle', 'alishe_nails') }}
                </a>
                <a href="https://wa.me/923113925166" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:0.85rem;">
                    <i class="fa-brands fa-whatsapp" style="color:#14713c;font-size:1.1rem;"></i>
                    +92 311 3925166
                </a>
            </div>

            {{-- Verified Payment Badges --}}
            <div style="background:rgba(255,255,255,0.6);border:1px solid var(--border-soft);border-radius:8px;padding:10px 12px;font-size:0.75rem;color:var(--espresso);line-height:1.4;">
                <strong style="display:block;margin-bottom:4px;color:var(--rose-dark);"><i class="fa-solid fa-lock" style="color:var(--gold);"></i> 100% Advance Payment Verified:</strong>
                <span>SadaPay &amp; JazzCash (<code>03113925166</code> &middot; Alishba Naz)</span>
            </div>
        </div>

        <div>
            <h2 class="footer__heading">Couture Collection</h2>
            <ul>
                <li><a href="{{ route('shop.index') }}">All Collections</a></li>
                <li><a href="{{ route('shop.index', ['sort' => 'popular']) }}">Best Sellers</a></li>
                <li><a href="{{ route('shop.index', ['shape' => 'Almond']) }}">Almond Shapes</a></li>
                <li><a href="{{ route('shop.index', ['shape' => 'Coffin']) }}">Coffin &amp; Ballerina</a></li>
                <li><a href="{{ route('shop.index', ['shape' => 'Square']) }}">Square &amp; Oval</a></li>
            </ul>
        </div>

        <div>
            <h2 class="footer__heading">Client Care &amp; Guides</h2>
            <ul>
                <li><a href="{{ route('how-to-apply.index') }}">Application &amp; Sizing Guide</a></li>
                <li><a href="{{ route('policies.index') }}#returns-policy-heading">Hygiene &amp; No-Returns Policy</a></li>
                <li><a href="{{ route('policies.index') }}#shipping-policy-heading">Karachi Doorstep Delivery (48-96h)</a></li>
                <li><a href="{{ route('track-order.index') }}">Track Your Order</a></li>
                <li><a href="{{ route('contact.index') }}">VIP Concierge Help</a></li>
                <li><a href="{{ route('seller.apply') }}">Sell With Us (Partner Atelier)</a></li>
            </ul>
        </div>

        <div>
            <h2 class="footer__heading">The Atelier Newsletter</h2>
            <p style="font-size:.85rem;color:rgba(43,29,29,.7);margin-bottom:12px;">Receive early access to seasonal capsule drops &amp; bespoke nail inspo.</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="newsletter-form">
                @csrf
                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">
                <input type="email" name="email" placeholder="Enter your email" required aria-label="Email address for newsletter" style="font-size:0.85rem;">
                <button type="submit" class="btn btn-primary" aria-label="Subscribe to newsletter">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
            <small style="display:block;margin-top:8px;font-size:0.72rem;color:rgba(43,29,29,.82);">No spam. Only handcrafted beauty drops.</small>
        </div>
    </div>

    <div class="footer-bottom" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            &copy; {{ date('Y') }} Alishe Nails &middot; Karachi Atelier. Handcrafted with love.
        </div>
        <div style="font-size:0.75rem;color:rgba(43,29,29,.82);display:flex;gap:16px;">
            <a href="{{ route('policies.index') }}">Privacy Policy</a>
            <a href="{{ route('policies.index') }}">Terms &amp; Hygiene Standards</a>
            <a href="{{ route('seller.login') }}">Seller Portal</a>
            <a href="{{ route('admin.login') }}">Atelier Admin</a>
        </div>
    </div>
</footer>
