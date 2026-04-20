@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <section class="grid gap-4 md:grid-cols-3">
            @foreach ($products as $product)
                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">{{ $product->name }}</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($product->stock) }}</p>
                    <p class="mt-1 text-sm text-slate-400">{{ $product->unit }} • {{ $product->sku }}</p>
                    <p class="mt-3 text-sm font-medium text-emerald-600">Harga dasar Rp {{ number_format($product->price, 2) }}</p>
                </div>
            @endforeach
        </section>

        <div class="grid gap-8 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="space-y-8">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Input stok Gas</h2>
                    <p class="mt-1 text-sm text-slate-500">Menu ini hanya menambah stok Gas. `Isi Gas` dan `Gas + Isi` akan selalu disamakan jumlah stoknya, sedangkan pengurangan stok terjadi otomatis dari penjualan Gas sesuai aturan.</p>

                    <form action="{{ route('gas.input.store') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="input_product_id" class="text-sm font-medium text-slate-700">Jenis Gas</label>
                            <select id="input_product_id" name="product_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih Gas</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="input_quantity" class="text-sm font-medium text-slate-700">Jumlah tambah</label>
                            <input id="input_quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', 1) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label for="input_notes" class="text-sm font-medium text-slate-700">Catatan</label>
                            <textarea id="input_notes" name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Tambah stok Gas</button>
                    </form>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Jual Gas</h2>
                    <p class="mt-1 text-sm text-slate-500">Gunakan menu ini untuk penjualan Gas dengan aturan harga dan perubahan stok khusus.</p>

                    <div class="mt-4 rounded-3xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-900">Aturan harga</p>
                        <ul class="mt-2 space-y-1">
                            <li>`Isi Gas`: Rp 20.000</li>
                            <li>Jika diskon dicentang dan jumlah lebih dari 2: Rp 19.000</li>
                            <li>Jika diskon dicentang dan jumlah lebih dari 99: Rp 18.500</li>
                            <li>`Gas Kosong`: Rp 180.000</li>
                            <li>`Gas + Isi`: Rp 210.000</li>
                        </ul>
                        <p class="mt-4 font-semibold text-slate-900">Aturan perubahan stok</p>
                        <ul class="mt-2 space-y-1">
                            <li>`Isi Gas` dan `Gas + Isi` harus selalu memiliki jumlah stok yang sama.</li>
                            <li>Jika `Isi Gas` dijual: `Gas + Isi` berkurang dan `Gas Kosong` bertambah.</li>
                            <li>Jika `Gas + Isi` dijual: `Isi Gas` berkurang.</li>
                            <li>Jika `Gas Kosong` dijual: tidak ada perubahan stok.</li>
                        </ul>
                    </div>

                    <form action="{{ route('gas.sale.store') }}" method="POST" class="mt-6 space-y-4" id="gas-sale-form">
                        @csrf
                        <div>
                            <label for="sale_product_id" class="text-sm font-medium text-slate-700">Jenis jual Gas</label>
                            <select id="sale_product_id" name="product_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                <option value="">Pilih Gas</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="sale_quantity" class="text-sm font-medium text-slate-700">Jumlah jual</label>
                                <input id="sale_quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', 1) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label for="payment_method" class="text-sm font-medium text-slate-700">Metode pembayaran</label>
                                <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                    @foreach (['cash', 'card', 'transfer', 'e-wallet'] as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ str($method)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-600">
                            <input id="use_discount" name="use_discount" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('use_discount'))>
                            Gunakan diskon `Isi Gas`
                        </label>
                        <div class="rounded-3xl bg-slate-900 p-5 text-white">
                            <p class="text-sm text-slate-300">Preview harga satuan</p>
                            <p class="mt-2 text-2xl font-semibold" id="gas-unit-price">Rp 0.00</p>
                            <p class="mt-2 text-sm text-slate-300">Preview total</p>
                            <p class="mt-1 text-xl font-semibold text-emerald-300" id="gas-total-price">Rp 0.00</p>
                        </div>
                        <div>
                            <label for="sale_notes" class="text-sm font-medium text-slate-700">Catatan</label>
                            <textarea id="sale_notes" name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Simpan penjualan Gas</button>
                    </form>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Riwayat Gas</h2>
                        <p class="mt-1 text-sm text-slate-500">Riwayat input dan penjualan Gas.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                        {{ $transactions->total() }} total
                    </span>
                </div>

                <form action="{{ route('gas.index') }}" method="GET" class="mt-6 grid gap-3 md:grid-cols-[minmax(0,1fr)_160px_160px_150px_auto]">
                    <div>
                        <label for="search" class="text-sm font-medium text-slate-700">Cari riwayat</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari referensi, jenis gas, catatan, atau user" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="date_from" class="text-sm font-medium text-slate-700">Dari tanggal</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="date_to" class="text-sm font-medium text-slate-700">Sampai tanggal</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="sort" class="text-sm font-medium text-slate-700">Urutkan</label>
                        <select id="sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                            <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Cari</button>
                        @if ($search || $dateFrom || $dateTo || $sort !== 'latest')
                            <a href="{{ route('gas.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="mt-6 space-y-4">
                    @forelse ($transactions as $transaction)
                        <div class="rounded-3xl border border-slate-200 p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-900">{{ $transaction->reference_number }}</h3>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ $transaction->type === \App\Models\GasTransaction::TYPE_INPUT ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $transaction->type === \App\Models\GasTransaction::TYPE_INPUT ? 'Input' : 'Jual' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-slate-700">{{ $transaction->product?->name ?? '-' }} • Qty {{ number_format($transaction->quantity) }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $transaction->payment_method ? str($transaction->payment_method)->headline().' • ' : '' }}{{ $transaction->transaction_date->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
                                    <p class="mt-1 text-sm text-slate-500">Dicatat oleh {{ $transaction->user?->username ?? 'Unknown' }}</p>
                                    <p class="mt-3 text-sm text-slate-600">Perubahan stok: {{ $transaction->stock_effect ?: 'Tidak ada perubahan stok.' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $transaction->notes ?: 'Tidak ada catatan.' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total</p>
                                    <p class="mt-1 text-lg font-semibold {{ $transaction->type === \App\Models\GasTransaction::TYPE_INPUT ? 'text-blue-600' : 'text-emerald-600' }}">
                                        Rp {{ number_format($transaction->total, 2) }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">Harga satuan Rp {{ number_format($transaction->unit_price, 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $transaction->discount_applied ? 'Diskon aktif' : 'Tanpa diskon' }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                            Belum ada transaksi Gas.
                        </div>
                    @endforelse
                </div>

                @if ($transactions->hasPages())
                    <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-500">
                            Menampilkan {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} dari {{ $transactions->total() }} transaksi
                        </p>
                        <div>
                            {{ $transactions->onEachSide(1)->links() }}
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const productData = @json($productData);
            const saleProductSelect = document.querySelector('#sale_product_id');
            const quantityInput = document.querySelector('#sale_quantity');
            const discountInput = document.querySelector('#use_discount');
            const unitPriceLabel = document.querySelector('#gas-unit-price');
            const totalPriceLabel = document.querySelector('#gas-total-price');

            const findProduct = id => productData.find(product => Number(product.id) === Number(id));

            const calculateUnitPrice = (product, quantity, useDiscount) => {
                if (! product) {
                    return 0;
                }

                if (product.name === 'Isi Gas') {
                    if (useDiscount && quantity > 99) {
                        return 18500;
                    }

                    if (useDiscount && quantity > 2) {
                        return 19000;
                    }

                    return 20000;
                }

                if (product.name === 'Gas Kosong') {
                    return 180000;
                }

                return 210000;
            };

            const formatCurrency = value => `Rp ${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const updatePreview = () => {
                const product = findProduct(saleProductSelect?.value);
                const quantity = Number(quantityInput?.value || 0);
                const useDiscount = Boolean(discountInput?.checked);
                const unitPrice = calculateUnitPrice(product, quantity, useDiscount);
                const totalPrice = unitPrice * quantity;

                if (unitPriceLabel) {
                    unitPriceLabel.textContent = formatCurrency(unitPrice);
                }

                if (totalPriceLabel) {
                    totalPriceLabel.textContent = formatCurrency(totalPrice);
                }
            };

            saleProductSelect?.addEventListener('change', updatePreview);
            quantityInput?.addEventListener('input', updatePreview);
            discountInput?.addEventListener('change', updatePreview);

            updatePreview();
        });
    </script>
@endsection
