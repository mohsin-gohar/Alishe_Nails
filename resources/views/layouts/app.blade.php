<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('title', 'Alishe Nails — Timeless Nails, Made for You')</title>
    <meta name="description" content="@yield('meta_description', 'Luxury handcrafted press-on nails from Alishe Nails Karachi. 7-layer salon gel, zero damage, reusable up to 5 times.')">
    <meta name="keywords" content="@yield('meta_keywords', 'press-on nails, handcrafted nails, salon gel, Alishe Nails, Karachi nails')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="theme-color" content="#2B231F">
    <meta name="msapplication-TileColor" content="#2B231F">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Alishe Nails">
    <meta property="og:locale" content="en_PK">
    <meta property="og:title" content="@yield('og_title', 'Alishe Nails — Timeless Nails, Made for You')">
    <meta property="og:description" content="@yield('og_description', 'Luxury handcrafted 7-layer salon gel press-on nails. Zero damage, reusable up to 5 times, delivered across Karachi in 48-96 hours.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og/og-image.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="@yield('og_title', 'Alishe Nails — Timeless Nails, Made for You')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Alishe Nails — Timeless Nails, Made for You')">
    <meta name="twitter:description" content="@yield('og_description', 'Luxury handcrafted 7-layer salon gel press-on nails. Zero damage, reusable up to 5 times, delivered across Karachi in 48-96 hours.')">
    <meta name="twitter:creator" content="@yield('twitter_creator', '@alishe_nails')">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Fonts and icons are self-hosted: no third-party DNS lookup, no extra
         TLS handshake, no third-party request to track a visitor, and the
         assets can be cached for a year by the browser. --}}
    <link rel="preload" href="{{ asset('css/googlefonts.css') }}?v={{ filemtime(public_path('css/googlefonts.css')) }}" as="style">
    <link rel="preload" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" as="style">

    <link rel="stylesheet" href="{{ asset('css/googlefonts.css') }}?v={{ filemtime(public_path('css/googlefonts.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}?v={{ filemtime(public_path('css/fontawesome.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
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
    $headerImages = $picked->map(function ($file) {
        $relativePath = ltrim(str_replace(public_path(), '', (string) $file), DIRECTORY_SEPARATOR);
        $relativePath = str_replace('\\', '/', $relativePath);
        return asset($relativePath);
    })
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
                        'telephone' => '+'.$globalWaDigits,
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

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')

    {{-- Google Analytics 4 --}}
    @if (!empty(config('services.ga4_measurement_id')))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga4_measurement_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.ga4_measurement_id') }}');
    </script>
    @endif

    {{-- Facebook Meta Pixel --}}
    @if (!empty(config('services.meta_pixel_id')))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){callMethod.apply(arguments.callQueue=n.callQueue=[]);
        callMethod.push=callMethod.callMethod.bind(n);n._jb2=n._jb2||v;s=b.createElement(e);s.async=!0;
        s.src=v;t=b.getElementsByTagName(e)[0];t.parentNode.insertBefore(s,t)}(window, document,'script',
        'https://connect.facebook.net/en_US/sdk.js');
        fbq('init', '{{ config('services.meta_pixel_id') }}');
        fbq('track', 'PageView');
    </script>
    @endif

    {{-- Microsoft Clarity --}}
    @if (!empty(config('services.clarity_project_id')))
    <script defer src="https://clarity.ms/universal.js?v=1"></script>
    <script>
        window.clarity=window.clarity||function(){arguments._push=arguments;window.clarity._L=+new Date;
        var recr=document.getElementsByTagName('script')[0];recr.parentNode.insertBefore(clarity, recr);
        clarity('set', 'siteId', '{{ config('services.clarity_project_id') }}');
        clarity('view');
    </script>
    @endif
</body>
</html>
