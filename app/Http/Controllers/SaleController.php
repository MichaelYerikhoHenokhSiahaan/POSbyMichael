<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $search = request('search');
        $categoryId = request('category_id');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $recentSales = $this->buildRecentSalesQuery($search, $categoryId, $dateFrom, $dateTo, $sort)
            ->paginate(5, ['*'], 'recent_sales_page')
            ->withQueryString();

        $productData = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'category' => $product->category?->name,
            ];
        })->values();

        $cashierName = auth()->user()?->name ?? 'Cashier';
        $canManageDiscount = auth()->user()?->isDeveloper() ?? false;

        return view('sales.index', compact('customers', 'categories', 'recentSales', 'productData', 'cashierName', 'canManageDiscount', 'search', 'categoryId', 'dateFrom', 'dateTo', 'sort'));
    }

    public function exportRecentTransactions(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $sort = $request->query('sort', 'latest');

        $sales = $this->buildRecentSalesQuery($search, $categoryId, $dateFrom, $dateTo, $sort)
            ->get();

        $categoryName = Category::query()->find($categoryId)?->name;

        $filename = 'recent-transactions-'.now()->format('Ymd_His').'.xls';

        return response()
            ->view('sales.exports.recent-transactions', compact('sales', 'search', 'categoryId', 'categoryName', 'dateFrom', 'dateTo', 'sort'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')],
            'payment_method' => ['required', 'string', 'max:50'],
            'customer_payment' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $items = collect($validated['items'])
            ->groupBy('product_id')
            ->map(fn ($group) => [
                'product_id' => (int) $group->first()['product_id'],
                'quantity' => $group->sum('quantity'),
            ])
            ->values();

        $productIds = $items->pluck('product_id');

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $subtotal = $items->sum(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                return 0;
            }

            return $product->price * $item['quantity'];
        });

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (! $product || $product->stock < $item['quantity']) {
                return redirect()
                    ->route('sales.index')
                    ->withErrors([
                        'items' => 'Satu atau lebih produk tidak memiliki stok yang cukup untuk penjualan ini.',
                    ])
                    ->withInput();
            }
        }

        $discount = $request->user()?->isDeveloper()
            ? (float) ($validated['discount'] ?? 0)
            : 0;
        $total = max($subtotal - $discount, 0);
        $customerPayment = (float) $validated['customer_payment'];

        if ($customerPayment < $total) {
            return redirect()
                ->route('sales.index')
                ->withErrors([
                    'customer_payment' => 'Pembayaran pelanggan harus lebih besar atau sama dengan total.',
                ])
                ->withInput();
        }

        $changeAmount = $customerPayment - $total;
        $cashierName = $request->user()?->name ?? 'Cashier';

        $sale = DB::transaction(function () use ($validated, $items, $products, $subtotal, $discount, $total, $customerPayment, $changeAmount, $cashierName) {
            $soldAt = now('Asia/Jakarta');
            $sale = Sale::create([
                'invoice_number' => 'INV-'.$soldAt->format('YmdHis').'-'.random_int(100, 999),
                'customer_id' => $validated['customer_id'] ?? null,
                'cashier_name' => $cashierName,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'customer_payment' => $customerPayment,
                'change_amount' => $changeAmount,
                'sold_at' => $soldAt,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $lineTotal = $product->price * $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            return $sale;
        });

        return redirect()->route('sales.show', $sale)->with('status', 'Penjualan berhasil disimpan.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'items.product']);

        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale): View
    {
        $sale->load(['customer', 'items.product']);

        return view('sales.receipt', compact('sale'));
    }

    protected function buildRecentSalesQuery(?string $search, ?string $categoryId, ?string $dateFrom, ?string $dateTo, string $sort): Builder
    {
        return Sale::query()
            ->with(['customer', 'items.product.category'])
            ->when($categoryId, function ($query, $categoryId) {
                $query->whereHas('items.product', function ($productQuery) use ($categoryId) {
                    $productQuery->where('category_id', $categoryId);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('items.product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                $query->whereDate('sold_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('sold_at', '<=', $dateTo);
            })
            ->orderBy('sold_at', $sort === 'oldest' ? 'asc' : 'desc');
    }
}
