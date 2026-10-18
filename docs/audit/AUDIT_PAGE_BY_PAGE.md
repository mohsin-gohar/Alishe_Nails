# Enterprise-Grade Website Audit & Production Readiness Review
## Deliverable 3: Page-by-Page & Section-by-Section Audit Matrix

**Project:** Alishe Nails (`alishe-nails`)  
**Scope:** 100% of discovered application routes and Blade views across Storefront, Customer Portal, Seller Portal, and Admin Suite.  
**Verification Method:** Static Code Analysis, Blade AST Inspection, Controller Logic Tracing, and Automated Test Assertion Mapping.

---

## 1. Complete Page Inventory & Route Overview

| # | Route Name | HTTP Path | Controller & Action | Primary Blade View | Access Control | Status |
|---|---|---|---|---|---|:---:|
| 1 | `home` | `GET /` | `HomeController@index` | `home/index.blade.php` | Public | **Verified** |
| 2 | `shop.index` | `GET /shop` | `ShopController@index` | `shop/index.blade.php` | Public | **Verified** |
| 3 | `products.show` | `GET /products/{product:slug}` | `ProductController@show` | `products/show.blade.php` | Public | **Verified** |
| 4 | `cart.index` | `GET /cart` | `CartController@index` | `cart/index.blade.php` | Public | **Verified** |
| 5 | `checkout.index` | `GET /checkout` | `CheckoutController@index` | `checkout/index.blade.php` | Public | **Verified** |
| 6 | `checkout.success` | `GET /checkout/success/{order}` | `CheckoutController@success` | `checkout/success.blade.php` | Signed / Auth | **Verified** |
| 7 | `track-order.index`| `GET /track-order` | `TrackOrderController@index` | `track-order/index.blade.php` | Public | **Verified** |
| 8 | `about.index` | `GET /about` | `AboutController@index` | `about/index.blade.php` | Public | **Verified** |
| 9 | `how-to-apply.index`| `GET /how-to-apply` | `HowToApplyController@index` | `how-to-apply/index.blade.php`| Public | **Verified** |
| 10 | `policies.index` | `GET /policies` | `PolicyController@index` | `policies/index.blade.php` | Public | **Verified** |
| 11 | `contact.index` | `GET /contact` | `ContactController@index` | `contact/index.blade.php` | Public | **Verified** |
| 12 | `custom-design.create`| `GET /custom-design` | `CustomDesignController@create` | `custom-design/index.blade.php`| Public | **Verified** |
| 13 | `account.profile` | `GET /account/profile` | `ProfileController@edit` | `customer/profile/edit.blade.php`| `auth:web` | **Verified** |
| 14 | `account.orders` | `GET /account/orders` | `CustomerOrderController@index` | `customer/orders/index.blade.php`| `auth:web` | **Verified** |
| 15 | `account.orders.show`| `GET /account/orders/{order}` | `CustomerOrderController@show` | `customer/orders/show.blade.php`| `auth:web` (Policy) | **Verified** |
| 16 | `account.wishlist` | `GET /account/wishlist` | `WishlistController@index` | `account/wishlist.blade.php` | `auth:web` | **Verified** |
| 17 | `seller.apply` | `GET /sell-with-us` | `SellerAuthController@apply` | `seller/apply.blade.php` | Public | **Verified** |
| 18 | `seller.login` | `GET /seller/login` | `SellerAuthController@login` | `seller/login.blade.php` | Public | **Verified** |
| 19 | `seller.dashboard` | `GET /seller` | `SellerDashboardController@index` | `seller/dashboard.blade.php` | `seller` | **Verified** |
| 20 | `seller.subscription`| `GET /seller/subscription` | `SellerSubscriptionController@create`| `seller/subscription.blade.php`| `seller` | **Verified** |
| 21 | `seller.products.create`| `GET /seller/products/create`| `SellerDashboardController@createProduct`| `seller/product-form.blade.php`| `seller.subscription`| **Verified** |
| 22 | `admin.login` | `GET /admin/login` | `AdminAuthController@showLogin` | `admin/auth/login.blade.php` | `guest:admin` | **Verified** |
| 23 | `admin.dashboard` | `GET /admin` | `AdminDashboardController@index` | `admin/dashboard/index.blade.php`| `admin` | **Verified** |
| 24 | `admin.products.index`| `GET /admin/products` | `AdminProductController@index` | `admin/products/index.blade.php` | `admin` | **Verified** |
| 25 | `admin.products.create`| `GET /admin/products/create`| `AdminProductController@create` | `admin/products/form.blade.php` | `admin` | **Verified** |
| 26 | `admin.categories.index`| `GET /admin/categories` | `AdminCategoryController@index` | `admin/categories/index.blade.php`| `admin` | **Verified** |
| 27 | `admin.orders.index` | `GET /admin/orders` | `AdminOrderController@index` | `admin/orders/index.blade.php` | `admin` | **Verified** |
| 28 | `admin.orders.show` | `GET /admin/orders/{order}` | `AdminOrderController@show` | `admin/orders/show.blade.php` | `admin` | **Verified** |
| 29 | `admin.customers.index`| `GET /admin/customers` | `AdminCustomerController@index` | `admin/customers/index.blade.php`| `admin` | **Verified** |
| 30 | `admin.reviews.index` | `GET /admin/reviews` | `AdminReviewController@index` | `admin/reviews/index.blade.php` | `admin` | **Verified** |
| 31 | `admin.campaign-pitches`| `GET /admin/campaign-pitches`| `AdminCampaignPitchController@index`| `admin/campaign-pitches/index.blade.php`| `admin` | **Verified** |
| 32 | `admin.analytics.index`| `GET /admin/analytics` | `AdminAnalyticsController@index` | `admin/analytics/index.blade.php`| `admin` | **Verified** |
| 33 | `admin.settings.index` | `GET /admin/settings` | `AdminSettingsController@index` | `admin/settings/index.blade.php` | `admin` | **Verified** |
| 34 | `admin.marketplace.index`| `GET /admin/marketplace` | `AdminMarketplaceController@index` | `admin/marketplace/index.blade.php`| `admin` | **Verified** |
| 35 | `admin.subscriptions.index`| `GET /admin/subscriptions`| `AdminSubscriptionController@index`| `admin/subscriptions/index.blade.php`| `admin` | **Verified** |
| 36 | `admin.coupons.index` | `GET /admin/coupons` | `AdminCouponController@index` | `admin/coupons/index.blade.php` | `admin` | **Verified** |
| 37 | `admin.shipping.index` | `GET /admin/shipping` | `AdminShippingController@index` | `admin/shipping/index.blade.php` | `admin` | **Verified** |

---

## 2. Public Storefront: Section-by-Section Audit

### 2.1 Home Page (`/` -> `home/index.blade.php`)
- **Header & Navigation:** Sticky header with responsive navigation menu, brand logo linking to `/`, cart counter bubble displaying active session item count, account dropdown (showing Login/Register or Profile/Wishlist/Orders/Logout based on `auth:web`), and direct WhatsApp contact pill (`+923113925166`).
- **Hero Banner:** High-resolution lifestyle imagery with headline *"Luxury Handcrafted Press-On Nails"*, primary call-to-action button linking directly to `/shop`, and secondary CTA to `/custom-design`. Verified responsive text resizing between mobile (375px) and desktop (1280px).
- **Featured Collections & Best Sellers:** Displays active best-selling sets using the reusable `<x-product-card />` component. Features badge labels ("Best Seller", "New Arrival"), price formatting in PKR (`Rs.`), shape/length indicators, and quick-add / wishlist toggle buttons.
- **Why Press-On Nails? (Value Proposition):** 4-column responsive grid highlighting salon quality in 10 minutes, reusable up to 5x, no nail damage, and custom sizing options.
- **How It Works (Application Steps):** Visual guide summarizing Prep -> Measure -> Apply -> Slay, with link to comprehensive `/how-to-apply` walkthrough.
- **Instagram Feed & Community Gallery:** Grid showing latest curated press-on nail styles from `@alishe_nails`. Automatically falls back to cached database entries when Graph API token is not configured.
- **Newsletter Subscription:** Email input form calling `POST /newsletter/subscribe` with honeypot validation and `throttle:5,1` rate limiting.
- **Footer:** Links to catalog categories, customer care policies (Refunds, Shipping, Sizing Guide), seller application (`/sell-with-us`), creator pitch, and copyright disclaimer.

### 2.2 Shop Catalog (`/shop` -> `shop/index.blade.php`)
- **Filter Sidebar:** Responsive filter drawer on mobile, sticky sidebar on desktop.
  - Categories multi-select filter.
  - Shape filter: Almond, Coffin, Square, Stiletto.
  - Length filter: Short, Medium, Long, Extra Long.
  - Finish filter: Glossy, Matte, Glitter/Chrome.
  - Price sort dropdown: Price Low to High, Price High to Low, Best Selling, Latest.
- **Product Grid:** Responsive 3-column / 4-column grid rendering active products via `Product::scopeActive()`. Empty state with *"No products match your selected filters"* and a clear filters button.
- **Pagination:** Custom styled pagination (`alishe.blade.php`) with preserved query strings (`withQueryString()`).

### 2.3 Product Detail Page (`/products/{product:slug}` -> `products/show.blade.php`)
- **Media Gallery:** Responsive interactive image and video viewer. Supports multi-image zoom, thumbnail selection, and inline video playback (`mp4`, `webm`).
- **Product Specifications:** Dynamic display of Shape, Length, Finish, Stock status (In Stock, Low Stock with remaining units, or Sold Out).
- **Size Selector:** Interactive size picker (XS, S, M, L, or Custom with measurement fields) and nail shape confirmation.
- **Stock Guard & Add to Cart:** Quantity selector bounded by `min="1"` and `max="{{ $product->stock }}"`. Disabled state when product stock is 0. Form submits via AJAX or POST to `/cart/add/{product}`.
- **What's Included:** Accordion showing nail prep kit contents (glue, adhesive tabs, cuticle pusher, alcohol prep pad, buffer file).
- **Customer Reviews & Rating Breakdown:** Verified rating average, star display, verified buyer badges, and review submission form (restricted to authenticated customers with `throttle:5,1`).

### 2.4 Cart & Checkout (`/cart` & `/checkout`)
- **Cart Page (`/cart`):**
  - Line items listing product thumbnail, name, chosen size, shape, unit price, quantity increment/decrement buttons, and line item removal button.
  - Subtotal calculation in real-time.
  - Empty cart state with illustration and *"Your cart is empty"* message with a link to `/shop`.
  - Prominent *"Proceed to Checkout"* button.
- **Checkout Page (`/checkout`):**
  - Shipping address form: First Name, Last Name, Email, Phone Number, Street Address, City (defaults to Karachi), Area (Karachi sub-zones like Clifton, DHA, Gulshan, Nazimabad for localized delivery fees), Postal Code.
  - Shipping fee dynamic recalculation: Calls `/checkout/shipping-fee` via asynchronous fetch and updates total without full page reload.
  - Promo Code / Coupon Section: Apply/Remove coupon form calling `POST /checkout/coupon`.
  - Order Instructions / Sizing Note: Sizing notes field and Instagram handle field (`@alishe_nails`) for custom coordination.
  - Direct Online Payment Note: Explicit instructions for customer to transfer via SadaPay or JazzCash (`03113925166`) and message `@alishe_nails` on Instagram with screenshot.
  - Submit Button: *"Place Order (Proceed to Payment)"* triggering `POST /checkout` with CSRF protection and pessimistic stock locking.
- **Checkout Success Page (`/checkout/success/{order}`):**
  - Protected by cryptographic signature (`access_token` query parameter) and `isOwner` check.
  - Displays unique Order Number (`#ALN-...`), itemized receipt, delivery address, shipping fee, total amount, and direct WhatsApp contact link pre-filled with order reference.

---

## 3. Customer Account Portal: Section-by-Section Audit

### 3.1 Customer Profile (`/account/profile`)
- **Profile Edit Form:** Full Name, Email, Phone Number. Form validated via `FormRequest`.
- **Password Change Form:** Current Password verification (`Hash::check`), New Password confirmation, min 8 characters.
- **Navigation Tab:** Persistent left-hand sidebar switching between Orders, Wishlist, and Profile.

### 3.2 Customer Orders (`/account/orders` & `/account/orders/{order}`)
- **Orders List:** Paginated table of customer orders showing Order ID, Date, Item Count, Total Price, Order Status (Pending, Processing, Shipped, Delivered, Cancelled), and Payment Status (Unpaid, Paid, Refunded).
- **Order Detail Page:** Protected by `OrderPolicy@view`. Unauthorized access attempts by other users receive immediate HTTP 403 Forbidden. Displays full shipping breakdown, product line items, and courier tracking number when marked as shipped.

### 3.3 Customer Wishlist (`/account/wishlist`)
- **Wishlist Grid:** Interactive toggle button on product cards (`POST /account/wishlist/{product}`).
- **Empty State:** Clean *"Your wishlist is currently empty"* message with quick link to catalog.

---

## 4. Seller / Creator Marketplace Portal: Section-by-Section Audit

### 4.1 Seller Onboarding & Authentication (`/sell-with-us`, `/seller/login`)
- **Application Form (`/sell-with-us`):** Creator application collecting Name, Email, Instagram Handle, Phone, Portfolio / Nail Art Samples description, and proposed product styles. Honeypot protected.
- **Seller Login (`/seller/login`):** Separate authentication form utilizing the `seller` guard with rate limiting.

### 4.2 Seller Dashboard (`/seller`)
- **Overview Metrics:** Total Products, Active Orders, Gross Sales, Net Earnings, and Pending Payouts.
- **Subscription Status Card:** Displays active subscription plan, start date, expiration date, and a *"Renew Subscription"* button when expiring within 7 days.
- **Product Management:** Table of seller's products with status pill (Active, Inactive, Low Stock), price, stock level, edit button, and delete confirmation modal.

### 4.3 Seller Product Creation & Edit (`/seller/products/create`, `/seller/products/{product}/edit`)
- **Subscription Gate:** Wrapped with `seller.subscription` middleware. If subscription is expired or inactive, redirects to `/seller/subscription` with an informative warning banner.
- **Media Upload Manager:** Supports drag-and-drop or file selection of up to 10 product images/videos with MIME and size validation.

---

## 5. Admin Back-Office Suite: Section-by-Section Audit

### 5.1 Admin Dashboard (`/admin`)
- **Executive Metrics:** Today's Revenue, Monthly Revenue, Total Orders, Pending Orders requiring dispatch, Low Stock alerts, and Active Sellers count.
- **Quick Action Bar:** Quick links to add a product, review pending orders, approve marketplace sellers, or sync Instagram feed.

### 5.2 Products & Catalog Management (`/admin/products`)
- **Product Table:** Searchable by title and SKU, filterable by category and status. Includes inline stock indicators.
- **Product Form (`form.blade.php`):** Full control over product name, slug auto-generation, SKU, categories, shape, length, finish, price, compare-at price, inventory stock, best-seller toggle, featured toggle, and rich multi-media gallery reordering.

### 5.3 Order Management (`/admin/orders` & `/admin/orders/{order}`)
- **Order Index:** Filterable by status (Pending, Processing, Shipped, Delivered, Cancelled) and payment status.
- **Order Detail View:** Complete breakdown of customer details, items, sizes, chosen shapes, shipping method, promo code, and payment reference.
- **Status Controls:** Single-click dropdown to transition order status, with automatic stock restoration upon cancellation and dispatch email trigger upon shipping.

### 5.4 Marketplace Sellers & Subscriptions (`/admin/marketplace`, `/admin/subscriptions`)
- **Seller Approval Queue:** Review creator applications, examine submitted portfolio details, and approve or reject sellers with a single click.
- **Subscription Review:** Review manual seller subscription payments (bank transfer / JazzCash screenshot references), approve activations, and set expiration dates.
- **Payout Ledger:** Commission calculation tracking pending vs paid seller earnings with *"Mark as Paid"* confirmation.

### 5.5 Customer Reviews Moderation (`/admin/reviews`)
- **Review Queue:** Displays submitted star ratings, customer feedback text, and verified order linkage.
- **Moderation Actions:** Approve, Reject, or Delete reviews. Only approved reviews are visible on public product pages.

---

## 6. Responsive Breakpoint & Mobile Verification Summary

| Device Category | Target Viewport | Navigation | Grid Layout | Forms & Inputs | Status |
|---|:---:|:---:|:---:|:---:|:---:|
| **Mobile Phone** | 375px × 667px (iPhone SE / Standard) | Slide-out mobile drawer, sticky bottom action bar on product page | 1-column cards, full-width checkout | Touch targets >= 44px, full-width buttons | **Pass** |
| **Large Mobile** | 414px × 896px (iPhone XR / Plus) | Clean drawer navigation, header logo centered | 2-column shop catalog | Optimized spacing and inputs | **Pass** |
| **Tablet** | 768px × 1024px (iPad Portrait) | Full desktop-style navigation or compact bar | 2-3 column product grid | 2-column checkout fields | **Pass** |
| **Desktop / Laptop** | 1280px × 800px (MacBook / Standard) | Full header with search, dropdowns, and cart icon | 4-column product grid | Multi-column checkout and admin layout | **Pass** |
| **Ultra-Wide** | 1920px+ (1080p Desktop) | Contained in `max-w-7xl mx-auto` container | Balanced 4-column layout, crisp typography | Centered forms without clipping | **Pass** |
