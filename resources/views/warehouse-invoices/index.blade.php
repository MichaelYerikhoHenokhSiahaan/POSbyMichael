@extends('layouts.app')

@section('content')
    @php($oldItems = old('items', [['product_id' => '', 'quantity' => 1]]))

    <div class="space-y-8">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Buat Invoice Gudang</h2>
                    <p class="mt-1 text-sm text-slate-500">Buat invoice penjualan untuk produk dari semua kategori.</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">
                    Semua Kategori
                </span>
            </div>

            @if ($products->isEmpty())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Belum ada produk yang tersedia untuk invoice.
                </div>
            @else
                <form action="{{ route('warehouse-invoices.store') }}" method="POST" class="mt-6 space-y-6" id="warehouse-invoice-form">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="customer_id" class="text-sm font-medium text-slate-700">Pelanggan</label>
                            <select id="customer_id" name="customer_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                                <option value="">Pelanggan umum</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-700">Kasir</p>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                {{ $cashierName }}
                            </div>
                        </div>
                        <div>
                            <label for="payment_method" class="text-sm font-medium text-slate-700">Metode pembayaran</label>
                            <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                @foreach (['cash', 'card', 'transfer', 'e-wallet'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ str($method)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_status" class="text-sm font-medium text-slate-700">Status pembayaran</label>
                            <select id="payment_status" name="payment_status" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                @foreach (['Lunas', 'Belum Lunas'] as $status)
                                    <option value="{{ $status }}" @selected(old('payment_status', 'Lunas') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="customer_payment" class="text-sm font-medium text-slate-700">Pembayaran pelanggan</label>
                            <input id="customer_payment" name="customer_payment" type="number" min="0" step="0.01" value="{{ old('customer_payment', 0) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                    </div>

                    <div>
                        <div class="auto-layout-header">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Item invoice</h3>
                                <p class="mt-1 text-sm text-slate-500">Pilih produk dan jumlah yang akan dijual.</p>
                            </div>
                            <button type="button" id="add-item" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Tambah item</button>
                        </div>

                        <div class="mt-4 space-y-4" id="invoice-items">
                            @foreach ($oldItems as $index => $item)
                                <div class="invoice-item grid gap-4 rounded-3xl border border-slate-200 p-4 md:grid-cols-[1fr_160px_120px]">
                                    <div>
                                        <label class="text-sm font-medium text-slate-700">Produk</label>
                                        <div class="product-dropdown relative mt-2">
                                            <input type="hidden" name="items[{{ $index }}][product_id]" class="product-select" value="{{ $item['product_id'] ?? '' }}" required>
                                            <button type="button" class="dropdown-toggle flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                                <span class="dropdown-label truncate">Pilih produk</span>
                                                <span class="ml-4 text-slate-400">▼</span>
                                            </button>
                                            <div class="dropdown-panel absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70">
                                                <input type="text" class="product-search w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Cari produk">
                                                <div class="product-options mt-3 max-h-[380px] space-y-2 overflow-y-auto pr-1"></div>
                                                <div class="product-pagination mt-3 flex items-center justify-between gap-2 text-xs text-slate-500"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-slate-700">Jumlah</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                    </div>
                                    <div class="flex flex-col justify-between">
                                        <div class="text-sm text-slate-500">
                                            <p class="line-total-label font-semibold text-slate-900">Rp 0.00</p>
                                        </div>
                                        <button type="button" class="remove-item mt-3 rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Hapus</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="text-sm font-medium text-slate-700">Catatan</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes') }}</textarea>
                    </div>

                    <div class="grid gap-4 rounded-3xl bg-slate-900 p-5 text-white md:grid-cols-3">
                        <div>
                            <p class="text-sm text-slate-300">Estimasi subtotal</p>
                            <p class="mt-2 text-2xl font-semibold" id="subtotal-preview">Rp 0.00</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-300">Estimasi total</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-300" id="total-preview">Rp 0.00</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-300">Estimasi kembalian</p>
                            <p class="mt-2 text-2xl font-semibold text-amber-300" id="change-preview">Rp 0.00</p>
                        </div>
                    </div>

                    <button type="submit" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200/80">
                        Simpan invoice Gudang
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Riwayat invoice Gudang</h2>
                    <p class="mt-1 text-sm text-slate-500">Daftar invoice Gudang yang sudah dibuat.</p>
                </div>
                <form action="{{ route('warehouse-invoices.index') }}" method="GET" class="auto-layout-filters">
                    <div>
                        <label for="search" class="text-sm font-medium text-slate-700">Cari invoice</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor invoice, produk, SKU, atau pelanggan" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
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
                    <div class="auto-layout-actions">
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Cari</button>
                        @if ($search || $dateFrom || $dateTo || $sort !== 'latest')
                            <a href="{{ route('warehouse-invoices.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($invoices as $invoice)
                    <a href="{{ route('warehouse-invoices.show', $invoice) }}" class="block rounded-3xl border border-slate-200 p-5 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                        <div class="auto-layout-card-row">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $invoice->customer?->name ?? 'Pelanggan umum' }} • {{ str($invoice->payment_method)->headline() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-emerald-600">Rp {{ number_format($invoice->total, 2) }}</p>
                                <p class="mt-1 text-sm font-medium {{ ($invoice->payment_status ?? ((float) $invoice->customer_payment >= (float) $invoice->total ? 'Lunas' : 'Belum Lunas')) === 'Lunas' ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $invoice->payment_status ?? ((float) $invoice->customer_payment >= (float) $invoice->total ? 'Lunas' : 'Belum Lunas') }}
                                </p>
                                <p class="mt-1 text-sm text-slate-400">{{ $invoice->sold_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        Belum ada invoice Gudang yang tersimpan.
                    </div>
                @endforelse
            </div>

            @if ($invoices->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ $invoices->firstItem() }}-{{ $invoices->lastItem() }} dari {{ $invoices->total() }} invoice
                    </p>
                    <div>
                        {{ $invoices->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>

    @if ($products->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const itemsContainer = document.querySelector('#invoice-items');
                const addItemButton = document.querySelector('#add-item');
                const customerPaymentInput = document.querySelector('#customer_payment');
                const subtotalPreview = document.querySelector('#subtotal-preview');
                const totalPreview = document.querySelector('#total-preview');
                const changePreview = document.querySelector('#change-preview');
                const products = @json($productData);
                const lineItemPageSize = 5;

                let nextIndex = {{ count($oldItems) }};

                const formatCurrency = value => `Rp ${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const escapeHtml = value => String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');

                const findProduct = productId => products.find(product => Number(product.id) === Number(productId));

                const productLabel = product => {
                    if (! product) {
                        return 'Pilih produk';
                    }

                    return product.name;
                };

                const renderDropdownOptions = (row, searchTerm = '') => {
                    const selectedId = row.querySelector('.product-select').value;
                    const optionsContainer = row.querySelector('.product-options');
                    const paginationContainer = row.querySelector('.product-pagination');
                    const normalizedTerm = searchTerm.trim().toLowerCase();
                    const filteredProducts = products.filter(product => {
                        const haystack = [
                            product.name,
                            product.sku,
                            product.category,
                            product.unit,
                        ].filter(Boolean).join(' ').toLowerCase();

                        return haystack.includes(normalizedTerm);
                    });

                    if (filteredProducts.length === 0) {
                        optionsContainer.innerHTML = `
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                Produk tidak ditemukan.
                            </div>
                        `;
                        paginationContainer.innerHTML = '';

                        return;
                    }

                    const totalPages = Math.max(Math.ceil(filteredProducts.length / lineItemPageSize), 1);
                    const currentPage = Math.min(
                        Math.max(Number(row.dataset.dropdownPage || 1), 1),
                        totalPages,
                    );
                    const paginatedProducts = filteredProducts.slice(
                        (currentPage - 1) * lineItemPageSize,
                        currentPage * lineItemPageSize,
                    );

                    row.dataset.dropdownPage = String(currentPage);

                    optionsContainer.innerHTML = paginatedProducts.map(product => {
                        const selected = Number(selectedId) === Number(product.id);

                        return `
                            <button
                                type="button"
                                class="product-option flex w-full items-start justify-between rounded-2xl px-4 py-3 text-left transition ${selected ? 'bg-emerald-50 ring-1 ring-emerald-200' : 'bg-slate-50 hover:bg-slate-100'}"
                                data-product-id="${product.id}"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-900">${escapeHtml(product.name)}</span>
                                    <span class="mt-1 block truncate text-xs text-slate-500">${escapeHtml(product.sku ?? 'Tanpa SKU')} • ${escapeHtml(product.category ?? 'Tanpa kategori')}</span>
                                </span>
                                <span class="ml-3 whitespace-nowrap text-right text-xs text-slate-500">
                                    Rp ${product.price.toFixed(2)}
                                </span>
                            </button>
                        `;
                    }).join('');

                    paginationContainer.innerHTML = `
                        <button
                            type="button"
                            class="dropdown-prev rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700 transition hover:bg-slate-200 ${currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
                            ${currentPage === 1 ? 'disabled' : ''}
                        >
                            Prev
                        </button>
                        <span class="text-center text-slate-500">
                            Halaman ${currentPage} dari ${totalPages}
                        </span>
                        <button
                            type="button"
                            class="dropdown-next rounded-full bg-slate-100 px-3 py-1.5 font-medium text-slate-700 transition hover:bg-slate-200 ${currentPage === totalPages ? 'cursor-not-allowed opacity-50' : ''}"
                            ${currentPage === totalPages ? 'disabled' : ''}
                        >
                            Next
                        </button>
                    `;
                };

                const syncDropdownLabel = row => {
                    const selectedId = row.querySelector('.product-select').value;
                    const label = row.querySelector('.dropdown-label');

                    label.textContent = productLabel(findProduct(selectedId));
                };

                const closeDropdown = row => {
                    row.querySelector('.dropdown-panel').classList.add('hidden');
                };

                const openDropdown = row => {
                    document.querySelectorAll('.invoice-item').forEach(item => {
                        if (item !== row) {
                            closeDropdown(item);
                        }
                    });

                    const panel = row.querySelector('.dropdown-panel');
                    const searchInput = row.querySelector('.product-search');

                    panel.classList.remove('hidden');
                    renderDropdownOptions(row, searchInput.value);
                    searchInput.focus();
                    searchInput.select();
                };

                const createRow = (index, selectedId = '', quantity = 1) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'invoice-item grid gap-4 rounded-3xl border border-slate-200 p-4 md:grid-cols-[1fr_160px_120px]';
                    wrapper.innerHTML = `
                        <div>
                            <label class="text-sm font-medium text-slate-700">Produk</label>
                            <div class="product-dropdown relative mt-2">
                                <input type="hidden" name="items[${index}][product_id]" class="product-select" value="${selectedId}" required>
                                <button type="button" class="dropdown-toggle flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <span class="dropdown-label truncate">Pilih produk</span>
                                    <span class="ml-4 text-slate-400">▼</span>
                                </button>
                                <div class="dropdown-panel absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70">
                                    <input type="text" class="product-search w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Cari produk">
                                    <div class="product-options mt-3 max-h-[380px] space-y-2 overflow-y-auto pr-1"></div>
                                    <div class="product-pagination mt-3 flex items-center justify-between gap-2 text-xs text-slate-500"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Jumlah</label>
                            <input type="number" name="items[${index}][quantity]" min="1" step="1" value="${quantity}" class="quantity-input mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div class="flex flex-col justify-between">
                            <div class="text-sm text-slate-500">
                                <p class="line-total-label font-semibold text-slate-900">Rp 0.00</p>
                            </div>
                            <button type="button" class="remove-item mt-3 rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Hapus</button>
                        </div>
                    `;

                    itemsContainer.appendChild(wrapper);
                    syncDropdownLabel(wrapper);
                    renderDropdownOptions(wrapper);
                    updateRow(wrapper);
                    updateTotals();
                };

                const updateRow = row => {
                    const productId = row.querySelector('.product-select').value;
                    const quantityInput = row.querySelector('.quantity-input');
                    const lineTotalLabel = row.querySelector('.line-total-label');
                    const product = findProduct(productId);
                    const price = Number(product?.price || 0);
                    const quantity = Number(quantityInput.value || 0);
                    const lineTotal = price * quantity;

                    lineTotalLabel.textContent = formatCurrency(lineTotal);
                    syncDropdownLabel(row);
                };

                const updateTotals = () => {
                    let subtotal = 0;

                    document.querySelectorAll('.invoice-item').forEach(row => {
                        const productId = row.querySelector('.product-select').value;
                        const quantityInput = row.querySelector('.quantity-input');
                        const product = findProduct(productId);
                        const price = Number(product?.price || 0);
                        const quantity = Number(quantityInput.value || 0);

                        subtotal += price * quantity;
                    });

                    const customerPayment = customerPaymentInput ? Number(customerPaymentInput.value || 0) : 0;
                    const change = Math.max(customerPayment - subtotal, 0);

                    subtotalPreview.textContent = formatCurrency(subtotal);
                    totalPreview.textContent = formatCurrency(subtotal);
                    changePreview.textContent = formatCurrency(change);
                };

                addItemButton.addEventListener('click', () => {
                    createRow(nextIndex);
                    nextIndex += 1;
                });

                itemsContainer.addEventListener('input', event => {
                    const row = event.target.closest('.invoice-item');

                    if (! row) {
                        return;
                    }

                    if (event.target.classList.contains('product-search')) {
                        row.dataset.dropdownPage = '1';
                        renderDropdownOptions(row, event.target.value);
                        return;
                    }

                    updateRow(row);
                    updateTotals();
                });

                itemsContainer.addEventListener('click', event => {
                    const toggle = event.target.closest('.dropdown-toggle');
                    const option = event.target.closest('.product-option');
                    const previousPageButton = event.target.closest('.dropdown-prev');
                    const nextPageButton = event.target.closest('.dropdown-next');

                    if (toggle) {
                        const row = toggle.closest('.invoice-item');
                        const panel = row.querySelector('.dropdown-panel');

                        if (panel.classList.contains('hidden')) {
                            openDropdown(row);
                        } else {
                            closeDropdown(row);
                        }

                        return;
                    }

                    if (previousPageButton || nextPageButton) {
                        const row = event.target.closest('.invoice-item');
                        const searchInput = row.querySelector('.product-search');
                        const currentPage = Number(row.dataset.dropdownPage || 1);

                        row.dataset.dropdownPage = String(previousPageButton ? currentPage - 1 : currentPage + 1);
                        renderDropdownOptions(row, searchInput.value);

                        return;
                    }

                    if (option) {
                        const row = option.closest('.invoice-item');
                        const productId = option.dataset.productId;

                        row.querySelector('.product-select').value = productId;
                        row.querySelector('.product-search').value = '';
                        renderDropdownOptions(row);
                        closeDropdown(row);
                        updateRow(row);
                        updateTotals();

                        return;
                    }

                    if (! event.target.classList.contains('remove-item')) {
                        return;
                    }

                    if (itemsContainer.querySelectorAll('.invoice-item').length === 1) {
                        return;
                    }

                    event.target.closest('.invoice-item').remove();
                    updateTotals();
                });

                if (customerPaymentInput) {
                    customerPaymentInput.addEventListener('input', updateTotals);
                }

                document.addEventListener('click', event => {
                    if (event.target.closest('.product-dropdown')) {
                        return;
                    }

                    document.querySelectorAll('.invoice-item').forEach(closeDropdown);
                });

                document.querySelectorAll('.invoice-item').forEach(row => {
                    renderDropdownOptions(row);
                    updateRow(row);
                });
                updateTotals();
            });
        </script>
    @endif
@endsection
