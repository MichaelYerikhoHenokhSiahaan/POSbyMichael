<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GasTransaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GasController extends Controller
{
    private const CATEGORY_NAME = 'Gas';

    private const PRODUCT_ISI_GAS = 'Isi Gas';

    private const PRODUCT_GAS_KOSONG = 'Gas Kosong';

    private const PRODUCT_GAS_PLUS_ISI = 'Gas + Isi';

    public function index(): View
    {
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');

        $gasCategory = $this->ensureGasCatalogExists();
        $products = $this->getGasProducts($gasCategory);

        $transactions = $this->buildGasTransactionQuery($search, $dateFrom, $dateTo, $sort)
            ->paginate(5, ['*'], 'gas_transactions_page')
            ->withQueryString();

        $productData = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $this->defaultPriceForProduct($product),
                'stock' => $product->stock,
                'unit' => $product->unit,
            ];
        })->values();

        return view('gas.index', compact(
            'products',
            'productData',
            'transactions',
            'search',
            'dateFrom',
            'dateTo',
            'sort',
        ));
    }

    public function storeInput(Request $request): RedirectResponse
    {
        $gasCategory = $this->ensureGasCatalogExists();

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('category_id', $gasCategory->id)),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $gasCategory) {
            $products = $this->getGasProducts($gasCategory, true)->keyBy('name');
            $product = $products->firstWhere('id', (int) $validated['product_id']);
            $quantity = (int) $validated['quantity'];
            $stockEffect = $product->name.' +'.$quantity;

            if ($product->name === self::PRODUCT_ISI_GAS || $product->name === self::PRODUCT_GAS_PLUS_ISI) {
                $isiGas = $products->get(self::PRODUCT_ISI_GAS);
                $gasPlusIsi = $products->get(self::PRODUCT_GAS_PLUS_ISI);

                $isiGas?->increment('stock', $quantity);
                $gasPlusIsi?->increment('stock', $quantity);
                $stockEffect = self::PRODUCT_ISI_GAS.' +'.$quantity.', '.self::PRODUCT_GAS_PLUS_ISI.' +'.$quantity;
            } else {
                $product->increment('stock', $quantity);
            }

            GasTransaction::create([
                'reference_number' => $this->generateReferenceNumber('IN'),
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'type' => GasTransaction::TYPE_INPUT,
                'quantity' => $quantity,
                'unit_price' => 0,
                'total' => 0,
                'payment_method' => null,
                'discount_applied' => false,
                'stock_effect' => $stockEffect,
                'notes' => $validated['notes'] ?? null,
                'transaction_date' => now('Asia/Jakarta'),
            ]);
        });

        return redirect()
            ->route('gas.index')
            ->with('status', 'Stok Gas berhasil ditambahkan.');
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $gasCategory = $this->ensureGasCatalogExists();

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('category_id', $gasCategory->id)),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'use_discount' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $gasCategory) {
            $products = $this->getGasProducts($gasCategory, true)->keyBy('name');
            $selectedProduct = $products->firstWhere('id', (int) $validated['product_id']);

            if (! $selectedProduct) {
                throw ValidationException::withMessages([
                    'product_id' => 'Jenis Gas tidak valid.',
                ]);
            }

            $quantity = (int) $validated['quantity'];
            $useDiscount = (bool) ($validated['use_discount'] ?? false);
            $unitPrice = $this->calculateSaleUnitPrice($selectedProduct, $quantity, $useDiscount);
            $stockEffect = 'Tidak ada perubahan stok.';

            if ($selectedProduct->name === self::PRODUCT_ISI_GAS) {
                $sourceProduct = $products->get(self::PRODUCT_GAS_PLUS_ISI);
                $targetProduct = $products->get(self::PRODUCT_GAS_KOSONG);

                if (! $sourceProduct || ! $targetProduct || $selectedProduct->stock < $quantity || $sourceProduct->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok Isi Gas dan Gas + Isi harus cukup untuk penjualan Isi Gas.',
                    ]);
                }

                $selectedProduct->decrement('stock', $quantity);
                $sourceProduct->decrement('stock', $quantity);
                $targetProduct->increment('stock', $quantity);
                $stockEffect = self::PRODUCT_ISI_GAS.' -'.$quantity.', '.self::PRODUCT_GAS_PLUS_ISI.' -'.$quantity.', '.self::PRODUCT_GAS_KOSONG.' +'.$quantity;
            }

            if ($selectedProduct->name === self::PRODUCT_GAS_PLUS_ISI) {
                $sourceProduct = $products->get(self::PRODUCT_ISI_GAS);

                if (! $sourceProduct || $selectedProduct->stock < $quantity || $sourceProduct->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Stok Gas + Isi dan Isi Gas harus cukup untuk penjualan Gas + Isi.',
                    ]);
                }

                $selectedProduct->decrement('stock', $quantity);
                $sourceProduct->decrement('stock', $quantity);
                $stockEffect = self::PRODUCT_GAS_PLUS_ISI.' -'.$quantity.', '.self::PRODUCT_ISI_GAS.' -'.$quantity;
            }

            GasTransaction::create([
                'reference_number' => $this->generateReferenceNumber('SL'),
                'product_id' => $selectedProduct->id,
                'user_id' => $request->user()?->id,
                'type' => GasTransaction::TYPE_SALE,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $quantity,
                'payment_method' => $validated['payment_method'],
                'discount_applied' => $useDiscount,
                'stock_effect' => $stockEffect,
                'notes' => $validated['notes'] ?? null,
                'transaction_date' => now('Asia/Jakarta'),
            ]);
        });

        return redirect()
            ->route('gas.index')
            ->with('status', 'Penjualan Gas berhasil disimpan.');
    }

    protected function ensureGasCatalogExists(): Category
    {
        $category = Category::query()->firstOrCreate(
            ['name' => self::CATEGORY_NAME],
            ['description' => 'Produk Gas']
        );

        collect([
            [
                'name' => self::PRODUCT_ISI_GAS,
                'sku' => 'GAS-ISI',
                'price' => 20000,
            ],
            [
                'name' => self::PRODUCT_GAS_KOSONG,
                'sku' => 'GAS-KOSONG',
                'price' => 180000,
            ],
            [
                'name' => self::PRODUCT_GAS_PLUS_ISI,
                'sku' => 'GAS-PLUS-ISI',
                'price' => 210000,
            ],
        ])->each(function (array $product) use ($category) {
            Product::query()->firstOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $product['name'],
                ],
                [
                    'sku' => $product['sku'],
                    'price' => $product['price'],
                    'stock' => 0,
                    'unit' => 'tabung',
                ]
            );
        });

        return $category;
    }

    protected function getGasProducts(Category $gasCategory, bool $lockForUpdate = false): Collection
    {
        $query = Product::query()
            ->where('category_id', $gasCategory->id)
            ->orderByRaw(
                "CASE name
                    WHEN '".self::PRODUCT_ISI_GAS."' THEN 1
                    WHEN '".self::PRODUCT_GAS_KOSONG."' THEN 2
                    WHEN '".self::PRODUCT_GAS_PLUS_ISI."' THEN 3
                    ELSE 99
                END"
            );

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    protected function buildGasTransactionQuery(?string $search, ?string $dateFrom, ?string $dateTo, string $sort): Builder
    {
        return GasTransaction::query()
            ->with(['product', 'user'])
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('stock_effect', 'like', "%{$search}%")
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
                $query->whereDate('transaction_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('transaction_date', '<=', $dateTo);
            })
            ->orderBy('transaction_date', $sort === 'oldest' ? 'asc' : 'desc');
    }

    protected function calculateSaleUnitPrice(Product $product, int $quantity, bool $useDiscount): float
    {
        if ($product->name === self::PRODUCT_ISI_GAS) {
            if ($useDiscount && $quantity > 99) {
                return 18500;
            }

            if ($useDiscount && $quantity > 2) {
                return 19000;
            }

            return 20000;
        }

        if ($product->name === self::PRODUCT_GAS_KOSONG) {
            return 180000;
        }

        return 210000;
    }

    protected function defaultPriceForProduct(Product $product): float
    {
        return $this->calculateSaleUnitPrice($product, 1, false);
    }

    protected function generateReferenceNumber(string $prefix): string
    {
        return 'GAS-'.$prefix.'-'.now('Asia/Jakarta')->format('YmdHis').'-'.random_int(100, 999);
    }
}
