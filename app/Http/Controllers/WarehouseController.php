<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    private const CATEGORY_NAME = 'Gudang';

    public function index(): View
    {
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');

        $gudangCategory = Category::query()
            ->where('name', self::CATEGORY_NAME)
            ->first();

        $products = Product::query()
            ->with('category')
            ->when($gudangCategory, function ($query) use ($gudangCategory) {
                $query->where('category_id', $gudangCategory->id);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get();

        $movements = WarehouseStockMovement::query()
            ->with(['product', 'user'])
            ->when($gudangCategory, function ($query) use ($gudangCategory) {
                $query->whereHas('product', function ($productQuery) use ($gudangCategory) {
                    $productQuery->where('category_id', $gudangCategory->id);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('type', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
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

        return view('warehouse.index', [
            'gudangCategory' => $gudangCategory,
            'products' => $products,
            'movements' => $movements,
            'movementTypes' => WarehouseStockMovement::types(),
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $gudangCategory = Category::query()
            ->where('name', self::CATEGORY_NAME)
            ->first();

        abort_unless($gudangCategory, 404);

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('category_id', $gudangCategory->id)),
            ],
            'type' => ['required', Rule::in(WarehouseStockMovement::types())],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $gudangCategory) {
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->where('category_id', $gudangCategory->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['type'] === WarehouseStockMovement::TYPE_REMOVE && $product->stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock gudang tidak cukup untuk pengurangan ini.',
                ]);
            }

            $product->update([
                'stock' => $validated['type'] === WarehouseStockMovement::TYPE_ADD
                    ? $product->stock + $validated['quantity']
                    : $product->stock - $validated['quantity'],
            ]);

            WarehouseStockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
                'moved_at' => now('Asia/Jakarta'),
            ]);
        });

        return redirect()
            ->route('warehouse.index')
            ->with('status', 'Perubahan stok gudang berhasil disimpan.');
    }
}
