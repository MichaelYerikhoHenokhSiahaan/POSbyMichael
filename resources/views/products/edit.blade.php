@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Edit product</h2>
                <p class="mt-1 text-sm text-slate-500">Update pricing and stock for {{ $product->name }}.</p>
            </div>
            <a href="{{ route('products.index') }}" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>

        <form action="{{ route('products.update', $product) }}" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="md:col-span-2">
                <label for="category_id" class="text-sm font-medium text-slate-700">Category</label>
                <select id="category_id" name="category_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sku" class="text-sm font-medium text-slate-700">ID Barang</label>
                <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="name" class="text-sm font-medium text-slate-700">Product name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="price" class="text-sm font-medium text-slate-700">Price</label>
                <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $product->price) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="stock" class="text-sm font-medium text-slate-700">Stock</label>
                <input id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', $product->stock) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div class="md:col-span-2">
                <label for="unit" class="text-sm font-medium text-slate-700">Unit</label>
                <input id="unit" name="unit" type="text" value="{{ old('unit', $product->unit) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <button type="submit" class="md:col-span-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Update product</button>
        </form>
    </div>
@endsection
