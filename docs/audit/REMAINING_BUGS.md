# Remaining bugs

Every line here was measured on 2026-11-15 against `https://alishenails.website/`
and against `main` at `0506ca5` plus the fixes in this run.

---

## OPEN-01 — 15 of 15 live products have no photograph (blocker, needs the owner)

Measured with HTTP `HEAD` against every interchangeable extension
(`.webp`, `.jpg`, `.jpeg`, `.png`) in `public/images/products`:

| # | Product slug | Result |
| --- | --- | --- |
| 1 | `bronze-dragon-eye-metallic-press-on-nails-with-application-kit` | no file |
| 2 | `bronze-metallic-dark-leopard-press-on-nails-with-application-kit` | no file |
| 3 | `champagne-cat-eye-heart-accent-press-on-nails-with-application-kit` | no file |
| 4 | `dark-charcoal-metallic-gold-french-press-on-nails-with-application-kit` | no file |
| 5 | `deep-plum-wine-glossy-press-on-nails-with-application-kit` | no file |
| 6 | `golden-hour-cat-eye-press-on-nails-with-application-kit` | no file |
| 7 | `hyper-realistics-press-on-nails-with-application-kit` | no file |
| 8 | `metallic-rose-leopard-print-press-on-nails-with-application-kit` | no file |
| 9 | `monochrome-checkered-star-press-on-nails-with-application-kit` | no file |
| 10 | `nude-floral-rhinestone-press-on-nails-with-application-kit` | no file |
| 11 | `rose-gold-shimmer-leopard-tip-press-on-nails-with-application-kit` | no file |
| 12 | `sage-green-velvet-cat-eye-press-on-nails-with-application-kit` | no file |
| 13 | `silver-glitter-cut-out-heart-press-on-nails-with-application-kit` | no file |
| 14 | `smokey-velvet-black-star-checkered-press-on-nails-with-application-kit` | no file |
| 15 | `white-french-star-ribbon-gem-press-on-nails-with-application-kit` | no file |

What I checked before concluding this:

* `public/images/products/<slug>.{webp,jpg,jpeg,png}` on the live server — all 404.
* 27 plausible shortened names (`deep-plum-wine-glossy`, `silver-glitter-heart`,
  `sage-green-velvet`, …) — all 404.
* `git log --all --diff-filter=A -- '*application-kit*' '*leopard*' '*cat-eye*'
  '*plum*' '*hyper-realistic*'` — no such file was **ever** committed.
* `git ls-files public/images/products` — the repository only holds the older
  catalogue (`rosy-quartz-ombre`, `blush-aura`, `midnight-espresso`,
  `classic-pearl-mauve`, `minimalist-dot`, `espresso-split`, … 15 products).

Conclusion: these 15 products were created on the live server and their photos
were uploaded there only. They are not in git, and they are not on the server now.
**This cannot be repaired from the repository.** It needs the original photographs
from the owner (WhatsApp originals, camera roll, or an AlifHost backup taken
before the files disappeared). No substitute or generated image will be committed.

### How to repair once the files exist

1. cPanel → File Manager → `public/images/products/` → upload the 15 photos.
   Any of these names works, one per product:
   `<product-slug>.jpg` or `<product-slug>.webp`.
2. Admin → **Image Doctor** (`/admin/images`) lists every file found and every
   product without an image; attach the right file to the right product. The page
   refuses 1×1 placeholder files and flags a file already used by another product.
3. Or, on a host with CLI: `php artisan images:doctor --repair` (only unambiguous
   1:1 name matches), or `php artisan images:doctor --from=/path/to/photos`.

---

## OPEN-02 — live server is not running `main`

Measured response headers from `https://alishenails.website/`:

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; ...
Cross-Origin-Resource-Policy: same-origin
```

`SecurityHeaders.php` on `main` emits a different, stricter policy and does not
set `Cross-Origin-Resource-Policy`. Deploying `main` (code + `public/` + `npm run
build` output) is required. This session has no cPanel access, so it is not done.

---

## OPEN-03 — no interactive browser test was possible from this session

This environment has no browser automation, so buttons, modals, cart quantity
steppers, gallery zoom and video playback were **not** clicked. HTTP-level
evidence is in `LIVE_BROWSER_RESULTS.md`; it proves pages render and assets
resolve, not that every control works. Interactive testing is still required
before sign-off.

---

## CLOSED in this run

| ID | Fix |
| --- | --- |
| NEW-01 | `public/css/app.css` malformed `.luxe-value-card h3` / `.shape-card h3` rules repaired — `npm run build` succeeds. |
| NEW-02 | `.htaccess` duplicate/conflicting CSP removed; now identical to `SecurityHeaders.php`, enforced by a test. |
| NEW-03 | Dead code removed from `Product::mediaSearchDirectories()`. |
| NEW-04 | 34 tracked product photos restored to the working tree (were deleted locally, present in `HEAD`). |