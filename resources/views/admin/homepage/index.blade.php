@extends('layouts.admin')
@section('title', 'Homepage Studio — Alishe Nails Admin')

@section('content')

    <div class="admin-page-head">
        <div>
            <h1>Homepage Studio</h1>
            <p>Choose exactly which products appear in “The Home Edit” and in what order.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-outline">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Preview Homepage
        </a>
    </div>

    <form id="homepage-form" action="{{ route('admin.homepage.update') }}" method="POST"
          data-max="{{ $maxItems }}">
        @csrf

        <div class="hp-studio">
            {{-- ---------- Product picker ---------- --}}
            <div class="hp-panel">
                <div class="hp-panel__head">
                    <h2><i class="fa-solid fa-box-open"></i> Product Library</h2>
                    <span class="hp-counter">{{ $products->count() }} products</span>
                </div>

                <input type="search" id="hp-search" class="hp-search" placeholder="Search by product or category…" autocomplete="off" aria-label="Search products to add to the homepage">

                <div class="hp-product-list" id="hp-product-list">
                    @forelse ($products as $product)
                        <div class="hp-product {{ $selected->contains('id', $product->id) ? 'is-selected' : '' }}"
                             data-hp-item
                             data-id="{{ $product->id }}"
                             data-name="{{ $product->name }}"
                             data-category="{{ $product->category?->name ?? 'Uncategorised' }}"
                             data-price="PKR {{ number_format((float) $product->price, 0) }}"
                             data-image="{{ $product->cover_image_url }}">
                            @if ($product->cover_image_url)
                                <x-optimized-image :src="$product->cover_image_url" :alt="$product->name" class="hp-product__thumb" loading="lazy" />
                            @else
                                <span class="hp-product__thumb"><i class="fa-solid fa-image"></i></span>
                            @endif

                            <div class="hp-product__meta">
                                <strong>{{ $product->name }}</strong>
                                <small>
                                    {{ $product->category?->name ?? 'Uncategorised' }}
                                    &middot; PKR {{ number_format((float) $product->price, 0) }}
                                    @if (! $product->is_active)
                                        &middot; <span style="color:#B3261E;font-weight:600;">Inactive</span>
                                    @endif
                                </small>
                            </div>

                            <button type="button" class="btn btn-outline btn-sm" data-add>
                                {{ $selected->contains('id', $product->id) ? 'Selected' : 'Add' }}
                            </button>
                        </div>
                    @empty
                        <div class="hp-empty">No products yet. <a href="{{ route('admin.products.create') }}">Add your first product</a>.</div>
                    @endforelse
                </div>
            </div>

            {{-- ---------- Selected lineup ---------- --}}
            <div class="hp-panel">
                <div class="hp-panel__head">
                    <h2><i class="fa-solid fa-house-chimney"></i> Homepage Lineup</h2>
                    <span class="hp-counter" id="hp-counter">{{ $selected->count() }} / {{ $maxItems }} selected</span>
                </div>

                <div class="hp-selected-list" id="hp-selected-list">
                    @foreach ($selected as $product)
                        <div class="hp-selected" data-hp-selected data-id="{{ $product->id }}">
                            <span class="hp-selected__order">{{ $loop->iteration }}</span>
                            @if ($product->cover_image_url)
                                <x-optimized-image :src="$product->cover_image_url" alt="" class="hp-selected__thumb" loading="lazy" />
                            @else
                                <span class="hp-selected__thumb" style="display:inline-flex;align-items:center;justify-content:center;color:var(--rose-dark);"><i class="fa-solid fa-image"></i></span>
                            @endif
                            <div class="hp-selected__meta">
                                <strong>{{ $product->name }}</strong>
                                <small>{{ $product->category?->name ?? 'Uncategorised' }} &middot; PKR {{ number_format((float) $product->price, 0) }}</small>
                            </div>
                            <div class="hp-selected__actions">
                                <button type="button" data-move="up" aria-label="Move {{ $product->name }} up"><i class="fa-solid fa-arrow-up"></i></button>
                                <button type="button" data-move="down" aria-label="Move {{ $product->name }} down"><i class="fa-solid fa-arrow-down"></i></button>
                                <button type="button" data-remove aria-label="Remove {{ $product->name }} from homepage"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <input type="hidden" name="products[]" value="{{ $product->id }}">
                        </div>
                    @endforeach
                </div>

                <div class="hp-empty" id="hp-empty" @if ($selected->isNotEmpty()) hidden @endif>
                    <i class="fa-solid fa-wand-magic-sparkles" style="font-size:1.4rem;display:block;margin-bottom:8px;"></i>
                    Nothing selected yet — add products from the library on the left.
                </div>

                <div class="hp-note">
                    <strong>How it works:</strong> the homepage shows this lineup in the exact order saved here.
                    Leave it empty and the storefront automatically falls back to best sellers, featured pieces and newest arrivals.
                </div>

                <div class="hp-savebar">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Homepage Lineup
                    </button>
                    <button type="button" class="btn btn-outline" id="hp-clear">Clear All</button>
                    <span class="hp-savebar__hint">Changes go live immediately after saving.</span>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var form = document.getElementById('homepage-form');
            if (!form) return;

            var max = parseInt(form.dataset.max, 10) || 20;
            var list = document.getElementById('hp-selected-list');
            var emptyState = document.getElementById('hp-empty');
            var counter = document.getElementById('hp-counter');
            var search = document.getElementById('hp-search');
            var items = Array.prototype.slice.call(document.querySelectorAll('[data-hp-item]'));

            function rows() {
                return Array.prototype.slice.call(list.querySelectorAll('[data-hp-selected]'));
            }

            function refresh() {
                var current = rows();
                var ids = current.map(function (row) { return row.dataset.id; });

                current.forEach(function (row, index) {
                    row.querySelector('.hp-selected__order').textContent = index + 1;
                    row.querySelector('[data-move="up"]').disabled = index === 0;
                    row.querySelector('[data-move="down"]').disabled = index === current.length - 1;
                });

                items.forEach(function (item) {
                    var isSelected = ids.indexOf(item.dataset.id) !== -1;
                    item.classList.toggle('is-selected', isSelected);

                    var button = item.querySelector('[data-add]');
                    if (!button) return;

                    button.disabled = isSelected || current.length >= max;
                    button.textContent = isSelected ? 'Selected' : (current.length >= max ? 'Limit reached' : 'Add');
                });

                counter.textContent = current.length + ' / ' + max + ' selected';
                emptyState.hidden = current.length > 0;
            }

            function buildRow(item) {
                var row = document.createElement('div');
                row.className = 'hp-selected';
                row.setAttribute('data-hp-selected', '');
                row.dataset.id = item.dataset.id;

                var order = document.createElement('span');
                order.className = 'hp-selected__order';
                order.textContent = '1';
                row.appendChild(order);

                if (item.dataset.image) {
                    var img = document.createElement('img');
                    img.src = item.dataset.image;
                    img.alt = '';
                    img.className = 'hp-selected__thumb';
                    row.appendChild(img);
                } else {
                    var thumb = document.createElement('span');
                    thumb.className = 'hp-selected__thumb';
                    thumb.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;color:var(--rose-dark);';
                    thumb.innerHTML = '<i class="fa-solid fa-image"></i>';
                    row.appendChild(thumb);
                }

                var meta = document.createElement('div');
                meta.className = 'hp-selected__meta';

                var name = document.createElement('strong');
                name.textContent = item.dataset.name;
                meta.appendChild(name);

                var details = document.createElement('small');
                details.textContent = item.dataset.category + ' · ' + item.dataset.price;
                meta.appendChild(details);

                row.appendChild(meta);

                var actions = document.createElement('div');
                actions.className = 'hp-selected__actions';
                actions.innerHTML =
                    '<button type="button" data-move="up" aria-label="Move up"><i class="fa-solid fa-arrow-up"></i></button>' +
                    '<button type="button" data-move="down" aria-label="Move down"><i class="fa-solid fa-arrow-down"></i></button>' +
                    '<button type="button" data-remove aria-label="Remove from homepage"><i class="fa-solid fa-xmark"></i></button>';
                row.appendChild(actions);

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'products[]';
                input.value = item.dataset.id;
                row.appendChild(input);

                return row;
            }

            items.forEach(function (item) {
                var button = item.querySelector('[data-add]');
                if (!button) return;

                button.addEventListener('click', function () {
                    if (rows().length >= max) return;

                    var exists = rows().some(function (row) { return row.dataset.id === item.dataset.id; });
                    if (exists) return;

                    list.appendChild(buildRow(item));
                    refresh();
                });
            });

            list.addEventListener('click', function (event) {
                var button = event.target.closest('button');
                if (!button) return;

                var row = button.closest('[data-hp-selected]');
                if (!row) return;

                if (button.hasAttribute('data-remove')) {
                    row.remove();
                    refresh();
                    return;
                }

                if (button.dataset.move === 'up' && row.previousElementSibling) {
                    list.insertBefore(row, row.previousElementSibling);
                    refresh();
                }

                if (button.dataset.move === 'down' && row.nextElementSibling) {
                    list.insertBefore(row.nextElementSibling, row);
                    refresh();
                }
            });

            if (search) {
                search.addEventListener('input', function () {
                    var term = search.value.trim().toLowerCase();

                    items.forEach(function (item) {
                        var haystack = (item.dataset.name + ' ' + item.dataset.category).toLowerCase();
                        item.hidden = term !== '' && haystack.indexOf(term) === -1;
                    });
                });
            }

            var clear = document.getElementById('hp-clear');
            if (clear) {
                clear.addEventListener('click', function () {
                    if (!rows().length) return;
                    if (!confirm('Remove all products from the homepage lineup?')) return;

                    rows().forEach(function (row) { row.remove(); });
                    refresh();
                });
            }

            refresh();
        })();
    </script>
@endsection
