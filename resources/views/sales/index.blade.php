@extends('layouts.app')

@section('content')
    @php($oldItems = old('items', [['product_id' => '', 'quantity' => 1]]))

    <div class="space-y-8">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Create sale</h2>
                    <p class="mt-1 text-sm text-slate-500">Build a transaction, deduct stock, and issue an invoice.</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">
                    POS Active
                </span>
            </div>

            <form action="{{ route('sales.store') }}" method="POST" class="mt-6 space-y-6" id="sale-form">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="customer_id" class="text-sm font-medium text-slate-700">Customer</label>
                        <select id="customer_id" name="customer_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                            <option value="">Walk-in customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Cashier</p>
                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                            {{ $cashierName }}
                        </div>
                    </div>
                    <div>
                        <label for="payment_method" class="text-sm font-medium text-slate-700">Payment method</label>
                        <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                            @foreach (['cash', 'card', 'transfer', 'e-wallet'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ str($method)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="customer_payment" class="text-sm font-medium text-slate-700">Customer payment</label>
                        <input id="customer_payment" name="customer_payment" type="number" min="0" step="0.01" value="{{ old('customer_payment', 0) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    </div>
                   
                        <div>
                            <label for="discount" class="text-sm font-medium text-slate-700">Discount</label>
                            <input id="discount" name="discount" type="number" min="0" step="0.01" value="{{ $canManageDiscount ? old('discount', 0) : 0 }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none disabled:bg-slate-100 disabled:text-slate-400" @disabled(! $canManageDiscount)>
                        </div>
                   
                </div>

                <div>
                    <div class="auto-layout-header">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Line items</h3>
                            <p class="mt-1 text-sm text-slate-500">Select products and quantities for this order.</p>
                        </div>
                        <button type="button" id="add-item" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Add item</button>
                    </div>

                    <div class="mt-4 space-y-4" id="sale-items">
                        @foreach ($oldItems as $index => $item)
                            <div class="sale-item grid gap-4 rounded-3xl border border-slate-200 p-4 md:grid-cols-[1fr_160px_120px]">
                                <div>
                                    <label class="text-sm font-medium text-slate-700">Product</label>
                                    <div class="product-dropdown relative mt-2">
                                        <input type="hidden" name="items[{{ $index }}][product_id]" class="product-select" value="{{ $item['product_id'] ?? '' }}" required>
                                        <button type="button" class="dropdown-toggle flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                            <span class="dropdown-label truncate">Select product</span>
                                            <span class="ml-4 text-slate-400">▼</span>
                                        </button>
                                        <div class="dropdown-panel absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70">
                                            <input type="text" class="product-search w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Search product">
                                            <div class="product-options mt-3 max-h-[380px] space-y-2 overflow-y-auto pr-1"></div>
                                            <div class="product-pagination mt-3 flex items-center justify-between gap-2 text-xs text-slate-500"></div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-slate-700">Quantity</label>
                                    <input type="number" name="items[{{ $index }}][quantity]" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" class="quantity-input mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                                </div>
                                <div class="flex flex-col justify-between">
                                    <div class="text-sm text-slate-500">
                                        <p class="line-total-label font-semibold text-slate-900">Rp 0.00</p>
                                    </div>
                                    <button type="button" class="remove-item mt-3 rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes') }}</textarea>
                </div>

                <div class="grid gap-4 rounded-3xl bg-slate-900 p-5 text-white md:grid-cols-4">
                    <div>
                        <p class="text-sm text-slate-300">Estimated subtotal</p>
                        <p class="mt-2 text-2xl font-semibold" id="subtotal-preview">Rp 0.00</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-300">Discount</p>
                        <p class="mt-2 text-2xl font-semibold" id="discount-preview">Rp 0.00</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-300">Estimated total</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-300" id="total-preview">Rp 0.00</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-300">Estimated change</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-300" id="change-preview">Rp 0.00</p>
                    </div>
                </div>

                <button type="submit" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200/80">Complete sale</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Recent transactions</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest completed POS sales.</p>
                </div>
                <form action="{{ route('sales.index') }}" method="GET" class="auto-layout-filters">
                    <div>
                        <label for="search" class="text-sm font-medium text-slate-700">Product or invoice</label>
                        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Search by product name or invoice" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="category_id" class="text-sm font-medium text-slate-700">Category</label>
                        <select id="category_id" name="category_id" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="text-sm font-medium text-slate-700">From date</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="date_to" class="text-sm font-medium text-slate-700">To date</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="sort" class="text-sm font-medium text-slate-700">Sort</label>
                        <select id="sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="latest" @selected($sort === 'latest')>Latest</option>
                            <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                        </select>
                    </div>
                    <div class="auto-layout-actions">
                        <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Apply</button>
                        <button type="submit" formaction="{{ route('sales.export-recent-transactions') }}" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Export Excel</button>
                        @if ($search || $categoryId || $dateFrom || $dateTo || $sort !== 'latest')
                            <a href="{{ route('sales.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Clear</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($recentSales as $sale)
                    <a href="{{ route('sales.show', $sale) }}" class="block rounded-3xl border border-slate-200 p-5 transition hover:border-emerald-300 hover:bg-emerald-50/40">
                        <div class="auto-layout-card-row">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $sale->invoice_number }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $sale->customer?->name ?? 'Walk-in customer' }} • {{ $sale->payment_method }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-emerald-600">Rp {{ number_format($sale->total, 2) }}</p>
                                <p class="mt-1 text-sm text-slate-400">{{ $sale->sold_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>

                        @if ($sale->items->isNotEmpty())
                            <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Purchase details</p>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ($sale->items->take(4) as $item)
                                        <div class="rounded-2xl bg-white px-3 py-3 ring-1 ring-slate-200">
                                            <p class="text-sm font-semibold text-slate-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ number_format($item->quantity) }} x Rp {{ number_format($item->unit_price, 2) }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($sale->items->count() > 4)
                                    <p class="mt-3 text-xs text-slate-500">
                                        +{{ $sale->items->count() - 4 }} item lainnya
                                    </p>
                                @endif
                            </div>
                        @endif
                    </a>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No sales have been recorded yet.
                    </div>
                @endforelse
            </div>

            @if ($recentSales->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ $recentSales->firstItem() }}-{{ $recentSales->lastItem() }} of {{ $recentSales->total() }} transactions
                    </p>
                    <div>
                        {{ $recentSales->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const itemsContainer = document.querySelector('#sale-items');
            const addItemButton = document.querySelector('#add-item');
            const discountInput = document.querySelector('#discount');
            const customerPaymentInput = document.querySelector('#customer_payment');
            const subtotalPreview = document.querySelector('#subtotal-preview');
            const discountPreview = document.querySelector('#discount-preview');
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
                    return 'Select product';
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
                            No products found.
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
                                <span class="mt-1 block truncate text-xs text-slate-500">${escapeHtml(product.sku ?? 'No SKU')} • ${escapeHtml(product.category ?? 'Uncategorized')}</span>
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
                        Page ${currentPage} of ${totalPages}
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
                document.querySelectorAll('.sale-item').forEach(item => {
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
                wrapper.className = 'sale-item grid gap-4 rounded-3xl border border-slate-200 p-4 md:grid-cols-[1fr_160px_120px]';
                wrapper.innerHTML = `
                    <div>
                        <label class="text-sm font-medium text-slate-700">Product</label>
                        <div class="product-dropdown relative mt-2">
                            <input type="hidden" name="items[${index}][product_id]" class="product-select" value="${selectedId}" required>
                            <button type="button" class="dropdown-toggle flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <span class="dropdown-label truncate">Select product</span>
                                <span class="ml-4 text-slate-400">▼</span>
                            </button>
                            <div class="dropdown-panel absolute left-0 right-0 top-full z-20 mt-2 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70">
                                <input type="text" class="product-search w-full rounded-2xl border border-slate-200 px-4 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Search product">
                                <div class="product-options mt-3 max-h-[380px] space-y-2 overflow-y-auto pr-1"></div>
                                <div class="product-pagination mt-3 flex items-center justify-between gap-2 text-xs text-slate-500"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Quantity</label>
                        <input type="number" name="items[${index}][quantity]" min="1" step="1" value="${quantity}" class="quantity-input mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div class="flex flex-col justify-between">
                        <div class="text-sm text-slate-500">
                            <p class="line-total-label font-semibold text-slate-900">Rp 0.00</p>
                        </div>
                        <button type="button" class="remove-item mt-3 rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Remove</button>
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

                document.querySelectorAll('.sale-item').forEach(row => {
                    const productId = row.querySelector('.product-select').value;
                    const quantityInput = row.querySelector('.quantity-input');
                    const product = findProduct(productId);
                    const price = Number(product?.price || 0);
                    const quantity = Number(quantityInput.value || 0);

                    subtotal += price * quantity;
                });

                const discount = discountInput ? Number(discountInput.value || 0) : 0;
                const customerPayment = customerPaymentInput ? Number(customerPaymentInput.value || 0) : 0;
                const total = Math.max(subtotal - discount, 0);
                const change = Math.max(customerPayment - total, 0);

                subtotalPreview.textContent = formatCurrency(subtotal);
                discountPreview.textContent = formatCurrency(discount);
                totalPreview.textContent = formatCurrency(total);
                changePreview.textContent = formatCurrency(change);
            };

            addItemButton.addEventListener('click', () => {
                createRow(nextIndex);
                nextIndex += 1;
            });

            itemsContainer.addEventListener('input', event => {
                const row = event.target.closest('.sale-item');

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
                    const row = toggle.closest('.sale-item');
                    const panel = row.querySelector('.dropdown-panel');

                    if (panel.classList.contains('hidden')) {
                        openDropdown(row);
                    } else {
                        closeDropdown(row);
                    }

                    return;
                }

                if (previousPageButton || nextPageButton) {
                    const row = event.target.closest('.sale-item');
                    const searchInput = row.querySelector('.product-search');
                    const currentPage = Number(row.dataset.dropdownPage || 1);

                    row.dataset.dropdownPage = String(previousPageButton ? currentPage - 1 : currentPage + 1);
                    renderDropdownOptions(row, searchInput.value);

                    return;
                }

                if (option) {
                    const row = option.closest('.sale-item');
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

                if (itemsContainer.querySelectorAll('.sale-item').length === 1) {
                    return;
                }

                event.target.closest('.sale-item').remove();
                updateTotals();
            });

            if (discountInput) {
                discountInput.addEventListener('input', updateTotals);
            }

            if (customerPaymentInput) {
                customerPaymentInput.addEventListener('input', updateTotals);
            }

            document.addEventListener('click', event => {
                if (event.target.closest('.product-dropdown')) {
                    return;
                }

                document.querySelectorAll('.sale-item').forEach(closeDropdown);
            });

            document.querySelectorAll('.sale-item').forEach(row => {
                renderDropdownOptions(row);
                updateRow(row);
            });
            updateTotals();
        });
    </script>
@endsection
