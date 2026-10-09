<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'payment_method' => 'cod',
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

    public function test_order_shipping_uses_the_selected_city_and_area_rate(): void
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
        $expected = ShippingRate::calculateFee('Lahore', 'Gulberg', (float) $order->subtotal)['fee'];

        $this->assertSame($expected, (float) $order->shipping);
        $this->assertSame('54660', $order->postal_code);
        $this->assertSame('BANK-REF-123', $order->transaction_reference);
    }

    public function test_unknown_city_uses_the_other_cities_shipping_rate(): void
    {
        ShippingRate::create([
            'city' => 'Other Cities',
            'area' => null,
            'delivery_fee' => 275,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
        ]);

        $shipping = ShippingRate::calculateFee('Multan', null, 2000);

        $this->assertSame(275.0, $shipping['fee']);
        $this->assertSame('Other Cities', $shipping['zone_label']);
    }

    public function test_non_cod_payment_requires_a_transaction_reference(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $response = $this->post(route('checkout.store'), array_merge($this->checkoutPayload(), [
            'payment_method' => 'bank_transfer',
        ]));

        $response->assertSessionHasErrors('transaction_reference');
        $this->assertDatabaseMissing('orders', ['email' => 'sana@example.com']);
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

    public function test_checkout_shipping_fee_endpoint_calculates_karachi_area_rates(): void
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
                'shipping' => 200,
                'is_free' => false,
                'total' => 2700,
            ]);
    }

    public function test_checkout_shipping_fee_is_free_over_threshold_for_karachi(): void
    {
        ShippingRate::create([
            'city' => 'Karachi',
            'area' => 'Gulshan-e-Iqbal',
            'delivery_fee' => 180,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
        ]);

        $product = $this->makeProduct(['price' => 3000]);
        $this->post(route('cart.add', $product), ['qty' => 2]); // total 6000 >= 5000

        $response = $this->getJson(route('checkout.shippingFee', [
            'city' => 'Karachi',
            'area' => 'Gulshan-e-Iqbal',
        ]));

        $response->assertOk()
            ->assertJson([
                'subtotal' => 6000,
                'shipping' => 0,
                'is_free' => true,
                'total' => 6000,
            ]);
    }

    public function test_checkout_with_jazzcash_requires_transaction_reference(): void
    {
        $product = $this->makeProduct(['price' => 1500]);
        $this->post(route('cart.add', $product), ['qty' => 1]);

        $payload = array_merge($this->checkoutPayload(), [
            'payment_method' => 'jazzcash',
            'transaction_reference' => null,
        ]);

        $response = $this->post(route('checkout.store'), $payload);

        $response->assertSessionHasErrors('transaction_reference');
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
}
