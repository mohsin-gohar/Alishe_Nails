<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use App\Services\SellerSubscriptionReviewService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function makeSeller(array $overrides = []): Seller
    {
        return Seller::create(array_merge([
            'name' => 'Nail Creator',
            'email' => 'creator@example.com',
            'instagram_handle' => 'nailcreator',
            'product_details' => 'Chrome and almond sets',
            'password' => Hash::make('SellerPass123'),
            'status' => 'approved',
            'approved_at' => now(),
        ], $overrides));
    }

    private function makeProduct(Seller $seller): Product
    {
        return Product::create([
            'name' => 'Creator Chrome Set',
            'slug' => 'creator-chrome-'.Str::random(6),
            'sku' => 'SEL-'.Str::random(6),
            'price' => 2000,
            'stock' => 5,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
    }

    private function activateSubscription(Seller $seller): SellerSubscription
    {
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);

        return SellerSubscription::create([
            'seller_id' => $seller->id,
            'subscription_plan_id' => $plan->id,
            'price' => 1000,
            'duration_days' => 30,
            'status' => 'active',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TEST-REF',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    private function checkoutPayload(): array
    {
        return [
            'first_name' => 'Sana', 'last_name' => 'Malik', 'email' => 'sana@example.com',
            'phone' => '+92 300 1234567', 'address' => '123 Blossom Lane', 'city' => 'Karachi',
            'payment_method' => 'jazzcash', 'transaction_reference' => 'TID-TEST-123',
        ];
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'first_name' => 'Sana',
            'last_name' => 'Malik',
            'email' => 'sana@example.com',
            'phone' => '03001234567',
            'address' => '123 Blossom Lane',
            'city' => 'Karachi',
            'payment_method' => 'jazzcash',
            'subtotal' => 1000,
            'shipping' => 300,
            'total' => 1300,
            'status' => 'pending',
        ], $overrides));
    }

    private function makeItem(Seller $seller, Order $order, array $overrides = []): OrderItem
    {
        return OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => null,
            'seller_id' => $seller->id,
            'product_name' => 'Creator Chrome Set',
            'quantity' => 1,
            'price' => 1000,
            'line_total' => 1000,
            'commission_rate' => 0,
            'commission_amount' => 0,
            'seller_earning' => 1000,
            'payout_status' => 'pending',
        ], $overrides));
    }

    public function test_influencer_can_apply_and_is_not_logged_in_before_approval(): void
    {
        $response = $this->post(route('seller.apply.store'), [
            'name' => 'New Creator', 'email' => 'new@example.com', 'instagram_handle' => 'newcreator',
            'product_details' => 'Hand-painted sets', 'password' => 'SellerPass123', 'password_confirmation' => 'SellerPass123',
        ]);

        $response->assertRedirect(route('seller.login'));
        $this->assertDatabaseHas('sellers', ['email' => 'new@example.com', 'status' => 'pending']);
        $this->assertGuest('seller');
    }

    public function test_admin_can_approve_seller_and_seller_can_open_dashboard(): void
    {
        $seller = $this->makeSeller(['status' => 'pending', 'approved_at' => null]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')->post(route('admin.marketplace.sellers.approve', $seller))->assertRedirect();
        $this->assertDatabaseHas('sellers', ['id' => $seller->id, 'status' => 'approved']);

        $this->post(route('seller.login.attempt'), ['email' => $seller->email, 'password' => 'SellerPass123'])
            ->assertRedirect(route('seller.dashboard'));
        $this->assertAuthenticatedAs($seller, 'seller');
        $this->get(route('seller.dashboard'))->assertOk()->assertSee('Your Products');
    }

    public function test_seller_product_is_attributed_and_seller_receives_gross_sale_at_checkout(): void
    {
        $seller = $this->makeSeller();
        $this->activateSubscription($seller);
        $product = $this->makeProduct($seller);
        $this->post(route('cart.add', $product), ['qty' => 2]);

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $item = OrderItem::first();
        $this->assertSame($seller->id, $item->seller_id);
        $this->assertSame(0.0, (float) $item->commission_rate);
        $this->assertSame(0.0, (float) $item->commission_amount);
        $this->assertSame(4000.0, (float) $item->seller_earning);
    }

    public function test_seller_dashboard_excludes_cancelled_orders_and_uses_net_earnings(): void
    {
        $seller = $this->makeSeller();
        $this->activateSubscription($seller);

        $liveOrder = $this->makeOrder(['status' => 'processing', 'subtotal' => 4000, 'total' => 4300]);
        $this->makeItem($seller, $liveOrder, [
            'quantity' => 2, 'price' => 2000, 'line_total' => 4000,
            'commission_rate' => 10, 'commission_amount' => 400, 'seller_earning' => 3600,
        ]);

        $cancelledOrder = $this->makeOrder(['status' => 'cancelled', 'subtotal' => 5000, 'total' => 5300]);
        $this->makeItem($seller, $cancelledOrder, [
            'quantity' => 1, 'price' => 5000, 'line_total' => 5000,
            'commission_rate' => 10, 'commission_amount' => 500, 'seller_earning' => 4500,
        ]);

        $this->actingAs($seller, 'seller')
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('PKR 4,000')       // gross sales, cancelled order excluded
            ->assertSee('PKR 3,600')       // net earnings after commission
            ->assertDontSee('PKR 9,000')   // the cancelled sale must not be counted
            ->assertDontSee('PKR 8,100');  // nor its earning

        $this->assertSame(3600.0, (float) $seller->fresh()->payout_due);
    }

    public function test_mark_paid_skips_items_from_cancelled_orders(): void
    {
        $seller = $this->makeSeller();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'mark-paid@example.com', 'password' => Hash::make('AdminPass123')]);

        $liveOrder = $this->makeOrder(['status' => 'processing']);
        $liveItem = $this->makeItem($seller, $liveOrder, ['seller_earning' => 900]);

        $cancelledOrder = $this->makeOrder(['status' => 'cancelled']);
        $cancelledItem = $this->makeItem($seller, $cancelledOrder, ['seller_earning' => 500]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.marketplace.sellers.markPaid', $seller))
            ->assertRedirect();

        $this->assertSame('paid', $liveItem->fresh()->payout_status);
        $this->assertSame('pending', $cancelledItem->fresh()->payout_status);
        $this->assertSame(0.0, (float) $seller->fresh()->payout_due);
    }

    public function test_seller_cannot_edit_another_sellers_product(): void
    {
        $owner = $this->makeSeller();
        $other = $this->makeSeller(['email' => 'other@example.com']);
        $this->activateSubscription($owner);
        $this->activateSubscription($other);
        $product = $this->makeProduct($owner);

        $this->actingAs($other, 'seller')->get(route('seller.products.edit', $product))->assertForbidden();
    }

    public function test_expired_subscription_hides_seller_product_from_shop(): void
    {
        $seller = $this->makeSeller();
        $subscription = $this->activateSubscription($seller);
        $subscription->update(['status' => 'expired', 'expires_at' => now()->subDay()]);
        $product = $this->makeProduct($seller);

        $this->get(route('shop.index'))->assertOk()->assertDontSee($product->name);
        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_seller_can_submit_a_renewal_payment_for_an_active_plan(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Yearly', 'duration_days' => 365, 'price' => 9000, 'is_active' => true]);

        $this->actingAs($seller, 'seller')->post(route('seller.subscription.store'), [
            'subscription_plan_id' => $plan->id,
            'payment_method' => 'jazzcash_easypaisa',
            'transaction_reference' => 'JC-12345',
        ])->assertRedirect(route('seller.dashboard'));

        $this->assertDatabaseHas('seller_subscriptions', [
            'seller_id' => $seller->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'transaction_reference' => 'JC-12345',
        ]);
    }

    public function test_admin_activation_starts_subscription_and_reactivates_products(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-123',
        ]);
        $product = $this->makeProduct($seller);
        $product->update(['is_active' => false]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller))
            ->assertRedirect();

        $this->assertDatabaseHas('seller_subscriptions', ['id' => $subscription->id, 'status' => 'active']);
        $this->assertTrue((bool) $product->fresh()->is_active);
        $this->assertNotNull($subscription->fresh()->expires_at);
    }

    public function test_reactivating_an_already_active_subscription_is_rejected_and_state_is_unchanged(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'active', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-123', 'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25), 'reviewed_at' => now()->subDays(5),
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin2@example.com', 'password' => Hash::make('AdminPass123')]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller));

        $response->assertRedirect(route('admin.marketplace.sellers.show', $seller));
        $response->assertSessionHas('error');
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, SellerSubscription::where('seller_id', $seller->id)->count());
    }

    public function test_rejecting_a_non_pending_subscription_is_rejected_and_state_is_unchanged(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'active', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-124', 'starts_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin3@example.com', 'password' => Hash::make('AdminPass123')]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller));

        $response->assertRedirect(route('admin.marketplace.sellers.show', $seller));
        $response->assertSessionHas('error');
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_activating_the_same_pending_payment_twice_only_applies_once(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-DOUBLE-1',
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin4@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller))
            ->assertSessionHas('success');

        // A double click / browser retry must not review the same payment again.
        $this->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller))
            ->assertSessionHas('error');

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(1, SellerSubscription::where('seller_id', $seller->id)->where('status', 'active')->count());
    }

    public function test_rejecting_the_same_pending_payment_twice_only_applies_once(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-DOUBLE-2',
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin5@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller))
            ->assertSessionHas('success');

        $reviewedAt = $subscription->fresh()->reviewed_at;

        $this->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller))
            ->assertSessionHas('error');

        $this->assertSame('rejected', $subscription->fresh()->status);
        $this->assertEquals($reviewedAt, $subscription->fresh()->reviewed_at);
    }

    public function test_a_rejected_payment_cannot_then_be_activated(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-DOUBLE-3',
        ]);
        $product = $this->makeProduct($seller);
        $product->update(['is_active' => false]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin6@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller))
            ->assertSessionHas('success');

        $this->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller))
            ->assertSessionHas('error');

        $this->assertSame('rejected', $subscription->fresh()->status);
        $this->assertFalse((bool) $product->fresh()->is_active, 'Products must stay hidden when the payment stays rejected.');
    }

    public function test_an_activated_payment_cannot_then_be_rejected(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);
        $subscription = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-DOUBLE-4',
        ]);
        $admin = Admin::create(['name' => 'Admin', 'email' => 'sub-admin7@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller))
            ->assertSessionHas('success');

        $this->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller))
            ->assertSessionHas('error');

        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_activating_two_different_pending_payments_for_one_seller_leaves_exactly_one_active(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);

        $first = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-MULTI-1',
        ]);
        $second = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-MULTI-2',
        ]);

        $service = app(SellerSubscriptionReviewService::class);

        // Approving the older payment must not silently approve the other one.
        $service->activate($seller, null, $first->id);
        $this->assertSame('active', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);

        // Approving the second payment expires the first one instead of leaving
        // two active rows behind (the race the seller-row lock prevents).
        $service->activate($seller, null, $second->id);

        $this->assertSame('expired', $first->fresh()->status);
        $this->assertSame('active', $second->fresh()->status);
        $this->assertSame(
            1,
            SellerSubscription::where('seller_id', $seller->id)->where('status', 'active')->count(),
            'A seller may never end up with two active subscriptions.'
        );
    }

    public function test_rejecting_one_pending_payment_leaves_the_other_pending_payment_reviewable(): void
    {
        $seller = $this->makeSeller();
        $plan = SubscriptionPlan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 1000, 'is_active' => true]);

        $first = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-MULTI-3',
        ]);
        $second = SellerSubscription::create([
            'seller_id' => $seller->id, 'subscription_plan_id' => $plan->id, 'price' => 1000,
            'duration_days' => 30, 'status' => 'pending', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'BANK-MULTI-4',
        ]);

        app(SellerSubscriptionReviewService::class)->reject($seller, null, $first->id);

        $this->assertSame('rejected', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame(0, SellerSubscription::where('seller_id', $seller->id)->where('status', 'active')->count());
    }

    public function test_database_failure_during_activation_is_not_disguised_as_a_status_error(): void
    {
        $seller = $this->makeSeller();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'db-fail-admin@example.com', 'password' => Hash::make('AdminPass123')]);

        // Simulate the database dying inside the review transaction. The
        // controller must not catch a QueryException (it is a RuntimeException
        // subclass) and turn it into a harmless "payment no longer pending"
        // session message: the real failure has to surface.
        $this->mock(SellerSubscriptionReviewService::class, function (MockInterface $mock) {
            $mock->shouldReceive('activate')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'select * from `seller_subscriptions` for update',
                    [],
                    new \RuntimeException('database connection lost')
                ));
        });

        $this->withoutExceptionHandling();
        $this->expectException(QueryException::class);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.activate', $seller));
    }

    public function test_database_failure_during_rejection_is_not_disguised_as_a_status_error(): void
    {
        $seller = $this->makeSeller();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'db-fail-admin-2@example.com', 'password' => Hash::make('AdminPass123')]);

        $this->mock(SellerSubscriptionReviewService::class, function (MockInterface $mock) {
            $mock->shouldReceive('reject')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'select * from `seller_subscriptions` for update',
                    [],
                    new \RuntimeException('database connection lost')
                ));
        });

        $this->withoutExceptionHandling();
        $this->expectException(QueryException::class);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.marketplace.sellers.show', $seller))
            ->post(route('admin.marketplace.sellers.subscription.reject', $seller));
    }
}
