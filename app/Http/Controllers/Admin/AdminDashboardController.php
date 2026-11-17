<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VisitorLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminDashboardController extends Controller
{
    /**
     * Low-stock threshold. Products at or below this count show up in the
     * "Low Stock Alerts" card and the Tasks & Alerts list.
     */
    private const LOW_STOCK_THRESHOLD = 5;

    public function index()
    {
        $totalOrders = Order::count();
        $totalSales = Order::where('status', '!=', 'cancelled')->sum('total');
        $activeCustomers = Order::distinct('email')->count('email');
        $lowStockProducts = Product::where('stock', '<=', self::LOW_STOCK_THRESHOLD)->get();

        $recentOrders = Order::latest()->take(4)->get();

        // "Top Performing" = products with the most units sold across all orders.
        $topPerforming = OrderItem::selectRaw('product_name, SUM(quantity) as units_sold')
            ->whereHas('order', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->groupBy('product_name')
            ->orderByDesc('units_sold')
            ->take(3)
            ->get();

        $totalUnitsSold = OrderItem::whereHas('order', fn ($query) => $query->where('status', '!=', 'cancelled'))->sum('quantity');

        $lowStockCount = $lowStockProducts->count();

        // Visitor / traffic intelligence (graceful if table not migrated yet).
        $traffic = [
            'enabled' => false,
            'today' => 0,
            'uniqueToday' => 0,
            'last7Days' => 0,
            'unique7Days' => 0,
            'suspicious7Days' => 0,
            'recent' => collect(),
            'topPages' => collect(),
        ];

        try {
            if (Schema::hasTable('visitor_logs')) {
                $today = Carbon::today();
                $since7 = Carbon::now()->subDays(7);

                $traffic['enabled'] = true;
                $traffic['today'] = VisitorLog::where('visited_at', '>=', $today)->count();
                $traffic['uniqueToday'] = VisitorLog::where('visited_at', '>=', $today)->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address');
                $traffic['last7Days'] = VisitorLog::where('visited_at', '>=', $since7)->count();
                $traffic['unique7Days'] = VisitorLog::where('visited_at', '>=', $since7)->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address');
                $traffic['suspicious7Days'] = VisitorLog::where('visited_at', '>=', $since7)->where('is_suspicious', true)->count();
                $traffic['recent'] = VisitorLog::orderByDesc('visited_at')->limit(12)->get();
                $traffic['topPages'] = VisitorLog::selectRaw('path, COUNT(*) as hits')
                    ->where('visited_at', '>=', $since7)
                    ->groupBy('path')
                    ->orderByDesc('hits')
                    ->limit(5)
                    ->get();
            }
        } catch (Throwable $e) {
            report($e);
        }

        return view('admin.dashboard.index', compact(
            'totalOrders',
            'totalSales',
            'activeCustomers',
            'lowStockCount',
            'lowStockProducts',
            'recentOrders',
            'topPerforming',
            'totalUnitsSold',
            'traffic'
        ));
    }
}
