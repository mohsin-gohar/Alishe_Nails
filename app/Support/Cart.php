<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Lightweight session-based cart. No external package required.
 * Cart contents are stored in the session under CART_KEY as:
 *   [rowId => ['product_id', 'name', 'slug', 'image', 'price', 'shape', 'size', 'qty']]
 */
class Cart
{
    private const CART_KEY = 'alishe_cart';

    public static function content(): array
    {
        return Session::get(self::CART_KEY, []);
    }

    /**
     * Returns the row ID on success, or null if the product is inactive /
     * out of stock / the requested quantity (existing + new) would exceed
     * available stock. The controller checks for null and shows an error —
     * the cart never silently holds more than what's actually in stock.
     */
    public static function add(Product $product, int $qty = 1, ?string $shape = null, ?string $size = null): ?string
    {
        if (! $product->is_active || $product->stock <= 0) {
            return null;
        }

        $cart = self::content();
        $qty = max(1, $qty);

        // Merge quantity if the exact same product+shape+size already exists.
        foreach ($cart as $rowId => $row) {
            if (($row['product_id'] ?? null) === $product->id
                && ($row['shape'] ?? null) === $shape
                && ($row['size'] ?? null) === $size) {
                $newQty = $row['qty'] + $qty;

                if ($newQty > $product->stock) {
                    return null;
                }

                $cart[$rowId]['qty'] = $newQty;
                Session::put(self::CART_KEY, $cart);

                return $rowId;
            }
        }

        if ($qty > $product->stock) {
            return null;
        }

        $rowId = Str::random(12);
        $cart[$rowId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->cover_image_path,
            'price' => (float) $product->price,
            'shape' => $shape,
            'size' => $size,
            'qty' => $qty,
        ];

        Session::put(self::CART_KEY, $cart);

        return $rowId;
    }

    public static function update(string $rowId, int $qty): void
    {
        $cart = self::content();

        if (! isset($cart[$rowId])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$rowId]);
            Session::put(self::CART_KEY, $cart);

            return;
        }

        $product = Product::find($cart[$rowId]['product_id']);
        if (! $product || ! $product->is_active) {
            unset($cart[$rowId]);
            Session::put(self::CART_KEY, $cart);

            return;
        }

        $qty = min($qty, max(0, $product->stock));
        if ($qty <= 0) {
            unset($cart[$rowId]);
            Session::put(self::CART_KEY, $cart);

            return;
        }

        $cart[$rowId]['qty'] = $qty;
        Session::put(self::CART_KEY, $cart);
    }

    public static function remove(string $rowId): void
    {
        $cart = self::content();
        unset($cart[$rowId]);
        self::restore($cart);
    }

    public static function restore(array $cart): void
    {
        Session::put(self::CART_KEY, $cart);
    }

    public static function clear(): void
    {
        Session::forget(self::CART_KEY);
    }

    public static function count(): int
    {
        return (int) collect(self::content())->sum('qty');
    }

    /**
     * Rows the customer can no longer buy as stored: the product disappeared,
     * became inactive, sold out, or the requested quantity (summed across
     * cart rows for the same product) is higher than the live stock.
     *
     * Checkout uses this to stop early with a clear message instead of
     * charging for (or failing on) an item the customer cannot receive.
     *
     * @return array<string, array{product_id:int, name:string, qty:int, stock:int}>
     */
    public static function unavailableRows(): array
    {
        $rows = self::content();

        if ($rows === []) {
            return [];
        }

        $products = Product::whereIn('id', collect($rows)->pluck('product_id')->unique())
            ->get(['id', 'name', 'stock', 'is_active'])
            ->keyBy('id');

        $requestedPerProduct = [];
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            $requestedPerProduct[$productId] = ($requestedPerProduct[$productId] ?? 0) + (int) $row['qty'];
        }

        $unavailable = [];

        foreach ($rows as $rowId => $row) {
            $product = $products->get($row['product_id']);
            $stock = $product ? (int) $product->stock : 0;
            $requested = $requestedPerProduct[(int) $row['product_id']] ?? 0;

            if (! $product || ! $product->is_active || $stock <= 0 || $requested > $stock) {
                $unavailable[$rowId] = [
                    'product_id' => (int) $row['product_id'],
                    'name' => $product->name ?? (string) $row['name'],
                    'qty' => (int) $row['qty'],
                    'stock' => $stock,
                ];
            }
        }

        return $unavailable;
    }

    /**
     * Calculate the cart subtotal using the CURRENT database price of each
     * product — never the price stored in the session when the item was added.
     * This prevents a customer manipulating the session (or a stale price)
     * from being charged an incorrect amount. Products that no longer exist,
     * became inactive, or are out of stock are skipped, matching what the
     * cart and checkout pages can actually sell.
     *
     * Stock is consumed in cart order, so two rows for the same product cannot
     * together be priced for more units than the live stock.
     */
    public static function subtotal(): float
    {
        $rows = self::content();

        if ($rows === []) {
            return 0.0;
        }

        $products = Product::whereIn('id', collect($rows)->pluck('product_id')->unique())
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->get(['id', 'price', 'stock'])
            ->keyBy('id');

        $remaining = [];
        $subtotal = 0.0;

        foreach ($rows as $row) {
            $product = $products->get($row['product_id']);

            // Skip rows whose product disappeared, became inactive or sold out.
            if ($product === null) {
                continue;
            }

            $productId = (int) $product->id;
            $remaining[$productId] ??= (int) $product->stock;

            // Never price more units than the live stock can satisfy.
            $qty = min((int) $row['qty'], max(0, $remaining[$productId]));

            if ($qty <= 0) {
                continue;
            }

            $remaining[$productId] -= $qty;
            $subtotal += (float) $product->price * $qty;
        }

        return round((float) $subtotal, 2);
    }
}
