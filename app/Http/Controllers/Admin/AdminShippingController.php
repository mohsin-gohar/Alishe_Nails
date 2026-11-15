<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use Illuminate\Http\Request;

class AdminShippingController extends Controller
{
    public function index(Request $request)
    {
        $query = ShippingRate::query()->orderBy('city')->orderBy('area');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('city', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        $shippingRates = $query->paginate(15)->withQueryString();

        return view('admin.shipping.index', compact('shippingRates'));
    }

    public function create()
    {
        return view('admin.shipping.form', ['shipping' => new ShippingRate]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateZone($request);

        ShippingRate::create($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Delivery zone created.');
    }

    public function edit(ShippingRate $shipping)
    {
        return view('admin.shipping.form', compact('shipping'));
    }

    public function update(Request $request, ShippingRate $shipping)
    {
        $validated = $this->validateZone($request);

        $shipping->update($validated);

        return redirect()->route('admin.shipping.index')->with('success', 'Delivery zone updated.');
    }

    /**
     * Delivery is a flat PKR 300, so the zone only carries a label. The fee is
     * taken from ShippingRate::FLAT_FEE rather than the request, which means a
     * tampered form can never change what a customer pays.
     *
     * @return array<string, mixed>
     */
    private function validateZone(Request $request): array
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['delivery_fee'] = ShippingRate::FLAT_FEE;
        $validated['free_shipping_threshold'] = null;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    public function destroy(ShippingRate $shipping)
    {
        $shipping->delete();

        return back()->with('success', 'Shipping rate deleted.');
    }
}
