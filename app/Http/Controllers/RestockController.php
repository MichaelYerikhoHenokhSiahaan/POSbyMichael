<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestockController extends Controller
{
    public function index(): View
    {
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');

        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $movements = WarehouseStockMovement::query()
            ->with(['product', 'user'])
            ->where('type', WarehouseStockMovement::TYPE_ADD)
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                $query->whereDate('moved_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('moved_at', '<=', $dateTo);
            })
            ->orderBy('moved_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('restock.index', [
            'products' => $products,
            'movements' => $movements,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id'),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $product->update([
                'stock' => $product->stock + $validated['quantity'],
            ]);

            WarehouseStockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => WarehouseStockMovement::TYPE_ADD,
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
                'moved_at' => now('Asia/Jakarta'),
            ]);
        });

        return redirect()
            ->route('restock.index')
            ->with('status', 'Restock produk berhasil disimpan.');
    }
}
