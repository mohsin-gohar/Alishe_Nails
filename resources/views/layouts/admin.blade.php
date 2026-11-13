<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Alishe Nails administration panel — catalogue, orders, merchandising and customer care.">
    <title>@yield('title', 'Admin — Alishe Nails')</title>
    <link rel="stylesheet" href="{{ asset('css/googlefonts.css') }}?v={{ filemtime(public_path('css/googlefonts.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}?v={{ filemtime(public_path('css/fontawesome.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin-body">

    @if (auth()->guard('admin')->check())
        <div class="admin-shell">
            {{-- ---------- Sidebar ---------- --}}
            <aside class="admin-sidebar">
                <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__brand" style="display:flex;align-items:center;gap:10px;">
                    <x-optimized-image src="images/logo.jpeg" alt="Alishe Nails Logo" class="admin-brand__logo" loading="eager" />
                    <span>Alishe Nails</span>
                </a>

                <div class="admin-sidebar__label">Main Menu</div>
                <nav>
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i> Overview
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> Orders
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-box"></i> Products
                    </a>
                    <a href="{{ route('admin.homepage.index') }}" class="admin-nav-link {{ request()->routeIs('admin.homepage.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-house-chimney"></i> Homepage Studio
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-tags"></i> Categories
                    </a>
                    <a href="{{ route('admin.shipping.index') }}" class="admin-nav-link {{ request()->routeIs('admin.shipping.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-truck"></i> Shipping Rates
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="admin-nav-link {{ request()->routeIs('admin.customers.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-users"></i> Customers
                    </a>
                    <a href="{{ route('admin.reviews.index') }}" class="admin-nav-link {{ request()->routeIs('admin.reviews.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-star"></i> Reviews
                    </a>
                    <a href="{{ route('admin.analytics.index') }}" class="admin-nav-link {{ request()->routeIs('admin.analytics.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i> Analytics
                    </a>
                </nav>

                <div class="admin-sidebar__bottom">
                    <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-gear"></i> Settings
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="admin-nav-link" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit;">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </div>
            </aside>

            {{-- ---------- Main content ---------- --}}
            <div class="admin-main">
                <header class="admin-topbar">
                    <div>
                        <button class="admin-topbar__menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                    </div>
                    <div class="admin-topbar__actions">
                        <a href="{{ route('home') }}" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Store
                        </a>
                        <span class="navbar__avatar" role="img" aria-label="Admin" style="display:inline-flex;align-items:center;justify-content:center;background:var(--blush);color:var(--rose-dark);font-weight:700;font-size:0.75rem;">
                            <i class="fa-solid fa-crown" style="font-size:0.75rem;color:var(--gold);"></i>
                        </span>
                    </div>
                </header>

                <main class="admin-content">
                    <x-alert />
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        {{-- Not logged in (e.g. the login page itself) — no sidebar, just the page content --}}
        <x-alert />
        @yield('content')
    @endif

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
