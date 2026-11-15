<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index()
    {
        return view('track-order.index');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'identifier' => ['required', 'string', 'max:150'],
        ]);

        $identifier = trim($validated['identifier']);
        $order = Order::where('order_number', trim($validated['order_number']))
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if (! $order) {
            return back()->withErrors([
                'order_number' => 'We could not find an order matching that order number and contact detail.',
            ]);
        }

        $order->load('items');

        if ($request->expectsJson()) {
            // Tracked orders are looked up with an order number plus an email
            // or phone number, so anonymous visitors can reach this endpoint.
            // Never serialise the model: it holds the confirmation access
            // token, the full address and the payment reference.
            return response()->json([
                'order' => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_at' => optional($order->created_at)->toIso8601String(),
                    'total' => (float) $order->total,
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'price' => (float) $item->price,
                        'line_total' => (float) $item->line_total,
                    ])->values()->all(),
                ],
            ]);
        }

        return view('track-order.index', compact('order'));
    }
}
