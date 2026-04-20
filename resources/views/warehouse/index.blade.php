@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Warehouse stock movement</h2>
                    <p class="mt-1 text-sm text-slate-500">Tambah atau kurangi stok hanya untuk produk dengan kategori Gudang.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $products->count() }} produk
                </span>
            </div>

            @if (! $gudangCategory)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                    Kategori <strong>Gudang</strong> belum dibuat. Tambahkan kategori tersebut lalu assign produk untuk mulai mencatat stok gudang.
                </div>
            @elseif ($products->isEmpty())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Belum ada produk dengan kategori <strong>Gudang</strong>.
                </div>
            @else
                @php
                    $selectedProduct = $products->firstWhere('id', (int) old('product_id'));
                    $selectedProductLabel = $selectedProduct
                        ? $selectedProduct->name.' ('.$selectedProduct->sku.')'
                        : '';
                @endphp
                <form action="{{ route('warehouse.store') }}" method="POST" class="mt-6 grid gap-4">
                    @csrf
                    <div>
                        <label for="product_lookup" class="text-sm font-medium text-slate-700">Produk Gudang</label>
                        <input
                            id="product_lookup"
                            name="product_lookup"
                            type="search"
                            list="warehouse-product-options"
                            value="{{ old('product_lookup', $selectedProductLabel) }}"
                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none"
                            placeholder="Cari nama produk atau SKU gudang"
                            autocomplete="off"
                            required
                        >
                        <input id="product_id" name="product_id" type="hidden" value="{{ old('product_id') }}">
                        <datalist id="warehouse-product-options">
                            @foreach ($products as $product)
                                <option value="{{ $product->name }} ({{ $product->sku }})"></option>
                            @endforeach
                        </datalist>
                        <p class="mt-2 text-xs text-slate-500">Ketik nama produk atau SKU, lalu pilih dari daftar yang muncul.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="type" class="text-sm font-medium text-slate-700">Tipe</label>
                            <select id="type" name="type" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih tipe</option>
                                @foreach ($movementTypes as $movementType)
                                    <option value="{{ $movementType }}" @selected(old('type') === $movementType)>
                                        {{ $movementType === 'add' ? 'Tambah stok' : 'Kurangi stok' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="quantity" class="text-sm font-medium text-slate-700">Jumlah</label>
                            <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="text-sm font-medium text-slate-700">Catatan</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: Barang masuk dari supplier atau stok rusak dipisahkan" required>{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">
                        Simpan pergerakan stok
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Riwayat gudang</h2>
                    <p class="mt-1 text-sm text-slate-500">Semua penambahan dan pengurangan stok Gudang tersimpan bersama catatannya.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $movements->total() }} log
                </span>
            </div>

            <form action="{{ route('warehouse.index') }}" method="GET" class="mt-6 grid gap-4 md:grid-cols-[minmax(0,1fr)_160px_160px_180px_auto]">
                <div>
                    <label for="warehouse-search" class="text-sm font-medium text-slate-700">Cari riwayat</label>
                    <input id="warehouse-search" name="search" type="text" value="{{ $search }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Cari produk, SKU, catatan, tipe, atau user">
                </div>
                <div>
                    <label for="warehouse-date-from" class="text-sm font-medium text-slate-700">Dari tanggal</label>
                    <input id="warehouse-date-from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="warehouse-date-to" class="text-sm font-medium text-slate-700">Sampai tanggal</label>
                    <input id="warehouse-date-to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="warehouse-sort" class="text-sm font-medium text-slate-700">Urutkan</label>
                    <select id="warehouse-sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Cari</button>
                    @if ($search || $dateFrom || $dateTo || $sort !== 'latest')
                        <a href="{{ route('warehouse.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Reset</a>
                    @endif
                </div>
            </form>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Produk</th>
                            <th class="px-4 py-3 font-medium">Perubahan</th>
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
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $movement->type === 'add' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $movement->type === 'add' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $movement->notes }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $movement->user?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada riwayat pergerakan stok gudang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->isEmpty() && ($search || $dateFrom || $dateTo))
                <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    Tidak ada riwayat gudang yang cocok dengan filter yang dipilih.
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

    @if ($gudangCategory && $products->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productLookup = document.getElementById('product_lookup');
                const productId = document.getElementById('product_id');
                const warehouseForm = productLookup ? productLookup.form : null;
                const productMap = {
                    @foreach ($products as $product)
                        @php($label = $product->name.' ('.$product->sku.')')
                        @json($label): @json((string) $product->id),
                    @endforeach
                };

                if (! productLookup || ! productId || ! warehouseForm) {
                    return;
                }

                const syncSelectedProduct = function () {
                    const selectedId = productMap[productLookup.value] ?? '';

                    productId.value = selectedId;
                    productLookup.setCustomValidity(selectedId || productLookup.value === '' ? '' : 'Pilih produk dari daftar yang tersedia.');
                };

                productLookup.addEventListener('input', syncSelectedProduct);
                productLookup.addEventListener('change', syncSelectedProduct);
                warehouseForm.addEventListener('submit', function () {
                    syncSelectedProduct();
                });

                syncSelectedProduct();
            });
        </script>
    @endif
@endsection
