<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminSellerController extends Controller
{
    public function index(Request $request)
    {
        $sellers = Seller::withCount('products')
            ->when($request->status, function ($q, $status) {
                if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
                    abort(422, 'Invalid status filter.');
                }
                $q->where('status', $status);
            })
            ->latest()->paginate(20)->withQueryString();

        return view('admin.marketplace.sellers', compact('sellers'));
    }

    public function show(Seller $seller)
    {
        $seller->load('products');
        $items = $seller->orderItems()->with('order')->latest()->paginate(20);

        // Subscription verification lives on the seller's own page: the old
        // standalone Subscriptions screen was removed, so the pending payment
        // is surfaced here together with the seller it belongs to.
        $subscriptions = $seller->subscriptions()
            ->with('plan')
            ->latest()
            ->get();

        return view('admin.marketplace.seller-show', compact('seller', 'items', 'subscriptions'));
    }

    /**
     * Approve a seller's subscription payment.
     *
     * Only one subscription can be active at a time, so any existing active
     * row for the same seller is expired first. The seller's products become
     * visible in the shop immediately.
     *
     * Race-safe: the target row is re-read with a write lock and its status is
     * revalidated inside the transaction, so two concurrent clicks/retries can
     * never activate the same payment twice or act on an already-reviewed row.
     */
    public function activateSubscription(Request $request, Seller $seller)
    {
        try {
            DB::transaction(function () use ($request, $seller) {
                $subscription = SellerSubscription::where('id', function ($query) use ($seller) {
                    $query->select('id')
                        ->from('seller_subscriptions')
                        ->where('seller_id', $seller->id)
                        ->where('status', 'pending')
                        ->orderByDesc('id')
                        ->limit(1);
                })->lockForUpdate()->first();

                if (! $subscription || $subscription->seller_id !== $seller->id || $subscription->status !== 'pending') {
                    throw new RuntimeException('This subscription payment is no longer pending.');
                }

                // Lock any currently-active rows so a concurrent activation
                // cannot race us into two active subscriptions for one seller.
                SellerSubscription::where('seller_id', $seller->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                SellerSubscription::where('seller_id', $seller->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired']);

                $startsAt = now();

                $subscription->update([
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'expires_at' => $startsAt->copy()->addDays($subscription->duration_days),
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user('admin')->id,
                ]);

                $seller->products()->update(['is_active' => true]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        Cache::forget('home_collections_and_bestsellers');
        Cache::forget('shop_sidebar_facets');

        return back()->with('success', 'Subscription activated and seller products made visible.');
    }

    public function rejectSubscription(Request $request, Seller $seller)
    {
        try {
            DB::transaction(function () use ($request, $seller) {
                $subscription = SellerSubscription::where('id', function ($query) use ($seller) {
                    $query->select('id')
                        ->from('seller_subscriptions')
                        ->where('seller_id', $seller->id)
                        ->where('status', 'pending')
                        ->orderByDesc('id')
                        ->limit(1);
                })->lockForUpdate()->first();

                if (! $subscription || $subscription->seller_id !== $seller->id || $subscription->status !== 'pending') {
                    throw new RuntimeException('This subscription payment is no longer pending.');
                }

                $subscription->update([
                    'status' => 'rejected',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user('admin')->id,
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        Cache::forget('home_collections_and_bestsellers');
        Cache::forget('shop_sidebar_facets');

        return back()->with('success', 'Subscription payment rejected.');
    }
}
