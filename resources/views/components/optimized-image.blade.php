{{--
    Renders an image with an automatic WebP source and CLS-safe dimensions.

    Usage:
        <x-optimized-image src="images/products/foo.jpg" alt="Foo" />

    The component keeps the original file as the <img> fallback and points
    <source> at the WebP sibling when one exists on disk. When no WebP
    sibling is available it degrades to a plain <img>, so the markup is
    always valid.
--}}
@props([
    'src',
    'alt' => '',
    'class' => null,
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'sizes' => null,
    'fallback' => false,
])

@php
    $webp = \App\Support\ImageUrl::webpVariant($src);
    $fallbackUrl = \App\Support\ImageUrl::fallback($src);
    [$intrinsicWidth, $intrinsicHeight] = \App\Support\ImageUrl::dimensions($src);
    $renderWidth = $width ?: $intrinsicWidth;
    $renderHeight = $height ?: $intrinsicHeight;
@endphp

<picture {{ $attributes }}>
    @if ($webp)
        <source type="image/webp" srcset="{{ $webp }}"@if ($sizes) sizes="{{ $sizes }}"@endif>
    @endif
    <img src="{{ $fallbackUrl }}" alt="{{ $alt }}"
        @if ($renderWidth) width="{{ $renderWidth }}" @endif
        @if ($renderHeight) height="{{ $renderHeight }}" @endif
        @if ($class) class="{{ $class }}" @endif
        @if ($sizes) sizes="{{ $sizes }}" @endif
        @if ($fallback) data-image-fallback @endif
        loading="{{ $loading }}" decoding="{{ $decoding }}"
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif>
</picture>
