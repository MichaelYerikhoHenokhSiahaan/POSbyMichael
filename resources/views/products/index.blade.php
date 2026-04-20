@extends('layouts.app')

@section('content')
    <div class="auto-layout-grid">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Add product</h2>
            <p class="mt-1 text-sm text-slate-500">Register a new inventory item for sale.</p>

            <form action="{{ route('products.store') }}" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <label for="category_id" class="text-sm font-medium text-slate-700">Category</label>
                    <select id="category_id" name="category_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sku" class="text-sm font-medium text-slate-700">ID barang</label>
                    <input id="sku" name="sku" type="text" value="{{ old('sku') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Product name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="price" class="text-sm font-medium text-slate-700">Price</label>
                    <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price',0) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none"required>
                </div>
                <div>
                    <label for="stock" class="text-sm font-medium text-slate-700">Stock</label>
                    <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', ) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div class="md:col-span-2">
                    <label for="unit" class="text-sm font-medium text-slate-700">Unit</label>
                    <input id="unit" name="unit" type="text" value="{{ old('unit', 'dus') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <button type="submit" class="md:col-span-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save product</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Inventory</h2>
                    <p class="mt-1 text-sm text-slate-500">Track prices, categories, and stock levels.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $products->total() }} items
                </span>
            </div>

            <form action="{{ route('products.index') }}" method="GET" class="auto-layout-filters mt-6">
                <div class="min-w-0">
                    <label for="inventory-search" class="text-sm font-medium text-slate-700">Search inventory</label>
                    <input id="inventory-search" name="search" type="text" value="{{ $search }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Search by product name, SKU, or category">
                </div>
                <div class="auto-layout-actions">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Search</button>
                    <a href="{{ route('warehouse.index') }}" class="rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white">Warehouse</a>
                    <button type="submit" formaction="{{ route('products.export') }}" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Export Excel</button>
                    @if ($search)
                        <a href="{{ route('products.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Clear</a>
                    @endif
                </div>
            </form>

            <div class="auto-layout-scroll mt-6 rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Product</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $product->sku }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                <td class="px-4 py-3 font-medium">Rp {{ number_format($product->price, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $product->stock <= 5 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ number_format($product->stock) }} {{ $product->unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('products.edit', $product) }}" class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">Edit</a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No products have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->isEmpty() && $search)
                <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    No matching products found.
                </p>
            @endif

            @if ($products->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} products
                    </p>
                    <div>
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
