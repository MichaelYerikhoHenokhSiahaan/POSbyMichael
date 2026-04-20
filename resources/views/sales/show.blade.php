@extends('layouts.app')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-300/40">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-300">Invoice</p>
                    <h2 class="mt-2 text-3xl font-semibold">{{ $sale->invoice_number }}</h2>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('sales.receipt', ['sale' => $sale, 'auto_print' => 1]) }}" target="_blank" class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-medium text-white">Print receipt</a>
                    <a href="{{ route('sales.index') }}" class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white">Back to POS</a>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Customer</p>
                    <p class="mt-2 text-lg font-semibold">{{ $sale->customer?->name ?? 'Walk-in customer' }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $sale->customer?->phone ?? 'No phone recorded' }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Sale details</p>
                    <p class="mt-2 text-lg font-semibold">{{ $sale->payment_method }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $sale->sold_at->format('d M Y H:i') }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Cashier</p>
                    <p class="mt-2 text-lg font-semibold">{{ $sale->cashier_name }}</p>
                </div>
                <div class="rounded-3xl bg-emerald-500 p-4 text-white">
                    <p class="text-sm text-emerald-50">Customer payment</p>
                    <p class="mt-2 text-3xl font-semibold">Rp {{ number_format($sale->customer_payment, 2) }}</p>
                </div>
            </div>

            <div class="mt-8 rounded-3xl bg-white/5 p-4">
                <p class="text-sm text-slate-300">Notes</p>
                <p class="mt-2 text-sm text-white">{{ $sale->notes ?: 'No notes added for this sale.' }}</p>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Purchased items</h2>
            <p class="mt-1 text-sm text-slate-500">Detailed breakdown of this transaction.</p>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Product</th>
                            <th class="px-4 py-3 font-medium">Qty</th>
                            <th class="px-4 py-3 font-medium">Unit price</th>
                            <th class="px-4 py-3 font-medium">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $item->product->name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $item->product->sku }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $item->quantity }} {{ $item->product->unit }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-600">Rp {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 ml-auto max-w-sm space-y-3 rounded-3xl bg-slate-50 p-5">
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($sale->subtotal, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Discount</span>
                    <span>Rp {{ number_format($sale->discount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-semibold text-slate-900">
                    <span>Total</span>
                    <span>Rp {{ number_format($sale->total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Customer payment</span>
                    <span>Rp {{ number_format($sale->customer_payment, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Change</span>
                    <span>Rp {{ number_format($sale->change_amount, 2) }}</span>
                </div>
            </div>
        </section>
    </div>
@endsection
