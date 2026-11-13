@extends('layouts.admin')
@section('title', 'Categories — Alishe Nails Admin')

@section('content')

    <div class="admin-page-head">
        <div>
            <h1>Categories</h1>
            <p>{{ $categories->total() }} categories · control homepage tiles, images and display order</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Category
        </a>
    </div>

    <div class="admin-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            @if ($category->image_url)
                                <x-optimized-image :src="$category->image_url" :alt="$category->name" class="admin-thumb admin-thumb--wide" loading="lazy" />
                            @else
                                <div style="width:56px;height:42px;border-radius:6px;background:var(--blush);"></div>
                            @endif
                        </td>
                        <td>{{ $category->name }}</td>
                        <td style="max-width:280px;">{{ \Illuminate\Support\Str::limit($category->description, 70) ?: '—' }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <span class="status-pill {{ $category->is_active ? 'status-delivered' : 'status-cancelled' }}">
                                {{ $category->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </td>
                        <td style="display:flex;gap:8px;">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ $category->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="background:#B3261E;color:#fff;border:none;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:30px;">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination" style="justify-content:flex-start;margin-top:20px;">
            {{ $categories->links() }}
        </div>
    </div>
@endsection
