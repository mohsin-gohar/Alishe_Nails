<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Support\Cart;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category-'.Str::random(6)]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Test Nail Set',
            'slug' => 'test-nail-set-'.Str::random(6),
            'sku' => 'SKU-'.Str::random(6),
            'price' => 2000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    private function checkoutPayload(): array
    {
        return [
            'first_name' => 'Sana',
            'last_name' => 'Malik',
            'email' => 'sana@example.com',
            'phone' => '+92 300 1234567',
            'address' => '123 Blossom Lane',
            'city' => 'Karachi',
            'payment_method' => 'jazzcash',
            'transaction_reference' => 'TID-TEST-123',
            'instagram_username' => '@sana_nails',
        ];
    }

    public function test_a_successful_order_decreases_product_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        $response = $this->post(route('checkout.store'), $this->checkoutPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['email' => 'sana@example.com', 'status' => 'pending']);
        $this->assertEquals(3, $product->fresh()->stock, 'Stock must decrease by the ordered quantity.');
        $this->assertEmpty(Cart::content(), 'Cart must be cleared after successful checkout.');
    }

    public function test_a_valid_coupon_discounts_the_order_and_counts_one_use(): void
    {
        $product = $this->makeProduct(['price' => 2000, 'stock' => 10]);

        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $product), ['qty' => 1]);

        $this->withSession(['applied_coupon' => ['code' => 'SAVE10']])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $order = Order::latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame('SAVE10', $order->coupon_code);
        $this->assertGreaterThan(0.0, (float) $order->discount_amount);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_a_coupon_that_reached_its_usage_limit_is_not_applied(): void
    {
        $product = $this->makeProduct(['price' => 2000, 'stock' => 10]);

        Coupon::create([
            'code' => 'LASTONE',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 1,
            'used_count' => 1,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $product), ['qty' => 1]);

        $this->withSession(['applied_coupon' => ['code' => 'LASTONE']])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $order = Order::latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->coupon_code, 'An exhausted coupon must not be written to the order.');
        $this->assertSame(0.0, (float) $order->discount_amount);
    }

    public function test_checkout_is_blocked_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct(['stock' => 2]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // Someone else buys the remaining stock between add-to-cart and checkout.
        $product->update(['stock' => 1]);

        $response = $this->post(route('checkout.store'), $this->checkoutPayload());

        $response->assertRedirect(route('cart.index'));
        $this->assertEquals(1, $product->fresh()->stock, 'Stock must not change when checkout is rejected.');
        $this->assertDatabaseMissing('orders', ['email' => 'sana@example.com']);
    }

    public function test_order_total_uses_the_current_database_price_not_a_client_supplied_one(): void
    {
        $product = $this->makeProduct(['price' => 2000, 'stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        // Simulate the price changing after it was added to the cart/session.
        $product->update(['price' => 9999]);

        $this->post(route('checkout.store'), $this->checkoutPayload());

        $order = Order::where('email', 'sana@example.com')->first();
        $this->assertEquals(9999, (float) $order->subtotal, 'The order must charge the live DB price, never a stale cart price.');
    }

    public function test_price_change_after_cart_is_reflected_in_cart_checkout_and_order_item_totals(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // Price increases after the cart was built.
        $product->update(['price' => 1500]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('PKR 3,000')  // subtotal at live price (2 x 1500)
            ->assertSee('PKR 3,300')  // subtotal + flat 300 delivery
            ->assertDontSee('PKR 2,000') // never the stale session/cart line
            ->assertDontSee('PKR 2,300');

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
        ]));

        $response->assertOk()->assertJson([
            'subtotal' => 3000,
            'shipping' => 300,
            'is_free' => false,
            'total' => 3300,
        ]);

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::where('email', 'sana@example.com')->firstOrFail();
        $this->assertSame(3000.0, (float) $order->subtotal);
        $this->assertSame(300.0, (float) $order->shipping);
        $this->assertSame(3300.0, (float) $order->total);

        $item = $order->items()->firstOrFail();
        $this->assertSame(1500.0, (float) $item->price);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(3000.0, (float) $item->line_total);
    }

    public function test_checkout_is_blocked_when_an_item_sells_out_after_being_added(): void
    {
        $product = $this->makeProduct(['price' => 2000, 'stock' => 2]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // The last units sell elsewhere, so the cart row is now impossible.
        $product->update(['stock' => 0]);

        // Cart and checkout must agree: the unavailable row is not priced.
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Currently unavailable')
            ->assertSee('PKR 300')      // flat delivery only
            ->assertDontSee('PKR 4,000')
            ->assertDontSee('PKR 4,300');

        $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
        ]))->assertOk()->assertJson([
            'subtotal' => 0,
            'shipping' => 300,
            'total' => 300,
        ]);

        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', ['email' => 'sana@example.com']);
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_price_drop_after_cart_lowers_cart_checkout_order_and_order_item_totals(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);
        $this->post(route('cart.add', $product), ['qty' => 3]);

        // Price drops after the cart was built.
        $product->update(['price' => 600]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('PKR 1,800') // 3 x 600, never the stale 3 x 1000
            ->assertSee('PKR 2,100')
            ->assertDontSee('PKR 3,000')
            ->assertDontSee('PKR 3,300');

        $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
        ]))->assertOk()->assertJson([
            'subtotal' => 1800,
            'shipping' => 300,
            'is_free' => false,
            'total' => 2100,
        ]);

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::where('email', 'sana@example.com')->firstOrFail();
        $this->assertSame(1800.0, (float) $order->subtotal);
        $this->assertSame(300.0, (float) $order->shipping);
        $this->assertSame(2100.0, (float) $order->total);

        $item = $order->items()->firstOrFail();
        $this->assertSame(600.0, (float) $item->price);
        $this->assertSame(3, (int) $item->quantity);
        $this->assertSame(1800.0, (float) $item->line_total);
    }

    public function test_shipping_fee_endpoint_is_null_safe_when_threshold_is_absent(): void
    {
        // calculateFee() always returns threshold => null for the flat model.
        // The endpoint must serialise this as JSON null (not a PHP 8.2
        // "implicit conversion from float null" deprecation / TypeError).
        $product = $this->makeProduct(['price' => 1200]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
        ]));

        $response->assertOk();
        $json = $response->json();
        $this->assertNull($json['threshold']);
        $this->assertSame(300, $json['shipping']);
        $this->assertSame(1500, $json['total']);
        $this->assertFalse($json['is_free']);
    }

    public function test_order_charges_the_flat_delivery_fee_regardless_of_zone(): void
    {
        $product = $this->makeProduct(['price' => 2000, 'stock' => 5]);
        ShippingRate::create([
            'city' => 'Lahore',
            'area' => 'Gulberg',
            'delivery_fee' => 175,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
        ]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'city' => 'Lahore',
            'area' => 'Gulberg',
            'postal_code' => '54660',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-REF-123',
        ]);
        $this->post(route('checkout.store'), $payload);

        $order = Order::where('email', 'sana@example.com')->first();

        // A row that says 175 must never change what the customer pays.
        $this->assertSame(ShippingRate::FLAT_FEE, (float) $order->shipping);
        $this->assertSame(2000 + ShippingRate::FLAT_FEE, (float) $order->total);
        $this->assertSame('54660', $order->postal_code);
        $this->assertStringContainsString('BANK-REF-123', $order->transaction_reference);
    }

    public function test_delivery_fee_is_flat_even_when_no_zone_matches(): void
    {
        $shipping = ShippingRate::calculateFee('Multan', null, 2000);

        $this->assertSame(ShippingRate::FLAT_FEE, $shipping['fee']);
        $this->assertFalse($shipping['is_free']);
        $this->assertSame('Multan', $shipping['zone_label']);
    }

    public function test_checkout_can_be_completed_without_transaction_reference(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = $this->checkoutPayload();
        unset($payload['transaction_reference']);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['email' => 'sana@example.com']);
    }

    public function test_guest_order_confirmation_requires_the_correct_access_token(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add', $product), ['qty' => 1]);
        $this->post(route('checkout.store'), $this->checkoutPayload());

        $order = Order::where('email', 'sana@example.com')->first();

        // Guessing the order number without the signature must fail.
        $this->get(route('checkout.success', $order->order_number))->assertForbidden();

        // The correct access token succeeds.
        $this->get(route('checkout.success', ['order' => $order->order_number, 'signature' => $order->access_token]))
            ->assertOk();
    }

    public function test_checkout_shipping_fee_endpoint_returns_the_flat_fee(): void
    {
        ShippingRate::create([
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
            'delivery_fee' => 200,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
        ]);

        $product = $this->makeProduct(['price' => 2500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'DHA (Defence)',
        ]));

        $response->assertOk()
            ->assertJson([
                'subtotal' => 2500,
                'shipping' => 300,
                'is_free' => false,
                'total' => 2800,
            ]);
    }

    public function test_delivery_is_never_free_even_for_a_large_cart(): void
    {
        ShippingRate::create([
            'city' => 'Karachi',
            'area' => 'Gulshan-e-Iqbal',
            'delivery_fee' => 180,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
        ]);

        $product = $this->makeProduct(['price' => 3000]);
        $this->post(route('cart.add', $product), ['qty' => 2]); // total 6000

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'Gulshan-e-Iqbal',
        ]));

        $response->assertOk()
            ->assertJson([
                'subtotal' => 6000,
                'shipping' => 300,
                'is_free' => false,
                'total' => 6300,
            ]);
    }

    public function test_checkout_summary_never_shows_coordinated_directly(): void
    {
        ShippingRate::query()->delete();

        $product = $this->makeProduct(['price' => 2500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('PKR 300')
            ->assertDontSee('Coordinated directly');
    }

    public function test_shipping_falls_back_to_the_flat_karachi_fee_when_no_rate_matches(): void
    {
        // Alishe Nails charges a flat PKR 300 across Karachi, even if the
        // customer picks a zone the owner has not configured yet.
        $product = $this->makeProduct(['price' => 2500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'A Zone That Does Not Exist',
        ]));

        $response->assertOk()
            ->assertJson([
                'subtotal' => 2500,
                'shipping' => 300,
                'is_free' => false,
                'total' => 2800,
            ]);
    }

    public function test_cart_shows_the_flat_karachi_delivery_fee_before_checkout(): void
    {
        ShippingRate::query()->delete();

        $product = $this->makeProduct(['price' => 2500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('PKR 300')
            ->assertSee('PKR 2,800');
    }

    public function test_checkout_with_online_payment_defaults_reference_when_omitted(): void
    {
        $product = $this->makeProduct(['price' => 1500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'payment_method' => 'online_payment',
            'transaction_reference' => null,
        ]);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertRedirect();
        $order = Order::where('email', 'sana@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('online_payment', $order->payment_method);
        $this->assertStringContainsString('Online Payment', $order->transaction_reference);
    }

    public function test_checkout_with_easypaisa_creates_order_with_reference_and_sender_info(): void
    {
        $product = $this->makeProduct(['price' => 1500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'payment_method' => 'easypaisa',
            'transaction_reference' => 'EP-TID-998877',
            'sender_number' => '03412126680',
            'payment_notes' => 'Sent from EasyPaisa app',
        ]);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertRedirect();
        $order = Order::where('email', 'sana@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('easypaisa', $order->payment_method);
        $this->assertStringContainsString('EP-TID-998877', $order->transaction_reference);
        $this->assertStringContainsString('Sender: 03412126680', $order->transaction_reference);
    }

    public function test_checkout_with_sadapay_creates_order_with_reference_and_sender_info(): void
    {
        $product = $this->makeProduct(['price' => 1500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'payment_method' => 'sadapay',
            'transaction_reference' => 'SADA-TID-554433',
            'sender_number' => '03113925166',
            'payment_notes' => 'Sent via SadaPay',
        ]);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertRedirect();
        $order = Order::where('email', 'sana@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('sadapay', $order->payment_method);
        $this->assertStringContainsString('SADA-TID-554433', $order->transaction_reference);
        $this->assertStringContainsString('Sender: 03113925166', $order->transaction_reference);
    }

    public function test_checkout_with_cod_is_rejected_because_advance_online_payment_is_required(): void
    {
        $product = $this->makeProduct(['price' => 1800]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'payment_method' => 'cod',
            'transaction_reference' => null,
            'instagram_username' => '@alishba_fan',
        ]);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertSessionHasErrors('payment_method');
        $this->assertDatabaseMissing('orders', ['email' => 'sana@example.com']);
    }

    public function test_order_header_subtotal_always_equals_the_sum_of_its_item_totals(): void
    {
        $first = $this->makeProduct(['price' => 1000, 'stock' => 10]);
        $second = $this->makeProduct(['price' => 500, 'stock' => 10]);

        $this->post(route('cart.add', $first), ['qty' => 2]);
        $this->post(route('cart.add', $second), ['qty' => 3]);

        // Both prices change after the cart was built.
        $first->update(['price' => 1200]);
        $second->update(['price' => 400]);

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::where('email', 'sana@example.com')->firstOrFail();
        $items = $order->items()->get();

        $this->assertCount(2, $items);
        $this->assertSame(3600.0, (float) $order->subtotal, '2 x 1200 + 3 x 400 at the live prices.');
        $this->assertSame(
            round((float) $items->sum('line_total'), 2),
            round((float) $order->subtotal, 2),
            'The order header must equal the sum of the line items it stores.'
        );
        $this->assertSame(3900.0, (float) $order->total, 'subtotal + flat 300 delivery.');
    }

    public function test_price_changed_during_checkout_cannot_split_the_order_header_from_its_items(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // Change the price exactly when checkout reads the locked product row.
        // The rows returned by that SELECT carry the old price, so the whole
        // order must be built from that same snapshot. Before the fix, the
        // subtotal was calculated by an earlier query and would disagree with
        // the line items created from the later locked read.
        $mutated = false;

        DB::listen(function ($query) use ($product, &$mutated) {
            if ($mutated) {
                return;
            }

            $sql = $query->sql;

            if (! str_contains($sql, 'from "products"') && ! str_contains($sql, 'from `products`')) {
                return;
            }

            $selectsPrice = str_contains($sql, '"price"')
                || str_contains($sql, '`price`')
                || str_contains($sql, 'select *');

            if (! $selectsPrice) {
                return;
            }

            $mutated = true;

            Product::whereKey($product->id)->update(['price' => 9999]);
        });

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $this->assertTrue($mutated, 'The price change must happen during checkout for this test to prove anything.');

        $order = Order::where('email', 'sana@example.com')->firstOrFail();
        $items = $order->items()->get();

        // The price really changed in the database during the transaction ...
        $this->assertSame(9999.0, (float) $product->fresh()->price);

        // ... yet header and items stay on the same frozen, locked snapshot.
        $this->assertSame(2000.0, (float) $order->subtotal);
        $this->assertSame(2300.0, (float) $order->total);
        $this->assertSame(1000.0, (float) $items->firstOrFail()->price);
        $this->assertSame(
            round((float) $items->sum('line_total'), 2),
            round((float) $order->subtotal, 2),
            'A price change mid-checkout must never split the order header from its items.'
        );
    }

    public function test_checkout_is_blocked_when_two_rows_for_one_product_exceed_stock(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);

        $this->withSession(['alishe_cart' => [
            'row-first' => $this->cartRow($product, 3),
            'row-second' => $this->cartRow($product, 3),
        ]]);

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, (int) $product->fresh()->stock, 'Stock must not change when checkout is rejected.');
    }

    public function test_database_failure_during_checkout_rolls_back_and_is_not_disguised_as_a_cart_error(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // Simulate the database failing while the order is being written.
        Order::creating(function () {
            throw new QueryException(
                'sqlite',
                'insert into "orders" (...) values (...)',
                [],
                new \RuntimeException('database unavailable')
            );
        });

        $this->withoutExceptionHandling();

        try {
            $this->post(route('checkout.store'), $this->checkoutPayload());
            $this->fail('A database failure must not be swallowed as a cart/status message.');
        } catch (QueryException $e) {
            // Expected: the real database exception reached the caller.
        }

        $this->assertSame(5, (int) $product->fresh()->stock, 'The order transaction must roll back stock changes.');
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotEmpty(Cart::content(), 'The cart must survive a failed order.');
    }

    public function test_checkout_is_blocked_when_a_product_becomes_inactive(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $product->update(['is_active' => false]);

        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, (int) $product->fresh()->stock, 'Stock must not change when checkout is rejected.');
    }

    public function test_checkout_is_blocked_when_a_product_is_deleted(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $product->delete();

        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_coupon_and_shipping_are_calculated_from_the_locked_live_subtotal(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);

        Coupon::create([
            'code' => 'LOCKED10',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $product), ['qty' => 2]);

        // The price doubles AFTER the coupon was applied in the session.
        // Discount and shipping must both derive from the one locked subtotal.
        $product->update(['price' => 2000]);

        $this->withSession(['applied_coupon' => ['code' => 'LOCKED10']])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect();

        $order = Order::where('email', 'sana@example.com')->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame(4000.0, (float) $order->subtotal, '2 x 2000 at the locked live price.');
        $this->assertSame(400.0, (float) $order->discount_amount, '10% of the SAME locked subtotal.');
        $this->assertSame(300.0, (float) $order->shipping, 'Flat delivery on the locked subtotal.');
        $this->assertSame(3900.0, (float) $order->total, '4000 + 300 - 400.');
        $this->assertSame(4000.0, (float) $item->line_total, 'Item total must match the header subtotal.');
        $this->assertSame(
            round((float) $order->subtotal - (float) $order->discount_amount + (float) $order->shipping, 2),
            round((float) $order->total, 2)
        );
    }

    private function cartRow(Product $product, int $qty): array
    {
        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => null,
            'price' => (float) $product->price,
            'shape' => null,
            'size' => null,
            'qty' => $qty,
        ];
    }
}
