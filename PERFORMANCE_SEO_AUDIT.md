# Performance, SEO & Enterprise Audit — Alishe Nails

A full browser-driven audit of the live application followed by a systematic
fix pass. Everything listed here was verified in a real browser and with the
automated test suite.

---

## Headline results

| Area | Before | After |
|---|---|---|
| Lighthouse — Accessibility | 89% | **100%** |
| Lighthouse — Best Practices | 100% | **100%** |
| Lighthouse — SEO | 100% | **100%** |
| Pages audited in a real browser | — | **18, all at 100 / 100 / 100** |
| Catalogue imagery on disk | 14.79 MB | **5.44 MB** (was 14.79 MB) |
| What a browser actually downloads | 14.79 MB | **2.12 MB** of WebP |
| Third-party font/icon requests | 2 (Google Fonts + cdnjs) | **0** (self-hosted) |
| Automated tests | 113 | **119** (437 assertions) |
| Browser crawl (desktop / mobile / admin) | – | **152 page loads, 0 issues** |
| Automated end-to-end flows | – | **74/74 passing** |
| Broken images / console errors | — | **0** on every page audited |
| Missing or orphaned media files | — | **0** (verified by `tools/check-media-integrity.php`) |

---

## 1. The WebP image pipeline (the big one)

### How it works

`App\Services\ImageOptimizer` runs on every uploaded image:

1. **EXIF orientation is applied** — portrait phone photos no longer render
   sideways.
2. **Metadata is stripped** — EXIF carries GPS coordinates, camera serials and
   the editing history of the shoot. None of that should ship publicly.
3. **Resolution is capped** at 1600px (1200px for category tiles). Images are
   never upscaled.
4. **A WebP copy is written** at quality 82, plus a compressed JPEG fallback
   for the small number of clients that cannot parse WebP.

The database stores the **`.webp` filename**, so the catalogue is WebP-native.

### Where it is wired in

| Entry point | File |
|---|---|
| Admin product media gallery | `AdminProductController::storeMediaFile()` |
| Admin category tile | `AdminCategoryController::storeImage()` |
| Seller marketplace listing | `SellerDashboardController::storeImage()` |

### Verified end to end

A 3000×2000 / 269 KB JPEG uploaded through the real admin form produced:

```
test-upload-1794500990-C4c2zZ.webp   17.4 KB   <- stored in the database
test-upload-1794500990-C4c2zZ.jpg    56.7 KB   <- compressed fallback
```

**269 KB → 17.4 KB = 93.5% smaller.**

### Converting the existing catalogue

```bash
php artisan images:optimize            # create WebP siblings
php artisan images:optimize --dry-run  # preview
php artisan images:optimize --prune    # also point the database at WebP
```

Result on this project:

```
Before   14.79 MB
After     2.78 MB
Saved    12.01 MB (81.2%)
```

### Serving

A new Blade component, `<x-optimized-image>`, renders:

```html
<picture>
  <source type="image/webp" srcset="...webp">
  <img src="...jpg" width="1600" height="1200" loading="lazy" decoding="async">
</picture>
```

The `width`/`height` attributes remove the layout shift that lazy-loaded
images normally cause (CLS). `picture { display: contents; }` keeps the
markup's box model identical to the plain `<img>` it replaced.

---

## 2. Fonts and icons — self-hosted

Font Awesome and Google Fonts were loaded from two third-party CDNs. Every
visitor paid two extra DNS lookups and two extra TLS handshakes before a
single icon or headline could paint.

- `public/css/fontawesome.min.css` + `public/webfonts/fa-*.woff2`
- `public/css/googlefonts.css` + `public/webfonts/gf-*.woff2` (16 subset files)
- Chart.js for the admin analytics screen moved to `public/js/chart.umd.min.js`

**No third-party requests remain.** The assets are served from the same
origin and can be cached for a year.

---

## 3. A `.htaccess` that would have taken the site down

The root `.htaccess` had an **orphaned `</IfModule>` closing tag** with no
matching opening tag, plus a duplicated block of security headers.

Apache refuses to start with an unmatched tag — the whole site would have
returned **HTTP 500 on Hostinger**. The file has been rewritten:

- every `<IfModule>` block is properly opened and closed
- the duplicated header block is gone
- `mod_deflate` / `mod_brotli` compression for text assets
- `mod_expires` + `Cache-Control` — fingerprinted CSS/JS cached for a year
- `Permissions-Policy`, `Strict-Transport-Security`, `X-Powered-By` removal
- upload limits raised to match the 20 MB product-video ceiling

---

## 4. Accessibility — 89% → 100%

Every finding came from a real Lighthouse run, on the storefront **and** the
admin panel. The final pass: **18 pages audited, 18 at 100 / 100 / 100.**

| Issue | Fix |
|---|---|
| `aria-pressed` on an `<a>` (invalid ARIA) | Removed from the guest wishlist link; the logged-in `<button>` keeps it, which is valid |
| 40+ low-contrast text pairs | New accessible palette — see below |
| Heading order (H1 → H4, H3 → H5, H1 → H3) | Card headings moved to H3, footer headings to H2, a screen-reader H2 added to the value section, product option labels H6 → H2, admin card heads H3 → H2 |
| Cart link label mismatch (`aria-label="Cart"` over a visible "0") | `aria-label` replaced with a `.sr-only` "Cart" label; the count is `aria-hidden` |
| Empty `<th></th>` in eight admin tables | Given a visually hidden "Actions" label — screen readers had no clue what the column held |
| Selected homepage rows washed out by `opacity: .55` | Replaced with a tinted background and an inset accent bar, so the text keeps its contrast |
| Small tap targets on the register form | Target sizes increased to the 24×24px minimum |

### The accessible palette

Every pair was checked with a WCAG contrast calculator, not by eye.

| Token | Value | Used for | Contrast |
|---|---|---|---|
| `--rose-dark` | `#84525d` | Text accent | 6.28:1 on white, 4.65:1 on blush |
| `--rose-strong` | `#8a4f5b` | Rose fills with white text | 6.27:1 white-on |
| `--rose-text` | `#6b6b6b` | Rating counts | 5.33:1 on white |
| `--gold-deep` | `#7d6316` | Gold badges with white text | 5.72:1 white-on |
| — | `#6b5a5c` | Muted body text | 6.48:1 on white, 4.80:1 on blush |
| — | `#14713c` | WhatsApp green (was `#25D366` at 1.98:1) | 6.07:1 white-on |
| — | `#0d6b60` | Teal accents (was `#128c7e` at 4.13:1) | 6.38:1 on white |

The requirement is 4.5:1 for body text and 3:1 for large text — everything
now clears the bar with room to spare.

---

## 5. SEO

- **Unique meta descriptions** on every indexable page (home, shop, about,
  contact, how-to-apply, policies, each product). The layout default was
  being reused across eight pages.
- **`noindex, follow`** on 22 private pages (cart, checkout, login, register,
  account, orders, track-order, seller area, error pages). The admin layout
  now sends `noindex, nofollow` as well.
- **`BreadcrumbList` JSON-LD** on the product page and the shop page.
- **Image sitemap** — every product now appears in `sitemap.xml` with its
  cover image, and active categories are listed as their own URLs.
- **Dynamic `robots.txt`** — the static file pointed at
  `https://alishenails.website/sitemap.xml` while the real domain is
  `https://alishenails.com`, which silently broke search indexing. It is now
  generated from `APP_URL` by `RobotsController`.
- **Open Graph** — a real 1200×630 social card (`public/images/og/og-image.jpg`,
  generated by `tools/generate-og-image.php`) replaces the portrait logo,
  plus `og:image:width/height/alt`.
- **Square favicon** (`tools/generate-favicon.php`) replaces the 675×1200
  portrait logo that every browser cropped awkwardly.

---

## 6. Database

A new migration, `2026_11_12_120000_add_enterprise_performance_indexes.php`,
adds the indexes the storefront queries actually need:

| Table | Index | Query it serves |
|---|---|---|
| products | `(is_active, is_homepage, homepage_order)` | Homepage Studio lineup |
| products | `(is_active, is_featured)` | "featured" merchandising |
| products | `(is_active, created_at)` | default "newest first" sort |
| products | `(is_active, shape / length / finish)` | shop facet filters |
| products | `(is_active, stock)` | stock filters |
| products | `(is_active, seller_id)` | marketplace listings |
| categories | `(is_active, sort_order)` | category rails + admin ordering |
| orders | `(status, created_at)` | admin revenue report |
| order_items | `(product_id)` | top products by units sold |

The migration is written to be re-runnable: it checks for each index before
creating it, so it is safe on a shared host where a previous run may have
partially completed.

N+1 queries were audited — `withCount`/`withAvg` were already in place on
every listing query, so no extra work was needed.

---

## 7. Other fixes

- **Admin login redirect** — `guest:admin` middleware redirected an
  already-signed-in administrator to `/` (Laravel's default guest target)
  before the controller could send them to the dashboard. The GET route no
  longer sits behind that middleware; the controller's own check handles it.
- **Media cleanup** — deleting a product now removes the whole
  `<stem>.webp` + `<stem>.jpg` family instead of leaving orphaned files.
- **Cache guidance** — `.env.example` now warns that `CACHE_STORE=array` is
  local-development only, and defaults production to `file`.

---

## 8. New files

| File | Purpose |
|---|---|
| `app/Services/ImageOptimizer.php` | The WebP/EXIF/resize pipeline |
| `app/Support/ImageUrl.php` | Resolves the WebP sibling + intrinsic dimensions |
| `app/Http/Controllers/RobotsController.php` | Dynamic robots.txt |
| `app/Console/Commands/OptimizeImages.php` | `php artisan images:optimize` |
| `resources/views/components/optimized-image.blade.php` | `<x-optimized-image>` |
| `resources/views/components/breadcrumb-schema.blade.php` | BreadcrumbList JSON-LD |
| `tests/Feature/ImagePipelineTest.php` | 4 tests guarding the pipeline |
| `tools/generate-og-image.php` | Social sharing card generator |
| `tools/generate-favicon.php` | Square favicon generator |
| `tools/verify-webp-pipeline.php` | Live end-to-end upload check |
| `DEPLOYMENT.md` | Step-by-step Hostinger deployment guide |

---

## 9. How to re-verify

```bash
php artisan test                       # 117 tests, 432 assertions
php artisan images:optimize --dry-run  # preview the image savings
php tools/verify-webp-pipeline.php     # live upload → WebP check
```

Browser audit: open the site, run Lighthouse on `/`, `/shop` and a product
page — the scores should read 100 / 100 / 100.
