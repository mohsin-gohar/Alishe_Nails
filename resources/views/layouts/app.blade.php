<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('title', 'Alishe Nails — Timeless Nails, Made for You')</title>
    <meta name="description" content="@yield('meta_description', 'Luxury handmade press-on nails for every occasion. Shop the Alishe Nails collection.')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="theme-color" content="#2B1D1D">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Alishe Nails">
    <meta property="og:locale" content="en_PK">
    <meta property="og:title" content="@yield('og_title', 'Alishe Nails — Timeless Nails, Made for You')">
    <meta property="og:description" content="@yield('og_description', 'Luxury handmade press-on nails for every occasion.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/logo.jpeg'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
    @stack('head')
</head>
@php
    // Rotating header imagery: a fresh, shuffled set of product/brand images is
    // picked on every request so the navbar, hero and page headers feel alive
    // and change on every page navigation / refresh (enterprise-level polish).
    $imageDirs = [
        public_path('images/products'),
        public_path('images/brand'),
        public_path('images/collections'),
        public_path('images/instagram'),
    ];
    $allImages = collect();
    foreach ($imageDirs as $dir) {
        if (is_dir($dir)) {
            $allImages = $allImages->merge(collect(glob($dir.'/*') ?: [])
                ->filter(fn ($file) => is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])));
        }
    }
    if ($allImages->isEmpty()) {
        $allImages = collect([public_path('images/logo.jpeg')]);
    }
    // Ensure at least 3 distinct images, padding with the logo when needed.
    $shuffled = $allImages->shuffle()->values();
    $picked = $shuffled->take(3);
    while ($picked->count() < 3) {
        $picked->push(public_path('images/logo.jpeg'));
    }
    $headerImages = $picked->map(fn ($file) => str_starts_with((string) $file, public_path('images'))
        ? asset(str_replace('\\', '/', str_replace(public_path().'\\', '', $file)))
        : asset('images/logo.jpeg'))
        ->values()
        ->all();
@endphp
<body style="--header-image-navbar: url('{{ $headerImages[0] }}'); --header-image-hero: url('{{ $headerImages[1] }}'); --header-image-page: url('{{ $headerImages[2] }}');">
    <a class="skip-link" href="#main-content">Skip to content</a>

    <x-alert />

    <x-navbar />

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    <x-footer />



    @php
        $globalWaRaw = config('services.whatsapp.number', '923113925166');
        $globalWaDigits = preg_replace('/[^0-9]/', '', $globalWaRaw);
        if (str_starts_with($globalWaDigits, '0')) {
            $globalWaDigits = '92'.substr($globalWaDigits, 1);
        }
    @endphp

    <a href="https://wa.me/{{ $globalWaDigits }}?text={{ urlencode('Assalam-o-Alaikum! Mujhe Alishe Nails se rabta karna hai.') }}"
       class="floating-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="Chat with us on WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <a href="https://instagram.com/{{ config('services.instagram.handle', 'alishe_nails') }}"
       class="floating-instagram" target="_blank" rel="noopener" aria-label="Follow Alishe Nails on Instagram" title="Follow on Instagram">
        <i class="fa-brands fa-instagram"></i>
    </a>

    @php
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'Alishe Nails',
                    'url' => url('/'),
                    'logo' => asset('images/logo.jpeg'),
                    'image' => asset('images/logo.jpeg'),
                    'description' => 'Luxury handmade press-on nails for every occasion.',
                    'sameAs' => ['https://instagram.com/'.config('services.instagram.handle', 'alishe_nails')],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Karachi',
                        'addressCountry' => 'PK',
                    ],
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer support',
                        'telephone' => config('services.whatsapp.number', '+92 311 3925166'),
                        'areaServed' => 'PK',
                        'availableLanguage' => ['en', 'ur'],
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'Alishe Nails',
                    'publisher' => ['@id' => url('/').'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('shop.index').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
