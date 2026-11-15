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

    <div class="admin-card" style="margin-bottom:24px;">
        <h2 style="margin:0 0 4px;font-size:1.05rem;">Photos on this server</h2>
        <p style="margin:0 0 16px;color:rgba(43,29,29,.7);font-size:.85rem;">
            Look at the photo, pick the product it belongs to, press <em>Attach</em>.
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;">
            @foreach ($files as $file)
                <div style="border:1px solid rgba(43,29,29,.14);border-radius:10px;overflow:hidden;background:#fff;">
                    <img src="{{ $file['url'] }}" alt="" loading="lazy"
                         style="display:block;width:100%;height:150px;object-fit:cover;background:#f4eeee;">

                    <div style="padding:8px 10px 10px;font-size:.72rem;">
                        <div style="word-break:break-all;color:rgba(43,29,29,.75);" title="{{ $file['name'] }}">
                            {{ \Illuminate\Support\Str::limit($file['name'], 32) }}
                        </div>
                        <div style="margin-top:2px;color:rgba(43,29,29,.55);">
                            {{ number_format($file['size'] / 1024, 0) }} KB
                            @if ($file['tiny'])
                                · <strong style="color:#B3261E;">placeholder</strong>
                            @endif
                            @if ($file['used_by'] !== [])
                                · used by {{ \Illuminate\Support\Str::limit(implode(', ', $file['used_by']), 40) }}
                            @endif
                        </div>

                        @unless ($file['tiny'])
                            <form action="{{ route('admin.images.attachAny') }}" method="POST" style="display:flex;gap:6px;margin-top:8px;">
                                @csrf
                                <input type="hidden" name="file" value="{{ $file['name'] }}">
                                <select name="product" required style="flex:1;min-width:0;font-size:.72rem;">
                                    <option value="">Product…</option>
                                    @foreach ($rows as $row)
                                        <option value="{{ $row['product']->id }}">{{ \Illuminate\Support\Str::limit($row['product']->name, 28) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Attach</button>
                            </form>
                        @else
                            <div style="margin-top:8px;color:#B3261E;">Not a usable photo.</div>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
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
