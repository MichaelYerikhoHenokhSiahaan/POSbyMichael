@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-300/40">
                <p class="text-sm text-slate-300">Products</p>
                <p class="mt-3 text-3xl font-semibold">{{ number_format($metrics['products']) }}</p>
                <p class="mt-2 text-sm text-slate-300">Registered inventory items</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Units in stock</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($metrics['inStock']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Total available quantity</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">Revenue</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($metrics['revenue'], 2) }}</p>
                <p class="mt-2 text-sm text-slate-500">Accumulated from all recorded sales</p>
            </div>
            <div class="rounded-3xl bg-emerald-600 p-6 text-white shadow-xl shadow-emerald-200/60">
                <p class="text-sm text-emerald-100">Today sales</p>
                <p class="mt-3 text-3xl font-semibold">Rp {{ number_format($metrics['todaySales'], 2) }}</p>
                <p class="mt-2 text-sm text-emerald-100">Sales posted today</p>
            </div>
        </section>

        <section class="auto-layout-sidebar">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="auto-layout-header">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Recent sales</h2>
                        <p class="mt-1 text-sm text-slate-500">Latest transaction summaries and payment details.</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white">Open POS</a>
                </div>

                <div class="auto-layout-scroll mt-6 rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Invoice</th>
                                <th class="px-4 py-3 font-medium">Customer</th>
                                <th class="px-4 py-3 font-medium">Items</th>
                                <th class="px-4 py-3 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-slate-900 hover:text-emerald-600">
                                            {{ $sale->invoice_number }}
                                        </a>
                                        <p class="mt-1 text-xs text-slate-400">{{ $sale->sold_at->format('d M Y H:i') }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ $sale->customer?->name ?? 'Walk-in customer' }}</td>
                                    <td class="px-4 py-3">{{ $sale->items->sum('quantity') }} items</td>
                                    <td class="px-4 py-3 font-semibold text-emerald-600">Rp {{ number_format($sale->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">No sales have been recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($recentSales->hasPages())
                    <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-500">
                            Showing {{ $recentSales->firstItem() }}-{{ $recentSales->lastItem() }} of {{ $recentSales->total() }} sales
                        </p>
                        <div>
                            {{ $recentSales->onEachSide(1)->links() }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Low stock alert</h2>
                <p class="mt-1 text-sm text-slate-500">Products that may need restocking soon.</p>

                <div class="mt-6 space-y-3">
                    @forelse ($lowStockProducts as $product)
                        <div class="auto-layout-card-row rounded-2xl bg-slate-50 px-4 py-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $product->sku }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold {{ $product->stock <= 5 ? 'text-rose-600' : 'text-amber-500' }}">
                                    {{ number_format($product->stock) }} {{ $product->unit }}
                                </p>
                                <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-400">Remaining</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No products are tracked yet.
                        </div>
                    @endforelse
                </div>

                @if ($lowStockProducts->hasPages())
                    <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-500">
                            Showing {{ $lowStockProducts->firstItem() }}-{{ $lowStockProducts->lastItem() }} of {{ $lowStockProducts->total() }} products
                        </p>
                        <div>
                            {{ $lowStockProducts->onEachSide(1)->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
