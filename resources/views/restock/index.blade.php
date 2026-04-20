@extends('layouts.app')

@section('content')
    <div class="auto-layout-grid">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Restock produk</h2>
                    <p class="mt-1 text-sm text-slate-500">Tambah stok produk dari semua kategori dengan catatan wajib. Menu ini tidak menyediakan pengurangan stok.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $products->count() }} produk
                </span>
            </div>

            @if ($products->isEmpty())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Belum ada produk yang tersedia untuk restock.
                </div>
            @else
                @php
                    $selectedProduct = $products->firstWhere('id', (int) old('product_id'));
                    $selectedProductLabel = $selectedProduct
                        ? $selectedProduct->name.' ('.$selectedProduct->sku.')'
                        : '';
                @endphp
                <form action="{{ route('restock.store') }}" method="POST" class="mt-6 grid gap-4">
                    @csrf
                    <div>
                        <label for="product_lookup" class="text-sm font-medium text-slate-700">Produk</label>
                        <input
                            id="product_lookup"
                            name="product_lookup"
                            type="search"
                            list="restock-product-options"
                            value="{{ old('product_lookup', $selectedProductLabel) }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none"
                            placeholder="Cari nama produk atau SKU"
                            autocomplete="off"
                            required
                        >
                        <input id="product_id" name="product_id" type="hidden" value="{{ old('product_id') }}">
                        <datalist id="restock-product-options">
                            @foreach ($products as $product)
                                <option value="{{ $product->name }} ({{ $product->sku }})"></option>
                            @endforeach
                        </datalist>
                        <p class="mt-2 text-xs text-slate-500">Ketik nama produk atau SKU, lalu pilih dari daftar yang muncul.</p>
                    </div>

                    <div>
                        <label for="quantity" class="text-sm font-medium text-slate-700">Jumlah tambah</label>
                        <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    </div>

                    <div>
                        <label for="notes" class="text-sm font-medium text-slate-700">Catatan</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: Restock dari supplier atau penambahan stok harian" required>{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">
                        Simpan restock
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Riwayat restock</h2>
                    <p class="mt-1 text-sm text-slate-500">Semua penambahan stok produk tersimpan bersama catatannya.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $movements->total() }} log
                </span>
            </div>

            <form action="{{ route('restock.index') }}" method="GET" class="auto-layout-filters mt-6">
                <div>
                    <label for="restock-search" class="text-sm font-medium text-slate-700">Cari riwayat</label>
                    <input id="restock-search" name="search" type="text" value="{{ $search }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Cari produk, SKU, catatan, atau user">
                </div>
                <div>
                    <label for="restock-date-from" class="text-sm font-medium text-slate-700">Dari tanggal</label>
                    <input id="restock-date-from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="restock-date-to" class="text-sm font-medium text-slate-700">Sampai tanggal</label>
                    <input id="restock-date-to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="restock-sort" class="text-sm font-medium text-slate-700">Urutkan</label>
                    <select id="restock-sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
                    </select>
                </div>
                <div class="auto-layout-actions">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Cari</button>
                    @if ($search || $dateFrom || $dateTo || $sort !== 'latest')
                        <a href="{{ route('restock.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Reset</a>
                    @endif
                </div>
            </form>

            <div class="auto-layout-scroll mt-6 rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Produk</th>
                            <th class="px-4 py-3 font-medium">Jumlah</th>
                            <th class="px-4 py-3 font-medium">Catatan</th>
                            <th class="px-4 py-3 font-medium">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $movement->moved_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $movement->product?->name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $movement->product?->sku ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        +{{ number_format($movement->quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->notes }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $movement->user?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada riwayat restock.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->isEmpty() && ($search || $dateFrom || $dateTo))
                <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    Tidak ada riwayat restock yang cocok dengan filter yang dipilih.
                </p>
            @endif

            @if ($movements->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ $movements->firstItem() }}-{{ $movements->lastItem() }} of {{ $movements->total() }} logs
                    </p>
                    <div>
                        {{ $movements->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>

    @if ($products->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productLookup = document.getElementById('product_lookup');
                const productId = document.getElementById('product_id');
                const restockForm = productLookup ? productLookup.form : null;
                const productMap = {
                    @foreach ($products as $product)
                        @php($label = $product->name.' ('.$product->sku.')')
                        @json($label): @json((string) $product->id),
                    @endforeach
                };

                if (! productLookup || ! productId || ! restockForm) {
                    return;
                }

                const syncSelectedProduct = function () {
                    const selectedId = productMap[productLookup.value] ?? '';

                    productId.value = selectedId;
                    productLookup.setCustomValidity(selectedId || productLookup.value === '' ? '' : 'Pilih produk dari daftar yang tersedia.');
                };

                productLookup.addEventListener('input', syncSelectedProduct);
                productLookup.addEventListener('change', syncSelectedProduct);
                restockForm.addEventListener('submit', function () {
                    syncSelectedProduct();
                });

                syncSelectedProduct();
            });
        </script>
    @endif
@endsection
