# Live checks — 2026-11-15

Target: `https://alishenails.website/`
Method: `Invoke-WebRequest` (HTTP GET/HEAD, redirects followed). No browser
automation is available in this session, so nothing below claims a click.

## 1. Page status codes

| Path | Status |
| --- | --- |
| `/` | 200 |
| `/shop` | 200 |
| `/shop?page=2` | 200 |
| `/shop?page=3` | 200 |
| `/cart` | 200 |
| `/checkout` | 200 |
| `/track-order` | 200 |
| `/contact` | 200 |
| `/custom-design` | 200 on the old build; **404 on current `main`** (feature removed) |
| `/about` | 200 |
| `/policies` | 200 |
| `/how-to-apply` | 200 |
| `/login` | 200 |
| `/register` | 200 |
| `/forgot-password` | 200 |
| `/sell-with-us` | 200 |
| `/seller/login` | 200 |
| `/admin/login` | 200 |
| `/sitemap.xml` | 200 |
| `/robots.txt` | 200 |

## 2. Temporary / diagnostic endpoints — all gone

| Path | Status |
| --- | --- |
| `/_deploy` | **404** |
| `/check-db-products` | **404** |
| `/fix-images` | **404** |
| `/storage_link.php` | **404** |
| `/public/deploy.php` | **404** |

## 3. Product detail pages

All 15 shop products return **200**. Every one of them renders the
`Image unavailable` placeholder (1–5 times per page, because the same product
appears in the hero, gallery and related blocks).

## 4. Product photography — the actual defect

`HEAD` request for each product against `<slug>.{webp,jpg,jpeg,png}`:

* **15 / 15 products: no image file exists on the server.**
* The catalogue photos that *do* exist on the server belong to the older
  catalogue (`rosy-quartz-ombre.jpg`, `blush-aura.jpg`, `midnight-espresso.jpg`,
  `golden-french.jpg`, `ivory-blossom-vine.jpg`, `pink-marble.jpg`,
  `gilded-rose-dust.jpg`, `modern-french-matte.jpg`, `classic-pearl-mauve.jpg`,
  `minimalist-dot.jpg`, `espresso-split.jpg`) and are referenced by the homepage
  Instagram strip, which renders correctly.

Homepage contains 19 `Image unavailable` placeholders for 15 products, because
best-seller and featured sections repeat the same cards.

## 5. Response headers (measured)

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; ...
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
Cross-Origin-Opener-Policy: same-origin
Cross-Origin-Resource-Policy: same-origin
Set-Cookie: alishe_nails_session=…; secure; httponly; samesite=lax
```

Session cookie is `Secure` + `HttpOnly` + `SameSite=Lax`. The CSP is the
pre-`main` policy, and `Cross-Origin-Resource-Policy` is still present, i.e. the
live server has **not** been redeployed with `main` (see `REMAINING_BUGS.md`
OPEN-02).

## 6. What is still untested

Interactive behaviour — add-to-cart, quantity steppers, coupon apply, checkout
submission, gallery zoom, video playback, admin and seller dashboards, mobile
layout — was not exercised. There is no browser automation in this environment;
sign-off on those paths still needs a manual pass.