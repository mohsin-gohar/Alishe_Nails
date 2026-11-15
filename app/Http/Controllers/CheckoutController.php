<?php

namespace App\Http\Controllers;

use App\Exceptions\CartUnavailableException;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        if (($unavailable = Cart::unavailableRows()) !== []) {
            return redirect()->route('cart.index')->with('error', $this->unavailableMessage($unavailable));
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
        [$coupon, $discount] = $this->resolveCoupon($request, $subtotal);
        $total = max(0, $subtotal + $res['fee'] - $discount);

        return response()->json([
            'subtotal' => round($subtotal, 2),
            'shipping' => round((float) $res['fee'], 2),
            'standard_fee' => round((float) $res['standard_fee'], 2),
            // Delivery is flat with no threshold; keep null-safe handling for
            // legacy rows or a future threshold-bearing rate without emitting
            // a PHP 8.2 "implicit conversion from float null" deprecation.
            'threshold' => $res['threshold'] === null ? null : round((float) $res['threshold'], 2),
            'is_free' => (bool) $res['is_free'],
            'discount' => round($discount, 2),
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

        if (($unavailable = Cart::unavailableRows()) !== []) {
            return redirect()->route('cart.index')->with('error', $this->unavailableMessage($unavailable));
        }

        try {
            [$order, $lineItems] = DB::transaction(function () use ($request, $cartContent) {
                // Lock every product row FIRST. Everything below — subtotal,
                // coupon discount, order header and order items — is computed
                // from these exact locked records, so a price change between
                // the cart page and checkout can never make the order header
                // disagree with its own line items.
                $productIds = collect($cartContent)->pluck('product_id')->unique()->values();

                /** @var Collection<int, Product> $products */
                $products = Product::with('seller')
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                [$pricedRows, $subtotal] = $this->priceLockedRows($cartContent, $products);

                $shippingRes = ShippingRate::calculateFee(
                    $request->validated('city'),
                    $request->validated('area'),
                    $subtotal
                );
                $shipping = $shippingRes['fee'];

                [$coupon, $discount] = $this->lockCoupon($request, $subtotal);
                $total = max(0, $subtotal + $shipping - $discount);

                $method = $request->input('payment_method') ?: 'online_payment';
                $ref = $request->input('transaction_reference') ?: 'Online Payment (Pending Contact)';
                $insta = $request->input('instagram_username');
                $sender = $request->input('sender_number');
                $notes = $request->input('payment_notes');
                $extra = [];
                if ($insta) {
                    $extra[] = "Insta: {$insta}";
                }
                if ($sender) {
                    $extra[] = "Sender: {$sender}";
                }
                if ($notes) {
                    $extra[] = "Note: {$notes}";
                }
                $finalRef = $ref.(! empty($extra) ? ' ('.implode(' | ', $extra).')' : '');

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
                    'payment_method' => $method,
                    'transaction_reference' => $finalRef,
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'discount_amount' => $discount,
                    'coupon_code' => $coupon?->code,
                    'total' => $total,
                ]);

                return [$order, $this->createOrderItems($order, $pricedRows, $products, $coupon, $request)];
            });

            // afterCommit: a database-queue worker can pick the job up the
            // instant it is pushed. Without this the worker may read an order
            // that has not been committed yet and fail all three attempts.
            SendOrderEmails::dispatch($order, $lineItems)->afterCommit();

            Cart::clear();

            return redirect()
                ->route('checkout.success', ['order' => $order->order_number, 'signature' => $order->access_token])
                ->with('success', 'Order placed successfully!');
        } catch (CartUnavailableException $e) {
            return redirect()->route('cart.index')
                ->with('error', $e->getMessage().' Please update or remove the item and try again.');
        } catch (\Throwable $e) {
            // Never disguise a database/queue failure as a cart problem. Log it
            // and let Laravel's normal error handling surface the real error.
            Log::error('Checkout order creation failed: '.$e->getMessage());

            throw $e;
        }
    }

    public function success(Request $request, string $order)
    {
        $orderModel = Order::where('order_number', $order)->firstOrFail();

        // access_token is nullable on rows created before tokens existed, and
        // hash_equals() only accepts strings — an old order must 403, not 500.
        $token = (string) $request->query('signature', '');
        $stored = (string) $orderModel->access_token;
        $validSignature = $token !== '' && $stored !== '' && hash_equals($stored, $token);
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
     * Human-readable list of the rows blocking checkout.
     *
     * @param  array<string, array{product_id:int, name:string, qty:int, stock:int}>  $rows
     */
    private function unavailableMessage(array $rows): string
    {
        $names = array_slice(array_values(array_unique(array_column($rows, 'name'))), 0, 3);
        $list = $names === [] ? '' : ': '.implode(', ', $names);

        return 'Some items in your cart are no longer available'.$list.'. Please update or remove them before checkout.';
    }

    /**
     * Validate the cart rows against the locked product records and freeze the
     * exact unit price and line total used for both the order header and its
     * items.
     *
     * Quantity is accumulated per product, so two cart rows that together ask
     * for more units than the locked stock are rejected instead of allowing a
     * negative stock after both decrements.
     *
     * @param  array<string, array<string, mixed>>  $cartContent
     * @param  Collection<int, Product>  $products
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function priceLockedRows(array $cartContent, Collection $products): array
    {
        $rows = [];
        $subtotal = 0.0;
        $requestedPerProduct = [];

        foreach ($cartContent as $row) {
            $product = $products->get($row['product_id']);
            $qty = (int) $row['qty'];

            if (! $product || ! $product->is_active || $qty < 1) {
                throw new CartUnavailableException('A product in your cart is no longer available.');
            }

            $productId = (int) $product->id;
            $requestedPerProduct[$productId] = ($requestedPerProduct[$productId] ?? 0) + $qty;

            if ($product->stock < $requestedPerProduct[$productId]) {
                throw new CartUnavailableException('A product in your cart is no longer available.');
            }

            $unitPrice = (float) $product->price;
            $lineTotal = round($unitPrice * $qty, 2);
            $subtotal += $lineTotal;

            $rows[] = array_merge($row, [
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        return [$rows, round($subtotal, 2)];
    }

    /**
     * Create order items from the exact prices frozen in priceLockedRows().
     * The surrounding transaction already holds the product row locks.
     *
     * @param  array<int, array<string, mixed>>  $pricedRows
     * @param  Collection<int, Product>  $products
     * @return array<int, OrderItem>
     */
    private function createOrderItems(
        Order $order,
        array $pricedRows,
        Collection $products,
        ?Coupon $coupon,
        Request $request
    ): array {
        $lineItems = [];

        foreach ($pricedRows as $row) {
            $product = $products->get($row['product_id']);

            if (! $product) {
                throw new CartUnavailableException('A product in your cart is no longer available.');
            }

            $lineTotal = (float) $row['line_total'];

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'shape' => $row['shape'],
                'size' => $row['size'],
                'quantity' => $row['qty'],
                'price' => $row['unit_price'],
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

    /**
     * Resolves the session coupon and locks its row for the rest of the order
     * transaction. Two simultaneous checkouts then serialise on that row, so
     * the last redemption of a limited coupon cannot be spent twice.
     *
     * @return array{0:Coupon|null,1:float}
     */
    private function lockCoupon(Request $request, float $subtotal): array
    {
        $couponCode = data_get($request->session()->get('applied_coupon'), 'code');

        if (! $couponCode) {
            return [null, 0.0];
        }

        $coupon = Coupon::where('code', strtoupper((string) $couponCode))->lockForUpdate()->first();

        if (! $coupon || ! $coupon->isValid()) {
            $request->session()->forget('applied_coupon');

            return [null, 0.0];
        }

        $discount = min($coupon->getDiscountFor($subtotal), $subtotal);

        return [$coupon, $discount];
    }

    /**
     * Read-only coupon lookup for the cart/checkout display.
     */
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
