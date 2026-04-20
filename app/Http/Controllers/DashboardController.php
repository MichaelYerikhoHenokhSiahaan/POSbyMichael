<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('sales')) {
            return view('dashboard', [
                'metrics' => [
                    'products' => 0,
                    'inStock' => 0,
                    'revenue' => 0,
                    'todaySales' => 0,
                ],
                'recentSales' => Sale::query()->whereRaw('1 = 0')->paginate(5, ['*'], 'recent_sales_page'),
                'lowStockProducts' => Product::query()->whereRaw('1 = 0')->paginate(5, ['*'], 'low_stock_page'),
            ]);
        }

        $todaySales = Sale::query()
            ->whereDate('sold_at', now()->toDateString())
            ->sum('total');

        $metrics = [
            'products' => Product::count(),
            'inStock' => Product::sum('stock'),
            'revenue' => Sale::sum('total'),
            'todaySales' => $todaySales,
        ];

        $recentSales = Sale::query()
            ->with(['customer', 'items.product'])
            ->latest('sold_at')
            ->paginate(5, ['*'], 'recent_sales_page')
            ->withQueryString();

        $lowStockProducts = Product::query()
            ->orderBy('stock')
            ->paginate(5, ['*'], 'low_stock_page')
            ->withQueryString();

        return view('dashboard', compact('metrics', 'recentSales', 'lowStockProducts'));
    }
}
