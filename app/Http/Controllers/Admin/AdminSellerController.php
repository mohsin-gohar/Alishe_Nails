<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SubscriptionNotPendingException;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Services\SellerSubscriptionReviewService;
use Illuminate\Http\Request;

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
     * Race-safe: SellerSubscriptionReviewService locks the seller row before
     * the pending payment, so concurrent reviews for one seller are serialised
     * and can never leave two active subscriptions behind.
     */
    public function activateSubscription(
        Request $request,
        Seller $seller,
        SellerSubscriptionReviewService $subscriptions
    ) {
        try {
            $subscriptions->activate($seller, $request->user('admin')?->id);
        } catch (SubscriptionNotPendingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Subscription activated and seller products made visible.');
    }

    public function rejectSubscription(
        Request $request,
        Seller $seller,
        SellerSubscriptionReviewService $subscriptions
    ) {
        try {
            $subscriptions->reject($seller, $request->user('admin')?->id);
        } catch (SubscriptionNotPendingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Subscription payment rejected.');
    }
}
