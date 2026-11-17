@extends('layouts.admin')
@section('title', 'Dashboard Overview — Alishe Nails Admin')

@section('content')

    <div class="admin-page-head">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back to the command center.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-outline">
                <i class="fa-solid fa-house-chimney"></i> Homepage Studio
            </a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> New Product
            </a>
        </div>
    </div>

    {{-- ---------- Stat cards ---------- --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-card__head">
                <span class="stat-card__label">Total Orders</span>
                <span class="stat-card__icon" style="background:var(--blush);color:var(--rose-dark);"><i class="fa-solid fa-cart-shopping"></i></span>
            </div>
            <div class="stat-card__value">{{ number_format($totalOrders) }}</div>
            <div class="stat-card__trend up"><i class="fa-solid fa-arrow-trend-up"></i> All-time orders</div>
        </div>

        <div class="stat-card">
            <div class="stat-card__head">
                <span class="stat-card__label">Total Sales</span>
                <span class="stat-card__icon" style="background:#F6D6DC;color:var(--rose-dark);"><i class="fa-solid fa-sack-dollar"></i></span>
            </div>
            <div class="stat-card__value">PKR {{ number_format($totalSales, 0) }}</div>
            <div class="stat-card__trend up"><i class="fa-solid fa-arrow-trend-up"></i> All-time revenue</div>
        </div>

        <div class="stat-card">
            <div class="stat-card__head">
                <span class="stat-card__label">Active Customers</span>
                <span class="stat-card__icon" style="background:#FBEBB5;color:#8A6D00;"><i class="fa-solid fa-users"></i></span>
            </div>
            <div class="stat-card__value">{{ number_format($activeCustomers) }}</div>
            <div class="stat-card__trend up"><i class="fa-solid fa-arrow-trend-up"></i> Unique buyers</div>
        </div>

        <div class="stat-card">
            <div class="stat-card__head">
                <span class="stat-card__label">Low Stock Alerts</span>
                <span class="stat-card__icon" style="background:#F7D6D6;color:#a12a2a;"><i class="fa-solid fa-triangle-exclamation"></i></span>
            </div>
            <div class="stat-card__value">{{ number_format($lowStockCount) }}</div>
            <div class="stat-card__trend warn">
                @if ($lowStockCount > 0)
                    <i class="fa-solid fa-circle-exclamation"></i> Requires attention
                @else
                    <i class="fa-solid fa-circle-check"></i> All stocked up
                @endif
            </div>
        </div>
    </div>

    {{-- ---------- Website traffic & visitor security ---------- --}}
    <div class="dashboard-grid" style="margin-top:20px;">
        <div class="admin-card">
            <div class="admin-card__head">
                <h2><i class="fa-solid fa-chart-simple" style="color:var(--rose-dark);"></i> Website Traffic Overview</h2>
                @if(($traffic['enabled'] ?? false))
                    <span style="font-size:.8rem;color:rgba(43,29,29,.7);">Live visitor logging active</span>
                @endif
            </div>

            @if(!($traffic['enabled'] ?? false))
                <div style="background:var(--ivory);border:1px solid var(--border-soft);border-radius:8px;padding:16px;">
                    <strong style="display:block;margin-bottom:6px;">Visitor logging not active yet</strong>
                    <p style="margin:0;font-size:.85rem;color:rgba(43,29,29,.75);">
                        Run the pending database migration (<code>visitor_logs</code>) on the server to start recording traffic, IPs, locations, devices, and suspicious activity for security monitoring.
                    </p>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
                    <div style="border:1px solid var(--border-soft);border-radius:10px;padding:14px;background:#fff;">
                        <div class="stat-card__label">Visits Today</div>
                        <div class="stat-card__value" style="font-size:1.35rem;">{{ number_format($traffic['today']) }}</div>
                        <div style="font-size:.78rem;color:rgba(43,29,29,.7);">All page requests logged</div>
                    </div>
                    <div style="border:1px solid var(--border-soft);border-radius:10px;padding:14px;background:#fff;">
                        <div class="stat-card__label">Unique Visitors Today</div>
                        <div class="stat-card__value" style="font-size:1.35rem;">{{ number_format($traffic['uniqueToday']) }}</div>
                        <div style="font-size:.78rem;color:rgba(43,29,29,.7);">By IP address</div>
                    </div>
                    <div style="border:1px solid var(--border-soft);border-radius:10px;padding:14px;background:#fff;">
                        <div class="stat-card__label">Last 7 Days</div>
                        <div class="stat-card__value" style="font-size:1.35rem;">{{ number_format($traffic['last7Days']) }}</div>
                        <div style="font-size:.78rem;color:rgba(43,29,29,.7);">{{ number_format($traffic['unique7Days']) }} unique IPs</div>
                    </div>
                    <div style="border:1px solid var(--border-soft);border-radius:10px;padding:14px;background:{{ ($traffic['suspicious7Days'] ?? 0) > 0 ? '#FFF5F5' : '#fff' }};">
                        <div class="stat-card__label">Suspicious (7 days)</div>
                        <div class="stat-card__value" style="font-size:1.35rem;color:{{ ($traffic['suspicious7Days'] ?? 0) > 0 ? '#b3261e' : 'inherit' }};">{{ number_format($traffic['suspicious7Days']) }}</div>
                        <div style="font-size:.78rem;color:rgba(43,29,29,.7);">Probing / sensitive paths / flagged</div>
                    </div>
                </div>

                @if(($traffic['topPages'] ?? collect())->isNotEmpty())
                    <div style="margin-bottom:4px;">
                        <div class="stat-card__label" style="margin-bottom:8px;">Top Pages (7 days)</div>
                        @foreach($traffic['topPages'] as $p)
                            <div class="legend-item">
                                <span style="font-size:.82rem;word-break:break-all;">{{ \Illuminate\Support\Str::limit($p->path, 60) }}</span>
                                <span>{{ number_format($p->hits) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="admin-card">
            <div class="admin-card__head">
                <h2><i class="fa-solid fa-shield-halved" style="color:var(--rose-dark);"></i> Recent Visitor Activity</h2>
            </div>

            @if(!($traffic['enabled'] ?? false))
                <p style="font-size:.85rem;color:rgba(43,29,29,.75);margin:0;">Security and visitor details will appear here after migration is applied.</p>
            @elseif(($traffic['recent'] ?? collect())->isEmpty())
                <p style="font-size:.85rem;color:rgba(43,29,29,.75);margin:0;">No visitor activity recorded yet. New visits will appear here automatically.</p>
            @else
                <div style="overflow:auto;">
                    <table class="data-table" style="font-size:.8rem;">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>IP / Location</th>
                                <th>Page</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($traffic['recent'] as $log)
                                @php
                                    $loc = collect([$log->city, $log->region, $log->country])->filter()->implode(', ');
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap;">{{ optional($log->visited_at)->format('M j, H:i') ?? optional($log->created_at)->format('M j, H:i') }}</td>
                                    <td>
                                        <div style="font-weight:600;">{{ $log->ip_address ?? '—' }}</div>
                                        <div style="color:rgba(43,29,29,.7);font-size:.75rem;">{{ $loc !== '' ? $loc : 'Location unavailable' }}</div>
                                    </td>
                                    <td style="max-width:220px;word-break:break-all;">
                                        <div title="{{ $log->full_url }}">{{ \Illuminate\Support\Str::limit($log->path ?? '/', 42) }}</div>
                                        <div style="color:rgba(43,29,29,.65);font-size:.72rem;">{{ $log->method }} @if($log->route_name) · {{ $log->route_name }} @endif</div>
                                    </td>
                                    <td>
                                        <span class="status-pill" style="background:{{ ($log->status_code ?? 0) >= 400 ? '#F7D6D6' : '#E8F5E9' }};color:{{ ($log->status_code ?? 0) >= 400 ? '#a12a2a' : '#2e7d32' }};">{{ $log->status_code ?? '—' }}</span>
                                        @if($log->is_suspicious)
                                            <span class="status-pill" style="background:#FFF3CD;color:#8a6d00;margin-left:4px;">Suspicious</span>
                                        @endif
                                        @if($log->is_bot)
                                            <span class="status-pill" style="background:#ECEFF1;color:#455a64;margin-left:4px;">Bot</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="text-transform:capitalize;">{{ $log->user_type ?? 'guest' }}</div>
                                        <div style="color:rgba(43,29,29,.65);font-size:.72rem;">{{ $log->user_id ? 'ID #'.$log->user_id : '—' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $log->browser ?? '—' }} @if($log->platform) · {{ $log->platform }} @endif</div>
                                        <div style="color:rgba(43,29,29,.65);font-size:.72rem;text-transform:capitalize;">{{ $log->device_type ?? 'unknown' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="margin:10px 0 0;font-size:.75rem;color:rgba(43,29,29,.65);">
                    IP, page, referrer, user-agent, device/browser and location (when available from headers/network) are stored for traffic analytics and security auditing.
                </p>
            @endif
        </div>
    </div>

    {{-- ---------- Recent orders + Top performing ---------- --}}
    <div class="dashboard-grid">
        <div class="admin-card">
            <div class="admin-card__head">
                <h2>Recent Orders</h2>
                <a href="{{ route('admin.orders.index') }}">View All &rarr;</a>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a>
                            </td>
                            <td>
                                <span class="table-avatar">{{ strtoupper(substr($order->first_name, 0, 1).substr($order->last_name, 0, 1)) }}</span>
                                {{ $order->first_name }} {{ $order->last_name }}
                            </td>
                            <td>{{ $order->created_at->format('M j, Y') }}</td>
                            <td>PKR {{ number_format($order->total, 0) }}</td>
                            <td><span class="status-pill status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:24px;">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <div class="admin-card__head">
                <h2>Top Performing</h2>
            </div>

            <div class="top-performing__total">
                <div class="value">{{ $totalUnitsSold > 999 ? round($totalUnitsSold / 1000, 1).'k' : $totalUnitsSold }}</div>
                <div class="label">Units Sold</div>
            </div>

            @php
                $dotColors = ['var(--espresso)', 'var(--rose)', 'var(--blush)'];
            @endphp

            @forelse ($topPerforming as $i => $product)
                <div class="legend-item">
                    <span><span class="legend-dot" style="background:{{ $dotColors[$i] ?? 'var(--blush)' }};"></span>{{ $product->product_name }}</span>
                    <span>{{ $totalUnitsSold > 0 ? round(($product->units_sold / $totalUnitsSold) * 100) : 0 }}%</span>
                </div>
            @empty
                <p style="font-size:.85rem;color:rgba(43,29,29,.75);">No sales data yet.</p>
            @endforelse
        </div>
    </div>

    {{-- ---------- Campaign + Tasks ---------- --}}
    <div class="dashboard-grid">
        <div class="campaign-banner">
            @if (file_exists(public_path('images/brand/about-hands.jpg')))
                <x-optimized-image src="images/brand/about-hands.jpg" alt="Spring Collection" loading="lazy" />
            @endif
            <div class="campaign-banner__content">
                <span class="campaign-banner__tag">Campaign</span>
                <h2>Spring Collection Setup</h2>
                <p>Prepare inventory and marketing materials for the upcoming seasonal drop.</p>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card__head">
                <h2>Tasks &amp; Alerts</h2>
            </div>

            @if ($lowStockCount > 0)
                <div class="task-item">
                    <div class="task-item__icon"><i class="fa-solid fa-box-open"></i></div>
                    <div class="task-item__body">
                        <strong>Restock Required</strong>
                        <small>
                            {{ $lowStockProducts->pluck('name')->take(1)->first() }} set is below threshold
                            ({{ $lowStockProducts->first()->stock }} left).
                        </small>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="task-item__action">View</a>
                </div>
            @endif

            @php
                $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
            @endphp
            @if ($pendingOrders > 0)
                <div class="task-item">
                    <div class="task-item__icon"><i class="fa-solid fa-truck"></i></div>
                    <div class="task-item__body">
                        <strong>Pending Orders</strong>
                        <small>{{ $pendingOrders }} order(s) awaiting processing.</small>
                    </div>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="task-item__action">View</a>
                </div>
            @endif

            @php
                $homepageCount = \App\Models\Product::where('is_homepage', true)->count();
            @endphp
            <div class="task-item">
                <div class="task-item__icon"><i class="fa-solid fa-house-chimney"></i></div>
                <div class="task-item__body">
                    <strong>Homepage Lineup</strong>
                    <small>
                        @if ($homepageCount > 0)
                            {{ $homepageCount }} product(s) hand-picked for “The Home Edit”.
                        @else
                            No products curated yet — automatic merchandising is live.
                        @endif
                    </small>
                </div>
                <a href="{{ route('admin.homepage.index') }}" class="task-item__action">Manage</a>
            </div>

            @if ($lowStockCount == 0 && $pendingOrders == 0 && $homepageCount > 0)
                <p style="font-size:.85rem;color:rgba(43,29,29,.75);">All caught up — no pending tasks.</p>
            @endif
        </div>
    </div>

@endsection
