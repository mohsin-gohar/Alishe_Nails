<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::content();

        return view('cart.index', [
            'items' => $items,
            'subtotal' => Cart::subtotal(),
        ]);
    }

    public function add(StoreCartRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        if ($product->seller && ! $product->seller->hasActiveSubscription()) {
            return back()->with('error', 'This seller subscription has expired and the product is currently unavailable.');
        }

        $qty = $request->validated()['qty'] ?? 1;
        $shape = $request->input('shape');
        $size = $request->input('size');

        if ($product->stock <= 0) {
            return back()->with('error', $product->name.' is currently out of stock.');
        }

        if ($qty > $product->stock) {
            return back()->with('error', 'Only '.$product->stock.' of '.$product->name.' are available.');
        }

        $rowId = Cart::add($product, $qty, $shape, $size);

        if ($rowId === null) {
            return back()->with('error', 'The requested quantity exceeds available stock.');
        }

        if ($request->expectsJson() && ! $request->has('buy_now') && $request->input('redirect') !== 'checkout') {
            return response()->json([
                'cart_count' => Cart::count(),
                'message' => $product->name.' added to your cart.',
            ]);
        }

        if ($request->has('buy_now') || $request->input('redirect') === 'checkout') {
            return redirect()->route('checkout.index')->with('success', $product->name.' added to your checkout order.');
        }

        return back()->with('success', $product->name.' added to your cart.');
    }

    public function update(UpdateCartRequest $request, string $rowId): RedirectResponse
    {
        $validated = $request->validated();

        $cart = Cart::content();

        if (! isset($cart[$rowId])) {
            return back()->with('error', 'Cart item not found.');
        }

        $product = Product::find($cart[$rowId]['product_id']);

        if (! $product || ! $product->is_active) {
            unset($cart[$rowId]);
            Cart::restore($cart);

            return back()->with('error', 'This product is no longer available.');
        }

        $qty = $validated['qty'] ?? $cart[$rowId]['qty'];

        if ($qty <= 0) {
            unset($cart[$rowId]);
            Cart::restore($cart);

            return back()->with('success', 'Item removed from cart.');
        }

        // Cap the quantity at the currently available stock. We never allow
        // more than what is actually in stock, but we also never reject the
        // update outright — the customer simply gets the maximum available.
        $qty = min($qty, max(0, $product->stock));

        if ($qty <= 0) {
            unset($cart[$rowId]);
            Cart::restore($cart);

            return back()->with('error', 'This item is now out of stock and was removed from your cart.');
        }

        $cart[$rowId]['qty'] = $qty;
        Cart::restore($cart);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(string $rowId): RedirectResponse
    {
        $cart = Cart::content();

        if (! isset($cart[$rowId])) {
            return back()->with('error', 'Cart item not found.');
        }

        unset($cart[$rowId]);
        Cart::restore($cart);

        return back()->with('success', 'Item removed from cart.');
    }
}
