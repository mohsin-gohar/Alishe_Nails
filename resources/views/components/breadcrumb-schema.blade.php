{{--
    Emits a BreadcrumbList JSON-LD block.

    Usage:
        <x-breadcrumb-schema :items="[
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $product->name, 'url' => route('products.show', $product)],
        ]" />
--}}
@props(['items' => []])

@php
    $crumbs = collect($items)
        ->filter(fn ($item) => ! empty($item['name']))
        ->values()
        ->all();
@endphp

@if (count($crumbs) > 1)
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->map(fn ($item, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? url()->current(),
        ])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endif
