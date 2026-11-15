# Local rework — 2026-11-15

Scope of this pass, all verified by running the app on `127.0.0.1:8000`
with a fresh SQLite database (`php artisan migrate:fresh --seed`).

---

## 1. Requested changes

### 1.1 Custom Design removed

The feature was already gone from `main` (the controller, routes and views were
deleted in an earlier commit). This pass removed what was left behind:

| Leftover | Action |
| --- | --- |
| `/custom-design` returning 200 on the live server | Confirmed it is **404** on current `main` (the live box simply runs an older build). |
| "Custom millimeter measurements" copy on the homepage | Still present — it describes the *product* (you can order your own measurements), not the removed page, so it stays. |

### 1.2 Delivery is a flat PKR 300 and it is always added

Before, the delivery line could render **"Coordinated directly"** because the fee
was looked up per area and could legitimately resolve to `0` (a matching row with
a zero fee, or a free-shipping threshold being met). The customer then saw a
total that silently excluded delivery.

Changes:

| File | Change |
| --- | --- |
| `app/Models/ShippingRate.php` | New `FLAT_FEE = 300.00`. `calculateFee()` now always returns that fee; `is_free` is always `false`; `free_shipping_threshold` is always `null`. The zone label is still looked up so the checkout can say "Karachi (DHA)". |
| `resources/views/checkout/index.blade.php` | Delivery row always prints `PKR 300`; the `Coordinated directly` branch is gone. Sub-line reads "Products + PKR 300 delivery". |
| `public/js/app.js` | The live estimate handler always writes `PKR <fee>`; the "Free Delivery" / "Coordinated directly" branches are removed. |
| `resources/views/cart/index.blade.php` | Delivery row shows the flat 300 (the "Free" branch can no longer trigger). |
| `admin/orders/show`, `customer/orders/show`, `track-order/index` | Order views always show `PKR <shipping>` instead of `Coordinated directly`. |
| `resources/views/products/show.blade.php` | Bullet now reads "Flat PKR 300 delivery anywhere in Karachi, added at checkout." |
| `AdminShippingController` | Store/update validate only city + area + active. The fee is taken from `ShippingRate::FLAT_FEE`, never from the request, so a tampered form cannot change what a customer pays. |
| `admin/shipping/*` | The admin screen is now a **Delivery Zones** list (fee column is read-only). The misleading "free shipping above" field was removed. |

Evidence (live run against `127.0.0.1:8000`):

```
cart             -> PKR 2,500 + PKR 300 = PKR 2,800
cart (qty 2)     -> PKR 5,600 + PKR 300 = PKR 5,900
checkout         -> "PKR 300" present, "Coordinated directly" absent
place-order btn  -> "PKR 2,800"
order row        -> subtotal=2500 shipping=300 total=2800
```

### 1.3 Admin pages removed: Coupons, Subscriptions, Campaign Pitches

| Removed | Replacement |
| --- | --- |
| `AdminCouponController`, `admin.coupons.*` routes, `admin/coupons` view | None — coupon *codes* still work at checkout, they are just no longer managed from the dashboard. |
| `AdminSubscriptionController`, `admin.subscriptions.*`, `admin/subscriptions` view | **Subscription verification moved onto the seller's own page** (`Sellers → Details → Subscription`) with Activate / Reject buttons, backed by new routes `admin.marketplace.sellers.subscription.activate` / `…reject`. Without this, removing the screen would have left no way to activate a seller and every marketplace listing would stay hidden. |
| `AdminCampaignPitchController`, `admin.campaign-pitches.*`, view + test | None — pitches are still stored, reachable via the database only. |

`/admin/coupons`, `/admin/subscriptions`, `/admin/campaign-pitches` now return
**404**, asserted by `AdminPagesRenderTest::test_removed_admin_screens_are_gone`.

### 1.4 Premium / professional polish

* Missing photos no longer say "Image unavailable". They render a branded
  monogram tile (`.img-placeholder__mark`) with "Photo coming soon" — an
  intentional empty state instead of a broken one (product cards + product page).
* Cart rows now show the **current** database price and a `max` equal to real
  stock, instead of the price frozen into the session and a hard-coded 20.
  Before, a price change made the line item disagree with the order total.
* Dead admin views removed (`admin/auth/{forgot-password,register,reset-password}` —
  only the login view is routed).

---

## 2. Bugs found while auditing

### BUG-01 — `npm run build` was broken on `main`

`public/css/app.css` contained two selector rules with no declaration block:

```css
.luxe-value-card h3,
  font-size: 0.95rem;   /* <- no opening brace */
```

Tailwind aborted with `Missing opening {`. Fixed both (`.luxe-value-card h3`,
`.shape-card h3`). `npm run build` now succeeds (49.14 kB CSS).

### BUG-02 — the slug fallback could attach the wrong photo

`Product::resolveFallbackImage()` step 2 took `$matches[0]` from the normalised
index without the "only one photo" guard that `resolveMediaFile()` uses. Two
different products whose filenames normalise to the same key could therefore
receive each other's picture — the exact thing the class docblock promises never
to do. Now it groups by photo (`groupByPhoto`) and refuses when more than one
photo matches. Covered by two new tests.

### BUG-03 — cart total and cart lines could disagree

`CartController::index` rendered `$item['price']` (the price captured when the
item was added) while `Cart::subtotal()` correctly uses the live price. Fixed by
passing a live price/stock map into the view.

### BUG-04 — "Best Sellers" footer link sorted by newest

`/shop?sort=popular` fell through the `match` to `latest()`. `popular` is now an
alias for best-selling.

---

## 3. Verification performed

| Check | Result |
| --- | --- |
| `php artisan migrate:fresh --seed` | 12 products, 0 missing images |
| `php artisan test` | **147 passed (514 assertions)** |
| `php vendor/bin/pint --test` | passed |
| `npm run build` | `✓ built in 309ms` |
| HTTP crawl of 22 public URLs | all 200, `/custom-design` and the three admin URLs 404 |
| 16 authenticated admin pages | all 200 |
| 41 internal links scraped from 13 pages | **0 broken** |
| 12 product pages | all 200, 0 "Image unavailable" |
| Cart → checkout → order | total = products + 300 (2500 + 300 = 2800) |
| Customer register → /account/profile, /orders, /wishlist | 200 |

### Not verified

There is no browser automation in this environment. Buttons were exercised at
the HTTP level (forms posted, redirects followed); hover states, gallery zoom,
animations and mobile breakpoints were **not** visually checked.