<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WarehouseStockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseInvoiceController extends Controller
{
    public function index(): View
    {
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');

        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $invoices = $this->buildWarehouseInvoiceQuery($search, $dateFrom, $dateTo, $sort)
            ->paginate(5, ['*'], 'invoice_page')
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

        $cashierName = auth()->user()?->name ?? 'Kasir';

        return view('warehouse-invoices.index', compact(
            'customers',
            'products',
            'invoices',
            'productData',
            'cashierName',
            'search',
            'dateFrom',
            'dateTo',
            'sort',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_status' => ['required', Rule::in(['Lunas', 'Belum Lunas'])],
            'customer_payment' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id'),
            ],
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

        if ($products->count() !== $items->count()) {
            return redirect()
                ->route('warehouse-invoices.index')
                ->withErrors([
                    'items' => 'Satu atau lebih produk yang dipilih tidak valid.',
                ])
                ->withInput();
        }

        $subtotal = $items->sum(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);

            return $product ? $product->price * $item['quantity'] : 0;
        });

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (! $product || $product->stock < $item['quantity']) {
                return redirect()
                    ->route('warehouse-invoices.index')
                    ->withErrors([
                        'items' => 'Satu atau lebih produk tidak memiliki stok yang cukup.',
                    ])
                    ->withInput();
            }
        }

        $total = $subtotal;
        $customerPayment = (float) $validated['customer_payment'];

        if ($customerPayment < $total) {
            return redirect()
                ->route('warehouse-invoices.index')
                ->withErrors([
                    'customer_payment' => 'Pembayaran pelanggan harus lebih besar atau sama dengan total.',
                ])
                ->withInput();
        }

        $changeAmount = $customerPayment - $total;
        $cashierName = $request->user()?->name ?? 'Kasir';

        $sale = DB::transaction(function () use ($request, $validated, $items, $products, $subtotal, $total, $customerPayment, $changeAmount, $cashierName) {
            $soldAt = now('Asia/Jakarta');
            $invoiceNumber = $this->generateInvoiceNumber($soldAt);

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'cashier_name' => $cashierName,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'customer_payment' => $customerPayment,
                'change_amount' => $changeAmount,
                'payment_status' => $validated['payment_status'],
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

                WarehouseStockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()?->id,
                    'type' => WarehouseStockMovement::TYPE_REMOVE,
                    'quantity' => $item['quantity'],
                    'notes' => $sale->invoice_number,
                    'moved_at' => $soldAt,
                ]);
            }

            return $sale;
        });

        return redirect()
            ->route('warehouse-invoices.show', $sale)
            ->with('status', 'Invoice Gudang berhasil dibuat.');
    }

    public function show(Sale $sale): View
    {
        $this->ensureWarehouseInvoice($sale);
        $sale->load(['customer', 'items.product']);

        return view('warehouse-invoices.show', compact('sale'));
    }

    public function receipt(Sale $sale): View
    {
        $this->ensureWarehouseInvoice($sale);
        $sale->load(['customer', 'items.product']);

        return view('warehouse-invoices.receipt', compact('sale'));
    }

    public function updatePaymentStatus(Request $request, Sale $sale): RedirectResponse
    {
        $this->ensureWarehouseInvoice($sale);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['Lunas', 'Belum Lunas'])],
        ]);

        $sale->update([
            'payment_status' => $validated['payment_status'],
        ]);

        return redirect()
            ->route('warehouse-invoices.show', $sale)
            ->with('status', 'Status pembayaran invoice Gudang berhasil diperbarui.');
    }

    protected function buildWarehouseInvoiceQuery(?string $search, ?string $dateFrom, ?string $dateTo, string $sort): Builder
    {
        return Sale::query()
            ->with(['customer', 'items.product.category'])
            ->where('invoice_number', 'like', 'INV.%.%')
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.product', function ($productQuery) use ($search) {
                            $productQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
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

    protected function ensureWarehouseInvoice(Sale $sale): void
    {
        $sale->loadMissing('items.product.category');

        abort_unless(
            str_starts_with($sale->invoice_number, 'INV.')
            && $sale->items->isNotEmpty(),
            404
        );
    }

    protected function generateInvoiceNumber($soldAt): string
    {
        $prefix = 'INV.'.$soldAt->format('md').'.';
        $transactionNumber = Sale::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('%s%04d', $prefix, $transactionNumber);
    }
}
