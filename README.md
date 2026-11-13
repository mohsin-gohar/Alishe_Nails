# Alishe Nails

Premium handmade / press-on nails e-commerce platform built with Laravel, PHP, MySQL, Tailwind CSS, and Alpine.js.

## Technology Stack
| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL 8 |
| Frontend | Tailwind CSS 4, Alpine.js 3, Blade, Vite |
| Auth | Laravel (web / admin / seller guards) |
| Payments | Advance payment via JazzCash, SadaPay, Easypaisa, or bank transfer; COD is disabled |

## Features

### Customer
- Homepage with hero, featured products, best sellers, Instagram feed
- Shop with search, category filters, price/sorting, pagination
- Product details with gallery, reviews, related products
- Cart with stock validation and price recalculation
- Multi-step checkout with advance payment via bank transfer, JazzCash, SadaPay, or Easypaisa
- Order tracking via secure access token (guest) or account (user)
- Customer account with dashboard, orders, wishlist, profile, addresses
- Product reviews with moderation queue
- Coupon/promo support with server-side validation
- Shipping rates with free-shipping thresholds
- Newsletter subscription with unsubscribe tokens

### Seller Marketplace
- Seller application flow with admin approval
- Seller authentication (separate guard)
- Seller dashboard with products, orders, earnings, subscriptions
- Subscription plans (Basic, Pro, Enterprise)
- Commission tracking on marketplace orders

### Admin Panel
- Admin guard with rate-limited login
- Dashboard with revenue, orders, customers, sellers
- Product CRUD, category management, review moderation
- Order management with status updates and stock restoration
- Customer management (no password exposure)
- Seller management with approval/rejection workflow
- Subscription management
- Shipping rate management
- Campaign pitch management
- Instagram sync (manual + scheduled)

### Infrastructure
- Structured Eloquent models with proper relationships
- Form Requests with server-side validation
- Policies for authorization
- Jobs and queues for background email sending
- Scheduler for subscription expiry and Instagram sync
- Error pages (404, 403, 419, 429, 500)
- SEO: sitemap, robots.txt, Open Graph metadata
- CSRF, XSS, SQL injection, authorization protection
- Validated media uploads with generated filenames
