<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $search = request('search');

        $products = $this->buildProductInventoryQuery($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('products.index', compact('products', 'categories', 'search'));
    }

    public function export(Request $request)
    {
        $search = $request->query('search');

        $products = $this->buildProductInventoryQuery($search)
            ->latest()
            ->get();

        $filename = 'inventory-'.now()->format('Ymd_His').'.xls';

        return response()
            ->view('products.exports.inventory', compact('products', 'search'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('status', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted successfully.');
    }

    protected function buildProductInventoryQuery(?string $search)
    {
        return Product::query()
            ->with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            });
    }
}
