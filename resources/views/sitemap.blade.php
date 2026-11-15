<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (!empty($url['lastmod']))<lastmod>{{ $url['lastmod'] }}</lastmod>@endif
        <changefreq>{{ $url['changefreq'] ?? 'weekly' }}</changefreq>
        <priority>{{ $url['priority'] ?? '0.5' }}</priority>
        @if (!empty($url['image']))
        <image:image>
            <image:loc>{{ $url['image']['loc'] }}</image:loc>
            <image:title>{{ $url['image']['title'] }}</image:title>
        </image:image>
        @endif
    </url>
@endforeach
</urlset>
