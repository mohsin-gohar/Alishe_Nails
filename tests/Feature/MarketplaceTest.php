<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
}
