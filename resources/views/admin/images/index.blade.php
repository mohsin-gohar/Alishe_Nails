@extends('layouts.admin')
@section('title', 'Image Doctor — Alishe Nails Admin')

@section('content')

    <div class="admin-page-head">
        <div>
            <h1>Image Doctor</h1>
            <p>Re-attach product photos that are already on the server to listings whose image record is missing.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Back to Products</a>
    </div>

    <div class="admin-card" style="margin-bottom:24px;">
        <p style="margin:0 0 6px;">
            <strong>{{ $rows->count() }}</strong> products ·
            <strong style="color:{{ $missing > 0 ? '#B3261E' : '#14713c' }};">{{ $missing }}</strong> without a working image ·
            <strong>{{ $files->count() }}</strong> image files found on the server.
        </p>
        <p style="margin:0;color:rgba(43,29,29,.7);font-size:.85rem;">
            Pick the photo that belongs to each product and press <em>Attach</em>. Nothing is renamed or deleted.
            Files marked <em>placeholder</em> are 1×1 pixels and should not be used.
        </p>
    </div>

    <div class="admin-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Current image</th>
                    <th>Stored reference</th>
                    <th>Attach an existing photo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['product']->name }}</strong><br>
                            <small style="color:rgba(43,29,29,.6);">{{ $row['product']->slug }}</small>
                        </td>
                        <td>
                            @if ($row['image'])
                                <img src="{{ $row['image'] }}" alt="" width="56" height="56" style="object-fit:cover;border-radius:6px;border:1px solid rgba(43,29,29,.15);">
                            @else
                                <span class="status-pill status-cancelled">Missing</span>
                            @endif
                        </td>
                        <td style="max-width:240px;word-break:break-all;font-size:.8rem;color:rgba(43,29,29,.7);">
                            @forelse ($row['stored'] as $reference)
                                <div>{{ $reference }}</div>
                            @empty
                                <em>none</em>
                            @endforelse
                        </td>
                        <td>
                            <form action="{{ route('admin.images.attach', $row['product']) }}" method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
                                @csrf
                                <select name="file" required style="min-width:220px;max-width:320px;">
                                    <option value="">Select a file…</option>
                                    @foreach ($files as $file)
                                        @php
                                            $labels = [number_format($file['size'] / 1024, 0).' KB'];
                                            if ($file['tiny']) {
                                                $labels[] = 'placeholder';
                                            }
                                            if ($file['used_by'] !== []) {
                                                $labels[] = 'used by: '.implode(', ', $file['used_by']);
                                            }
                                        @endphp
                                        <option value="{{ $file['name'] }}">
                                            {{ $file['name'] }} ({{ implode(' · ', $labels) }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Attach</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
