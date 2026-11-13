@extends('layouts.admin')
@section('title', ($category->exists ? 'Edit' : 'Add').' Category — Alishe Nails Admin')

@section('content')
    <div class="admin-page-head">
        <div>
            <h1>{{ $category->exists ? 'Edit' : 'Add' }} Category</h1>
            <p>Categories power the “Find Your Signature Aesthetic” tiles on the homepage.</p>
        </div>
    </div>

    <div class="admin-card" style="max-width:680px;">
        <form action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div class="form-grid">
                <div class="form-field full">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field full">
                    <label for="description">Short Description <span style="font-weight:400;color:#6b5a5c;">(optional — shown under the tile)</span></label>
                    <input type="text" id="description" name="description" maxlength="500"
                           value="{{ old('description', $category->description) }}"
                           placeholder="e.g. Soft everyday sets in sheer nudes and blush tones.">
                    @error('description') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field full">
                    <label for="image">Category Tile Image <span style="font-weight:400;color:#6b5a5c;">(JPG / PNG / WebP, max 4MB)</span></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                    @error('image') <div class="error">{{ $message }}</div> @enderror

                    @if ($category->image_url)
                        <div style="display:flex;align-items:center;gap:12px;margin-top:12px;">
                            <x-optimized-image :src="$category->image_url" :alt="$category->name" class="admin-thumb admin-thumb--large" loading="lazy" />
                            <small style="color:#6b5a5c;">Current image. Upload a new one to replace it.</small>
                        </div>
                    @endif
                </div>

                <div class="form-field">
                    <label for="sort_order">Display Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                           value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    <small style="color:#6b5a5c;">Lower numbers appear first on the homepage.</small>
                    @error('sort_order') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label style="display:flex;align-items:center;gap:8px;margin-top:30px;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                        Visible on storefront
                    </label>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">{{ $category->exists ? 'Update' : 'Add' }} Category</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
@endsection
