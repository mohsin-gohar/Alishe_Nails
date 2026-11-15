<?php

namespace App\Services;

use App\Exceptions\SubscriptionNotPendingException;
use App\Models\Seller;
use App\Models\SellerSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reviews seller subscription payments.
 *
 * Every activation/rejection first takes a write lock on the seller's own row,
 * then locks the pending payment. Two admins therefore can never review two
 * different payments for the same seller at the same time: the second request
 * waits for the first transaction to commit and only then re-reads the state.
 * This serialises the whole "expire the old active row, activate the new one"
 * sequence, not just the single row that happened to be selected.
 */
class SellerSubscriptionReviewService
{
    /**
     * @param  int|null  $subscriptionId  Optional explicit payment id. The admin
     *                                    screen reviews the newest pending row,
     *                                    but the concurrency harness needs to
     *                                    race two different rows on purpose.
     */
    public function activate(Seller $seller, ?int $adminId, ?int $subscriptionId = null): SellerSubscription
    {
        $subscription = DB::transaction(function () use ($seller, $adminId, $subscriptionId) {
            $lockedSeller = $this->lockSeller($seller);

            $subscription = $this->lockPendingSubscription($lockedSeller, $subscriptionId);

            // Re-read and lock the currently-active rows as well. Without the
            // seller lock above, two transactions could each see no active row
            // and both commit an active one afterwards.
            SellerSubscription::where('seller_id', $lockedSeller->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            SellerSubscription::where('seller_id', $lockedSeller->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $startsAt = now();

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addDays((int) $subscription->duration_days),
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
            ]);

            $lockedSeller->products()->update(['is_active' => true]);

            return $subscription;
        });

        $this->forgetCaches();

        return $subscription;
    }

    public function reject(Seller $seller, ?int $adminId, ?int $subscriptionId = null): SellerSubscription
    {
        $subscription = DB::transaction(function () use ($seller, $adminId, $subscriptionId) {
            $lockedSeller = $this->lockSeller($seller);

            $subscription = $this->lockPendingSubscription($lockedSeller, $subscriptionId);

            $subscription->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
            ]);

            return $subscription;
        });

        $this->forgetCaches();

        return $subscription;
    }

    private function lockSeller(Seller $seller): Seller
    {
        $locked = Seller::whereKey($seller->getKey())->lockForUpdate()->first();

        if (! $locked) {
            throw new SubscriptionNotPendingException('This seller no longer exists.');
        }

        return $locked;
    }

    /**
     * The direct SELECT ... FOR UPDATE is deliberate: on MySQL/MariaDB the
     * lock is taken on the row as it is read, so a second request that was
     * waiting on the seller row sees the committed status and fails the
     * revalidation below instead of reviewing the payment twice.
     */
    private function lockPendingSubscription(Seller $seller, ?int $subscriptionId): SellerSubscription
    {
        $query = SellerSubscription::where('seller_id', $seller->getKey())
            ->where('status', 'pending');

        if ($subscriptionId !== null) {
            $query->whereKey($subscriptionId);
        } else {
            $query->orderByDesc('id');
        }

        $subscription = $query->lockForUpdate()->first();

        if (
            ! $subscription
            || (int) $subscription->seller_id !== (int) $seller->getKey()
            || $subscription->status !== 'pending'
        ) {
            throw new SubscriptionNotPendingException('This subscription payment is no longer pending.');
        }

        return $subscription;
    }

    private function forgetCaches(): void
    {
        Cache::forget('home_collections_and_bestsellers');
        Cache::forget('shop_sidebar_facets');
    }
}
