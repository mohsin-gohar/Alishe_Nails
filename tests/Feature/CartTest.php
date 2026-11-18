<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
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

    public function test_a_product_can_be_added_to_the_cart(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('cart.add', $product), ['qty' => 2]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(2, session('alishe_cart') ? array_sum(array_column(session('alishe_cart'), 'qty')) : 0);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = $this->makeProduct(['stock' => 3]);

        $response = $this->post(route('cart.add', $product), ['qty' => 10]);

        $response->assertSessionHas('error');
        $cart = session('alishe_cart', []);
        $this->assertEmpty($cart, 'Cart should not contain an item that exceeds available stock.');
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $product = $this->makeProduct(['stock' => 0]);

        $response = $this->post(route('cart.add', $product), ['qty' => 1]);

        $response->assertSessionHas('error');
        $this->assertEmpty(session('alishe_cart', []));
    }

    public function test_cart_quantity_is_capped_at_current_stock_on_update(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        $cart = session('alishe_cart');
        $rowId = array_key_first($cart);

        // Stock drops to 1 after the item was added (e.g. another order came in).
        $product->update(['stock' => 1]);

        $this->patch(route('cart.update', $rowId), ['qty' => 5]);

        $cart = session('alishe_cart');
        $this->assertEquals(1, $cart[$rowId]['qty'], 'Quantity must never exceed live stock, even if requested higher.');
    }

    public function test_shop_add_to_cart_can_return_json_without_redirecting(): void
    {
        $product = Product::create([
            'name' => 'AJAX Nail Set',
            'sku' => 'ALN-AJX-1',
            'price' => 2500,
            'stock' => 10,
            'is_active' => true,
            'slug' => 'ajax-nail-set',
        ]);

        $response = $this->postJson(route('cart.add', $product), ['qty' => 1]);

        $response->assertOk()
            ->assertJsonPath('cart_count', 1)
            ->assertJsonStructure(['message', 'cart_count']);
    }

    public function test_zero_stock_cart_item_is_shown_as_unavailable_and_does_not_count_in_totals(): void
    {
        $product = $this->makeProduct(['stock' => 3, 'price' => 2000]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        // Stock becomes zero after the item was added (e.g. sold out elsewhere).
        $product->update(['stock' => 0]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('Currently unavailable');
        $response->assertSee('Out of stock');
        // Quantity controls must be disabled for an unavailable row.
        $response->assertSee('disabled', false);
        // Subtotal and estimated total must not include the unavailable item.
        $response->assertSee('PKR 0');
        $response->assertSee('PKR 300'); // flat delivery only
        $response->assertDontSee('PKR 4,000');
        $response->assertDontSee('PKR 4,300');
    }

    public function test_cart_rows_saved_before_shape_and_size_existed_still_merge(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        // Session rows written by an older version of the cart have no
        // shape/size keys at all; adding the same product must merge into
        // that row instead of throwing an undefined-array-key error.
        $this->withSession(['alishe_cart' => [
            'legacy-row' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => null,
                'price' => 1000,
                'qty' => 1,
            ],
        ]]);

        $this->post(route('cart.add', $product), ['qty' => 2])->assertRedirect();

        $this->assertSame(3, (int) session('alishe_cart')['legacy-row']['qty']);
        $this->assertCount(1, session('alishe_cart'));
    }

    public function test_cart_update_to_zero_removes_the_item(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        $cart = session('alishe_cart');
        $rowId = array_key_first($cart);

        $this->patch(route('cart.update', $rowId), ['qty' => 0])
            ->assertSessionHas('success');

        $this->assertEmpty(session('alishe_cart', []));
    }

    public function test_updating_a_zero_stock_cart_item_removes_it(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        $cart = session('alishe_cart');
        $rowId = array_key_first($cart);

        $product->update(['stock' => 0]);

        $this->patch(route('cart.update', $rowId), ['qty' => 1])
            ->assertSessionHas('error');

        $this->assertEmpty(session('alishe_cart', []));
    }

    public function test_subtotal_ignores_zero_stock_and_inactive_products(): void
    {
        $available = $this->makeProduct(['price' => 1000, 'stock' => 5]);
        $soldOut = $this->makeProduct(['price' => 2000, 'stock' => 0]);
        $inactive = $this->makeProduct(['price' => 3000, 'stock' => 5, 'is_active' => false]);

        $this->withSession(['alishe_cart' => [
            'row-available' => $this->cartRow($available, 2),
            'row-sold-out' => $this->cartRow($soldOut, 1),
            'row-inactive' => $this->cartRow($inactive, 1),
        ]]);

        $this->assertSame(2000.0, Cart::subtotal(), 'Only the buyable row may be priced.');
        $this->assertSame(
            ['row-sold-out', 'row-inactive'],
            array_keys(Cart::unavailableRows()),
            'The sold-out and inactive rows must be flagged as unavailable.'
        );
    }

    public function test_subtotal_never_charges_more_units_than_live_stock(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);

        $this->withSession(['alishe_cart' => [
            'row-over-stock' => $this->cartRow($product, 4),
        ]]);

        // Another order takes three of the five units after the cart was saved.
        $product->update(['stock' => 1]);

        $this->assertSame(1000.0, Cart::subtotal(), 'The subtotal must cap the quantity at the live stock.');
        $this->assertArrayHasKey('row-over-stock', Cart::unavailableRows());
    }

    public function test_two_cart_rows_for_one_product_cannot_be_priced_for_more_than_live_stock(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 5]);

        // Two separate cart rows for the same product: 3 + 3 requested, only 5
        // units exist. Stock must be consumed in cart order, never per row.
        $this->withSession(['alishe_cart' => [
            'row-first' => $this->cartRow($product, 3),
            'row-second' => $this->cartRow($product, 3),
        ]]);

        $this->assertSame(5000.0, Cart::subtotal(), 'Never price more than the five live units.');
        $this->assertSame(
            ['row-first', 'row-second'],
            array_keys(Cart::unavailableRows()),
            'The combined requested quantity exceeds stock, so both rows block checkout.'
        );

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('PKR 5,000')
            ->assertDontSee('PKR 6,000');
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
