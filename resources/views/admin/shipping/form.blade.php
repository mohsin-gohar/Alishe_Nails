@extends('layouts.admin')
@section('title', ($shipping->exists ? 'Edit' : 'Add').' Delivery Zone — Alishe Nails Admin')

@section('content')

    <div class="admin-page-head">
        <div>
            <h1>{{ $shipping->exists ? 'Edit Delivery Zone' : 'Add Delivery Zone' }}</h1>
            <p>Delivery is a flat PKR {{ number_format(\App\Models\ShippingRate::FLAT_FEE, 0) }} anywhere in Karachi. Zones below exist so the checkout can label the customer's area correctly.</p>
        </div>
        <a href="{{ route('admin.shipping.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Delivery Zones
        </a>
    </div>

    <div class="admin-card" style="max-width:600px;">
        <form action="{{ $shipping->exists ? route('admin.shipping.update', $shipping) : route('admin.shipping.store') }}" method="POST">
            @csrf
            @if ($shipping->exists)
                @method('PUT')
            @endif

            <div class="form-field" style="margin-bottom:16px;">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" value="{{ old('city', $shipping->city ?? 'Karachi') }}" placeholder="e.g. Karachi" required>
                @error('city') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field" style="margin-bottom:16px;">
                <label for="area">Area / Location *</label>
                <input type="text" id="area" name="area" value="{{ old('area', $shipping->area) }}" placeholder="e.g. DHA (Defence), Clifton, Gulshan-e-Iqbal" required>
                <small style="color:rgba(43,29,29,.75);font-size:.78rem;">This is what the customer picks at checkout; it only changes the label, never the price.</small>
                @error('area') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field" style="margin-bottom:16px;">
                <label>Delivery Fee</label>
                <input type="text" value="PKR {{ number_format(\App\Models\ShippingRate::FLAT_FEE, 0) }} (flat, not editable)" disabled
                       style="background:rgba(43,29,29,.05);cursor:not-allowed;">
                <small style="color:rgba(43,29,29,.75);font-size:.78rem;">Charged once per order. It is added to the product total automatically.</small>
            </div>

            <div class="form-field" style="margin-bottom:24px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $shipping->is_active ?? true) ? 'checked' : '' }}>
                    <span>Active (shown at checkout)</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                {{ $shipping->exists ? 'Update Zone' : 'Create Zone' }}
            </button>
        </form>
    </div>

@endsection