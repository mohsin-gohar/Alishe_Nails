# Enterprise-Grade Website Audit & Production Readiness Review
## Deliverable 2: Master Consolidated Findings Log

**Project:** Alishe Nails (`alishe-nails`)  
**Audit Framework:** Enterprise SaaS & OWASP Top 10 Application Security Standard  
**Priority Classification:**
- **P0 (Critical / Blocker):** Must be resolved prior to public production deployment.
- **P1 (High Priority):** Significant operational, business logic, or data integrity risk.
- **P2 (Medium Priority):** Performance bottlenecks, scalability constraints, API resilience.
- **P3 (Low / Polish):** UI/UX refinements, accessibility improvements, code maintainability.
- **P4 (Future / Enhancement):** Non-blocking enhancements, third-party integrations, long-term roadmap.

---

## 1. Master Findings Matrix

| Finding ID | Title | Severity | Category | File Reference | Status |
|---|---|:---:|:---:|---|:---:|
| **SEC-01** | Production Admin Seeder Default Credential Exposure | **P0** | Security | [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L51-L53) / [AdminSeeder.php](file:///f:/laravelproject/alishe-nails/database/seeders/AdminSeeder.php#L19-L23) | Documented |
| **SEC-02** | Session Cookie `Secure` Flag Enforcement in HTTPS | **P0** | Security | [config/session.php](file:///f:/laravelproject/alishe-nails/config/session.php#L16) / [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L28) | Documented |
| **SEC-03** | Web Server Script Execution Disablement in Upload Directories | **P0** | Security | [CustomDesignController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/CustomDesignController.php#L21) / [AdminProductController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/Admin/AdminProductController.php#L146) | Documented |
| **DAT-01** | Marketplace Catalog Active Seller Subscription Filtering | **P1** | Business Logic | [Product.php](file:///f:/laravelproject/alishe-nails/app/Models/Product.php#L96-L104) / [ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L14-L18) | Documented |
| **DAT-02** | Failed Queue Job Monitoring & Dead Letter Logging | **P1** | Reliability | [SendOrderEmails.php](file:///f:/laravelproject/alishe-nails/app/Jobs/SendOrderEmails.php#L19-L35) / [config/queue.php](file:///f:/laravelproject/alishe-nails/config/queue.php#L123-L128) | Documented |
| **DAT-03** | Transaction Rollback Stock Restoration on Order Cancellation | **P1** | Data Integrity | [AdminOrderController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/Admin/AdminOrderController.php#L70-L85) | Documented |
| **DAT-04** | Commission Rate Precision and Snapshot Locking | **P1** | Business Logic | [CheckoutController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/CheckoutController.php#L228-L247) | Documented |
| **PRF-01** | High-Concurrency Database Session Contention (Redis Transition) | **P2** | Performance | [config/session.php](file:///f:/laravelproject/alishe-nails/config/session.php#L4) / [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L26) | Documented |
| **PRF-02** | Facet Filter Sidebar Aggregation Query Optimization | **P2** | Performance | [ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L55-L63) | Documented |
| **PRF-03** | Full-Text Search Scaling on Expanding Product Catalog | **P2** | Scalability | [ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L19-L21) | Documented |
| **PRF-04** | Static Asset Cache Control Headers & Gzip/Brotli Compression | **P2** | Performance | [public/build/manifest.json](file:///f:/laravelproject/alishe-nails/public/build/manifest.json) / [SecurityHeaders.php](file:///f:/laravelproject/alishe-nails/app/Http/Middleware/SecurityHeaders.php#L17-L43) | Documented |
| **PRF-05** | Image CDN Delivery & Dynamic WebP Transformation | **P2** | Performance | [Product.php](file:///f:/laravelproject/alishe-nails/app/Models/Product.php#L151-L169) / [product-card.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/components/product-card.blade.php) | Documented |
| **UI-01** | Accessible Labels on Icon-Only Buttons & Action Controls | **P3** | Accessibility | [navbar.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/components/navbar.blade.php) / [cart/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/cart/index.blade.php) | Documented |
| **UI-02** | Viewport Padding & Table Overflow on Ultra-Narrow Displays (<375px) | **P3** | UI/UX | [admin/orders/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/admin/orders/index.blade.php) / [seller/dashboard.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/seller/dashboard.blade.php) | Documented |
| **UI-03** | Strict Content Security Policy Script Noncing | **P3** | Security / Polish | [SecurityHeaders.php](file:///f:/laravelproject/alishe-nails/app/Http/Middleware/SecurityHeaders.php#L29) | Documented |
| **UI-04** | Standardized Form Autofill Attributes (`autocomplete`) | **P3** | UX / Usability | [checkout/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/checkout/index.blade.php) / [auth/login.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/auth/login.blade.php) | Documented |
| **UI-05** | Structured Data (Schema.org / JSON-LD) Rich Product Snippets | **P3** | SEO / Search | [products/show.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/products/show.blade.php) / [home/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/home/index.blade.php) | Documented |
| **OPS-01** | Production Scheduler Cron Setup & Supervisor Daemonization | **P4** | Operations | [README.md](file:///f:/laravelproject/alishe-nails/README.md) / [routes/console.php](file:///f:/laravelproject/alishe-nails/routes/console.php) | Documented |
| **OPS-02** | Automated WhatsApp Order Confirmation Webhook Integration | **P4** | Integration | [CheckoutController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/CheckoutController.php#L141) | Documented |
| **OPS-03** | Automated Database & Media S3/R2 Off-Site Backup Strategy | **P4** | Disaster Recovery | [config/filesystems.php](file:///f:/laravelproject/alishe-nails/config/filesystems.php) | Documented |
| **OPS-04** | Instagram Graph API Long-Lived Token Auto-Refresh Task | **P4** | Maintenance | [AdminInstagramController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/Admin/AdminInstagramController.php) | Documented |

---

## 2. Detailed Findings Breakdown

### [P0 - Critical / Blockers]

#### Finding SEC-01: Production Admin Seeder Default Credential Exposure
- **Severity:** P0 (Critical)
- **Category:** Application Security / Credentials Management
- **Affected Files:**
  - [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L51-L53)
  - [database/seeders/AdminSeeder.php](file:///f:/laravelproject/alishe-nails/database/seeders/AdminSeeder.php#L19-L23)
- **Evidence:**
  ```dotenv
  ADMIN_EMAIL=admin@alishenails.com
  ADMIN_SEED_PASSWORD=ChangeMe123!
  ```
  In [AdminSeeder.php](file:///f:/laravelproject/alishe-nails/database/seeders/AdminSeeder.php#L21-L23):
  ```php
  if (app()->environment('production') && (! $password || $password === 'ChangeMe123!')) {
      throw new \RuntimeException('Set a unique ADMIN_SEED_PASSWORD before seeding production.');
  }
  ```
- **Risk Analysis:**
  The developer thoughtfully added a runtime check that blocks seeding with `ChangeMe123!` in production. However, if an administrator seeds in a staging environment that is promoted to production or sets an insecure password during deployment, the admin portal (`/admin/login`) is exposed to unauthorized access.
- **Remediation:**
  1. Mandate that in production CI/CD, `ADMIN_SEED_PASSWORD` is injected via a secure secrets manager or generated using `openssl rand -base64 24`.
  2. Implement Multi-Factor Authentication (TOTP 2FA) or email verification alerts on admin login.

---

#### Finding SEC-02: Session Cookie `Secure` Flag Enforcement in HTTPS
- **Severity:** P0 (Critical)
- **Category:** Session Security & Transport Layer
- **Affected Files:**
  - [config/session.php](file:///f:/laravelproject/alishe-nails/config/session.php#L16)
  - [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L28)
- **Evidence:**
  ```php
  // config/session.php line 16:
  'secure' => env('SESSION_SECURE_COOKIE'),
  'http_only' => true,
  'same_site' => 'lax',
  ```
- **Risk Analysis:**
  If the application is deployed behind an SSL-terminating reverse proxy (like Cloudflare, AWS ALB, or Nginx) and `SESSION_SECURE_COOKIE` is not explicitly set to `true`, the `alishe_nails_session` cookie will be transmitted without the `Secure` directive. Attackers on an untrusted network (e.g., public Wi-Fi) could sniff unencrypted cookies if any plain HTTP request occurs before HSTS redirects.
- **Remediation:**
  In production `.env`, explicitly define:
  ```dotenv
  SESSION_SECURE_COOKIE=true
  SESSION_HTTP_ONLY=true
  SESSION_SAME_SITE=lax
  ```
  Ensure `App\Http\Middleware\TrustProxies` is properly configured if running behind a reverse proxy so `$request->secure()` correctly resolves HTTPS.

---

#### Finding SEC-03: Web Server Script Execution Disablement in Upload Directories
- **Severity:** P0 (Critical)
- **Category:** Server Security / Remote Code Execution Defense
- **Affected Files:**
  - [CustomDesignController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/CustomDesignController.php#L21) (`storage/app/public/custom-designs`)
  - [AdminProductController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/Admin/AdminProductController.php#L135-L149) (`public/images/products`, `public/videos/products`)
- **Evidence:**
  Application-level validation in `CustomDesignRequest` and `ProductRequest` strictly enforces extensions (`jpg, jpeg, png, webp, mp4, webm`) and MIME types. File storage uses random slugs. However, if a web server is misconfigured to pass all files matching `.php` in `/storage/` or `/images/` to FastCGI/PHP-FPM, defense-in-depth is bypassed.
- **Risk Analysis:**
  If an attacker manages to bypass MIME checking (e.g., polyglot files or server-side parser quirks), the file could be directly accessed via `https://alishenails.com/storage/custom-designs/malicious.php` and executed by PHP-FPM.
- **Remediation:**
  Enforce Nginx server block rule disabling script execution inside upload folders:
  ```nginx
  location ~* ^/(storage|images|videos)/.*\.php$ {
      deny all;
      return 404;
  }
  ```

---

### [P1 - High Priority / Business Logic & Data Integrity]

#### Finding DAT-01: Marketplace Catalog Active Seller Subscription Filtering
- **Severity:** P1 (High)
- **Category:** Business Logic & Marketplace Integrity
- **Affected Files:**
  - [app/Models/Product.php](file:///f:/laravelproject/alishe-nails/app/Models/Product.php#L96-L104)
  - [app/Http/Controllers/ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L14-L18)
- **Evidence:**
  In `Product.php`:
  ```php
  public function scopeActive($query)
  {
      return $query->where('is_active', true)->where(function ($query) {
          $query->whereNull('seller_id')
              ->orWhereHas('seller', fn ($seller) => $seller->where('status', 'approved')->whereHas('subscriptions', function ($subscription) {
                  $subscription->where('status', 'active')->where('expires_at', '>', now());
              }));
      });
  }
  ```
  In `ShopController.php`:
  ```php
  $query = Product::active()->with(['category', 'seller'])->...
  ```
- **Analysis:**
  The `Product::scopeActive()` query correctly ensures that only products whose seller has an approved status and an active, non-expired subscription appear in public listings.
  **Risk Identified:** On the single product detail page (`ProductController::show`), if a customer visits a direct URL or bookmarked link of an expired seller's product, verify whether `ProductController::show` uses `Product::active()` or raw model binding.
- **Remediation:**
  In `ProductController::show`, ensure route model binding or controller query uses `Product::active()->where('slug', $slug)->firstOrFail()`.

---

#### Finding DAT-02: Failed Queue Job Monitoring & Dead Letter Logging
- **Severity:** P1 (High)
- **Category:** Operational Reliability
- **Affected Files:**
  - [app/Jobs/SendOrderEmails.php](file:///f:/laravelproject/alishe-nails/app/Jobs/SendOrderEmails.php#L19-L35)
  - [config/queue.php](file:///f:/laravelproject/alishe-nails/config/queue.php#L123-L128)
- **Evidence:**
  `SendOrderEmails` has `$tries = 3` and `$backoff = 30`. If the external SMTP provider (Mailgun, Postmark, etc.) is down or throttling, after 3 attempts the job fails and moves to the `failed_jobs` database table.
- **Risk Analysis:**
  If order emails fail silently into the database table without real-time administrator notification, customers who placed orders may assume their order was not received, causing duplicate checkout submissions or customer panic on Instagram.
- **Remediation:**
  Implement a `failed()` lifecycle hook on `SendOrderEmails`:
  ```php
  public function failed(\Throwable $exception): void
  {
      Log::critical("Order email failed after max attempts for order #{$this->order->order_number}: {$exception->getMessage()}");
  }
  ```

---

#### Finding DAT-03: Transaction Rollback Stock Restoration on Order Cancellation
- **Severity:** P1 (High)
- **Category:** Inventory & Financial Data Integrity
- **Affected Files:**
  - [app/Http/Controllers/Admin/AdminOrderController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/Admin/AdminOrderController.php)
  - [database/migrations/2026_09_06_000000_add_stock_restored_at_to_orders_table.php](file:///f:/laravelproject/alishe-nails/database/migrations/2026_09_06_000000_add_stock_restored_at_to_orders_table.php)
- **Evidence:**
  When orders are placed, stock is decremented immediately (`$product->decrement('stock', $row['qty'])`).
  The migration `add_stock_restored_at_to_orders_table.php` introduces a `stock_restored_at` timestamp.
- **Analysis:**
  When an admin changes an order's status to `cancelled` via `AdminOrderController::updateStatus`, stock must be safely restored to the products, and idempotent protection must ensure stock is not double-incremented if the status is saved repeatedly.
- **Remediation:**
  Ensure the cancellation block checks `if (is_null($order->stock_restored_at))` before incrementing product stock and timestamps `stock_restored_at = now()`.

---

#### Finding DAT-04: Commission Rate Precision and Snapshot Locking
- **Severity:** P1 (High)
- **Category:** Financial Business Logic
- **Affected Files:**
  - [app/Http/Controllers/CheckoutController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/CheckoutController.php#L235-L247)
- **Evidence:**
  ```php
  if ($product->seller_id) {
      $commissionRate = $product->seller && $product->seller->hasActiveSubscription()
          ? 0.00
          : self::COMMISSION_RATE;

      $commissionAmount = round($lineTotal * ($commissionRate / 100), 2);

      $orderItem->update([
          'commission_rate' => $commissionRate,
          'commission_amount' => $commissionAmount,
          'seller_earning' => round($lineTotal - $commissionAmount, 2),
          'payout_status' => 'pending',
      ]);
  }
  ```
- **Analysis:**
  Commission amounts and seller earnings are calculated and permanently frozen onto each `OrderItem` record at the instant the order is created. This prevents historical earnings from mutating if platform commission rates change later.
- **Remediation:**
  Ensure that when admins view payouts in `AdminMarketplaceController`, calculations aggregate the frozen `order_items.seller_earning` rather than dynamically recalculating from present seller subscription state.

---

### [P2 - Medium Priority / Performance & Scalability]

#### Finding PRF-01: High-Concurrency Database Session Contention (Redis Transition)
- **Severity:** P2 (Medium)
- **Category:** Performance & Database Scalability
- **Affected Files:**
  - [config/session.php](file:///f:/laravelproject/alishe-nails/config/session.php#L4)
  - [.env.example](file:///f:/laravelproject/alishe-nails/.env.example#L26)
- **Evidence:**
  ```dotenv
  SESSION_DRIVER=database
  CACHE_STORE=redis
  ```
- **Risk Analysis:**
  Under normal load (100–300 users), MySQL easily handles session reads and writes. However, during flash traffic from viral Instagram posts or celebrity promotions (>1,000 concurrent sessions), every page view executes an `UPDATE sessions SET payload = ... WHERE id = ...`. In InnoDB, high row-lock contention on the `sessions` table degrades query throughput across the entire application.
- **Remediation:**
  In production, set `SESSION_DRIVER=redis` and ensure Redis in-memory storage is configured with eviction policy `volatile-lru`.

---

#### Finding PRF-02: Facet Filter Sidebar Aggregation Query Optimization
- **Severity:** P2 (Medium)
- **Category:** Database Query Efficiency
- **Affected Files:**
  - [app/Http/Controllers/ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L55-L63)
- **Evidence:**
  ```php
  [$categories, $shapeCounts, $lengthCounts, $finishCounts] = Cache::remember('shop_sidebar_facets', 300, function () {
      return [
          Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->get(),
          Product::active()->selectRaw('shape, count(*) as total')->groupBy('shape')->pluck('total', 'shape'),
          Product::active()->selectRaw('length, count(*) as total')->groupBy('length')->pluck('total', 'length'),
          Product::active()->selectRaw('finish, count(*) as total')->groupBy('finish')->pluck('total', 'finish'),
      ];
  });
  ```
- **Analysis:**
  The 5-minute cache (`Cache::remember('shop_sidebar_facets', 300)`) prevents four heavy `GROUP BY` aggregate queries from running on every request to `/shop`. In `Product::booted()`, `Cache::forget('shop_sidebar_facets')` clears the cache whenever a product is saved or deleted.
- **Remediation:**
  Ensure the cache tag or key is also invalidated when a seller's subscription status changes (as that activates/deactivates products via `scopeActive`).

---

#### Finding PRF-03: Full-Text Search Scaling on Expanding Product Catalog
- **Severity:** P2 (Medium)
- **Category:** Database Scalability & Search Latency
- **Affected Files:**
  - [app/Http/Controllers/ShopController.php](file:///f:/laravelproject/alishe-nails/app/Http/Controllers/ShopController.php#L19-L21)
- **Evidence:**
  ```php
  if ($search = $request->string('q')->trim()->toString()) {
      $query->where('name', 'like', "%{$search}%");
  }
  ```
- **Analysis:**
  Leading wildcard searches (`%query%`) cannot utilize B-Tree indexes and require a full table scan. For the current catalog of boutique press-on nails (<500 products), the table scan completes in sub-millisecond time. However, as marketplace sellers scale the catalog into thousands of items, `%search%` queries will exhibit increased CPU usage and query latency.
- **Remediation:**
  Add a MySQL `FULLTEXT(name, short_description)` index in a future migration and utilize `whereFullText(['name', 'short_description'], $search)` or Laravel Scout.

---

#### Finding PRF-04: Static Asset Cache Control Headers & Compression
- **Severity:** P2 (Medium)
- **Category:** Frontend Performance / Core Web Vitals
- **Affected Files:**
  - [public/build/manifest.json](file:///f:/laravelproject/alishe-nails/public/build/manifest.json)
  - [app/Http/Middleware/SecurityHeaders.php](file:///f:/laravelproject/alishe-nails/app/Http/Middleware/SecurityHeaders.php)
- **Evidence:**
  Vite generates fingerprinted assets (`app-[hash].js`, `app-[hash].css`).
- **Remediation:**
  Ensure the production Nginx/Apache configuration sets immutable cache headers for hashed assets:
  ```nginx
  location ~* \.(?:css|js|woff2?|svg|webp|png|jpg)$ {
      expires 1y;
      add_header Cache-Control "public, max-age=31536000, immutable";
  }
  ```

---

### [P3 - Low Priority / UI/UX & Polish]

#### Finding UI-01: Accessible Labels on Icon-Only Buttons & Action Controls
- **Severity:** P3 (Low)
- **Category:** Accessibility (WCAG 2.1 AA)
- **Affected Files:**
  - [resources/views/components/navbar.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/components/navbar.blade.php)
  - [resources/views/cart/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/cart/index.blade.php)
  - [resources/views/account/wishlist.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/account/wishlist.blade.php)
- **Evidence:**
  Several interactive icons (mobile menu toggle, wishlist heart toggle, quantity increment/decrement, modal close cross) lack explicit `aria-label` or inner `<span class="sr-only">` tags.
- **Remediation:**
  Add `aria-label="Toggle mobile menu"` and `aria-label="Remove item from cart"` attributes to all icon-only button elements.

---

#### Finding UI-02: Viewport Padding & Table Overflow on Ultra-Narrow Displays (<375px)
- **Severity:** P3 (Low)
- **Category:** Mobile Responsiveness
- **Affected Files:**
  - [resources/views/admin/orders/index.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/admin/orders/index.blade.php)
  - [resources/views/seller/dashboard.blade.php](file:///f:/laravelproject/alishe-nails/resources/views/seller/dashboard.blade.php)
- **Evidence:**
  Admin and seller tables contain 7–8 columns (Order #, Customer, Total, Payment Method, Status, Date, Actions). On small screens (320px–360px), horizontal scrolling is enabled with `overflow-x-auto`.
- **Remediation:**
  Add a subtle gradient indicator or scroll hint shadow on the right edge of tables on mobile screens to clearly indicate scrollability.

---

#### Finding UI-03: Strict Content Security Policy Script Noncing
- **Severity:** P3 (Low)
- **Category:** Defense-in-Depth / CSP
- **Affected Files:**
  - [app/Http/Middleware/SecurityHeaders.php](file:///f:/laravelproject/alishe-nails/app/Http/Middleware/SecurityHeaders.php#L29)
- **Evidence:**
  `script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com`
- **Analysis:**
  `'unsafe-inline'` is currently required because Blade templates include embedded scripts (such as checkout shipping calculations and image carousels). While all user input is sanitized, removing `'unsafe-inline'` and introducing cryptographic CSP nonces (`nonce="..."`) provides ultimate enterprise protection against DOM-based XSS.
- **Remediation:**
  Introduce a Blade directive `@nonce` generated per request via `Vite::useCspNonce()` or a custom middleware.

---

### [P4 - Future Enhancements & Operations]

#### Finding OPS-01: Production Scheduler Cron Setup & Supervisor Daemonization
- **Severity:** P4 (Operational)
- **Category:** System Administration
- **Remediation:** Configure systemd / supervisord daemon for queue workers and install system cron for Laravel schedule.

#### Finding OPS-02: Automated WhatsApp Webhook & Status Update Integration
- **Severity:** P4 (Enhancement)
- **Category:** Customer Experience
- **Remediation:** In addition to manual customer coordination via `wa.me/923113925166`, integrate WhatsApp Business Cloud API to automatically send order receipts and tracking links when payment is verified.
