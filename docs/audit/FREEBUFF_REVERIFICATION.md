# Freebuff work — independent re-verification

Repository: `mohsin-gohar/Alishe_Nails`
Compared revision: `0506ca5` (after `git pull origin main` on 2026-11-15)
Live site checked: `https://alishenails.website/`

This document records what I checked myself, not what a previous report claimed.

---

## 1. Previously fixed — and independently verified

| Item | Evidence |
| --- | --- |
| Temporary deployment endpoints removed | `routes/web.php` contains no `/_deploy`, `/check-db-products` or `/fix-images` route (`git grep` returns nothing outside docs). Live probes: `/_deploy`, `/check-db-products`, `/fix-images`, `/storage_link.php`, `/public/deploy.php` all return **404**. |
| Deploy/recovery files deleted from the repo | `deploy.php`, `deploy/alifhost/storage_link.php`, `remove_temp_routes.php`, `fix_htaccess.py`, `image_optimizer.php`, `routes/web.php.backup` are absent from `HEAD` (`git show --stat 0506ca5`). |
| Order-tracking JSON no longer leaks private data | `TrackOrderController::search()` builds an explicit whitelist (order number, status, payment status, created_at, total, line items). The Eloquent model is never serialised. `Order::$hidden` also covers `access_token` and `transaction_reference`. Covered by `SecurityHardeningTest` (2 tests). |
| Guest order confirmation requires a signature | `CheckoutController::success()` compares `?signature=` with `hash_equals($order->access_token)` and 403s otherwise. Covered by `OrderAuthorizationTest`. |
| Root-relative media URLs | `Product::rootRelativeUrl()` / `ImageUrl::relativeUrl()` derive the path from `public_path()` and the resolved document root instead of trusting `APP_URL`. Covered by `ProductMediaPathTest`. |
| WebP/JPG interchange + legacy folder lookup | `Product::mediaSearchDirectories()`, `resolveMediaFile()` (5-step, all real `is_file()` checks) and `ImageUrl::webpVariant()`. |
| Image diagnostics command | `app/Console/Commands/DoctorImages.php` — reports server capability, files on disk, records pointing at missing files, orphaned files, and repairs only unambiguous 1:1 matches. |
| Admin Image Doctor | `AdminImageController` + `resources/views/admin/images/index.blade.php` (routes `/admin/images`, `/admin/images/attach`, `/admin/images/{product}/attach`). Refuses sub-16px placeholder files and never guesses. |

## 2. Previously claimed fixed — but NOT actually live

| Claim | What I measured on 2026-11-15 |
| --- | --- |
| "CSP relaxed to `img-src 'self' data: blob: https:`, `Cross-Origin-Resource-Policy` removed" | Live `Content-Security-Policy` is still the **old** policy: `img-src 'self' data: blob: https://*.fbcdn.net https://*.cdninstagram.com https://lookaside.fbsbx.com`, and `Cross-Origin-Resource-Policy: same-origin` is still sent. The live server is running an older build than `main`. |
| "Product images fixed" | **15 of 15** live products still render "Image unavailable" (see `REMAINING_BUGS.md`). No code change can restore a file that is not on disk. |

The live server therefore needs a **deploy of `main`**. Nothing in the repository is wrong with these two items; the gap is deployment, not code.

## 3. Newly discovered in this pass

| ID | Severity | Finding |
| --- | --- | --- |
| NEW-01 | High | `public/css/app.css` was syntactically invalid: `.luxe-value-card h3,` and `.shape-card h3,` were left without a selector body (`.luxe-value-card h3,\n  font-size: …`), so the block had one `}` too many. `npm run build` failed with `[@tailwindcss/vite:generate:build] Missing opening {`. The file is committed broken (present in `1883315` and `6d53b6c` too), so the production build has been broken for several commits. **Fixed.** |
| NEW-02 | Medium | `.htaccess` declared a second, weaker `Content-Security-Policy` (`'unsafe-eval'`, `default-src 'self' data: https:`, `style-src … https:`) while `SecurityHeaders.php` declares a strict one. Browsers enforce the intersection of both policies, so the two files could silently disagree. **Fixed** — the values are now identical, and a regression test asserts they stay identical. |
| NEW-03 | Medium | `Product::mediaSearchDirectories()` contained a dead `if (is_dir($pubSub)) { }` block with an unused `$pubSub`. **Removed.** |
| NEW-04 | Low | The working tree had **deleted 34 tracked product images** (`git status` showed ` D public/images/products/*`). They were intact in `HEAD`. A commit from that state would have deleted the originals from the repository. **Restored with `git restore`; the tree is clean for `public/images`.** |

## 4. Fixed in this run

1. `.htaccess` CSP aligned with `SecurityHeaders.php`; `'unsafe-eval'`, `data:` and wildcard `https:` removed from the static-asset policy.
2. `public/css/app.css` malformed rules repaired — `npm run build` now succeeds (`✓ built in 506ms`).
3. Dead code removed from `Product::mediaSearchDirectories()`.
4. 34 tracked product photos restored to the working tree.
5. Regression tests added to `tests/Feature/SecurityHeadersTest.php`.

## 5. Requires AlifHost access or the original photographs

* 15 live products have no image file on the server under any extension, and none exists in git history. The originals must come from the owner (WhatsApp originals / camera roll / hosting backup) — see `REMAINING_BUGS.md`.
* Deploying `main` to the live server requires cPanel access, which this session does not have.