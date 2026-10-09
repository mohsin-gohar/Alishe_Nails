<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Jobs\SendOrderEmails;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Support\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutController extends Controller
{
    /**
     * Platform commission rate applied to marketplace products when the
     * seller does NOT have an active subscription. Sellers with a valid
     * subscription pay no per-sale commission (subscription model).
     */
    private const COMMISSION_RATE = 15.00;

    public function index(Request $request)
    {
        if (empty(Cart::content())) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = Cart::subtotal();

        $shipping = ShippingRate::calculateFee(
            old('city', 'Karachi'),
            old('area'),
            $subtotal
        )['fee'];

        [$coupon, $discount] = $this->resolveCoupon($request, $subtotal);

        $total = max(0, $subtotal + $shipping - $discount);

        return view('checkout.index', [
            'items' => Cart::content(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'coupon' => $coupon,
            'total' => $total,
            'user' => $request->user(),
        ]);
    }

    public function calculateShippingFee(Request $request): JsonResponse
    {
        $city = $request->string('city')->trim()->toString() ?: 'Karachi';
        $area = $request->string('area')->trim()->toString() ?: null;
        $subtotal = Cart::subtotal();

        $res = ShippingRate::calculateFee($city, $area, $subtotal);
        $total = $subtotal + $res['fee'];

        return response()->json([
            'subtotal' => round($subtotal, 2),
            'shipping' => round($res['fee'], 2),
            'standard_fee' => round($res['standard_fee'], 2),
            'threshold' => round($res['threshold'], 2),
            'is_free' => $res['is_free'],
            'total' => round($total, 2),
            'zone_label' => $res['zone_label'],
        ]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $cartContent = Cart::content();

        if (empty($cartContent)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        try {
            [$order, $lineItems] = DB::transaction(function () use ($request, $cartContent) {
                $subtotal = Cart::subtotal();

                $shippingRes = ShippingRate::calculateFee(
                    $request->validated('city'),
                    $request->validated('area'),
                    $subtotal
                );
                $shipping = $shippingRes['fee'];

                [$coupon, $discount] = $this->resolveCoupon($request, $subtotal);
                $total = max(0, $subtotal + $shipping - $discount);

                $ref = $request->validated('transaction_reference');
                $sender = $request->input('sender_number');
                $notes = $request->input('payment_notes');
                $extra = [];
                if ($sender) {
                    $extra[] = "Sender: {$sender}";
                }
                if ($notes) {
                    $extra[] = "Note: {$notes}";
                }
                $finalRef = $ref ? ($ref.(! empty($extra) ? ' ('.implode(' | ', $extra).')' : '')) : null;

                $order = Order::create([
                    'user_id' => $request->user()?->id,
                    'first_name' => $request->validated('first_name'),
                    'last_name' => $request->validated('last_name'),
                    'email' => $request->validated('email'),
                    'phone' => $request->validated('phone'),
                    'address' => $request->validated('address'),
                    'city' => $request->validated('city'),
                    'area' => $request->validated('area'),
                    'postal_code' => $request->validated('postal_code'),
                    'payment_method' => $request->validated('payment_method'),
                    'transaction_reference' => $finalRef,
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'discount_amount' => $discount,
                    'coupon_code' => $coupon?->code,
                    'total' => $total,
                ]);

                return [$order, $this->createOrderItems($order, $cartContent, $coupon, $request)];
            });

            SendOrderEmails::dispatch($order, $lineItems);

            Cart::clear();

            return redirect()
                ->route('checkout.success', ['order' => $order->order_number, 'signature' => $order->access_token])
                ->with('success', 'Order placed successfully!');
        } catch (\Throwable $e) {
            Log::error('Checkout order creation failed: '.$e->getMessage());

            return redirect()->route('cart.index')
                ->with('error', 'We could not complete your order. Please review your cart and try again.');
        }
    }

    public function success(Request $request, string $order)
    {
        $orderModel = Order::where('order_number', $order)->firstOrFail();

        $token = $request->query('signature');
        $validSignature = is_string($token) && hash_equals($orderModel->access_token, $token);
        $isOwner = $request->user() && $request->user()->id === $orderModel->user_id;

        if (! $validSignature && ! $isOwner) {
            abort(403, 'You are not authorized to view this order.');
        }

        $orderModel->load('items');

        return view('checkout.success', ['order' => $orderModel]);
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($validated['code'])))->first();

        if (! $coupon || ! $coupon->isValid()) {
            return back()->with('error', 'That promo code is invalid or has expired.');
        }

        $request->session()->put('applied_coupon', ['code' => $coupon->code]);

        return redirect()->route('checkout.index')->with('success', 'Coupon applied successfully.');
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $request->session()->forget('applied_coupon');

        return redirect()->route('checkout.index')->with('success', 'Promo code removed.');
    }

    /**
     * Create order items with server-side stock locking, price snapshots and
     * frozen commission values. Throws if any product is unavailable so the
     * surrounding transaction rolls back cleanly.
     *
     * @return array<int, OrderItem>
     */
    private function createOrderItems(Order $order, array $cartContent, ?Coupon $coupon, Request $request): array
    {
        $productIds = collect($cartContent)->pluck('product_id')->unique();
        $products = Product::with('seller')->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        $lineItems = [];

        foreach ($cartContent as $row) {
            $product = $products->get($row['product_id']);

            if (! $product || ! $product->is_active || $product->stock < $row['qty']) {
                throw new RuntimeException('A product in your cart is no longer available.');
            }

            $lineTotal = round((float) $product->price * $row['qty'], 2);

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'shape' => $row['shape'],
                'size' => $row['size'],
                'quantity' => $row['qty'],
                'price' => $product->price,
                'line_total' => $lineTotal,
                'seller_id' => $product->seller_id,
                'commission_rate' => 0.00,
                'commission_amount' => 0.00,
                'seller_earning' => 0.00,
            ]);

            if ($product->seller_id) {
                $commissionRate = $product->seller && $product->seller->hasActiveSubscription()
                    ? 0.00
                    : self::COMMISSION_RATE;

                $commissionAmount = round($lineTotal * ($commissionRate / 100), 2);

                $orderItem->update([
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'seller_earning' => round($lineTotal - $commissionAmount, 2),
                    'payout_status' => 'pending',
                ]);
            }

            $lineItems[] = $orderItem;

            $product->decrement('stock', $row['qty']);
        }

        if ($coupon) {
            $coupon->increment('used_count');
        }

        $request->session()->forget('applied_coupon');

        return $lineItems;
    }

    private function resolveCoupon(Request $request, float $subtotal): array
    {
        $couponCode = data_get($request->session()->get('applied_coupon'), 'code');

        if (! $couponCode) {
            return [null, 0.0];
        }

        $coupon = Coupon::where('code', strtoupper($couponCode))->first();

        if (! $coupon || ! $coupon->isValid()) {
            $request->session()->forget('applied_coupon');

            return [null, 0.0];
        }

        $discount = min($coupon->getDiscountFor($subtotal), $subtotal);

        return [$coupon, $discount];
    }
}
