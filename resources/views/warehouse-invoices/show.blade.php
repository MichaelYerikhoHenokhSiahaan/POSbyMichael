@extends('layouts.app')

@section('content')
    @php($paymentStatus = $sale->payment_status ?? ((float) $sale->customer_payment >= (float) $sale->total ? 'Lunas' : 'Belum Lunas'))
    <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-300/40">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-300">Invoice Gudang</p>
                    <h2 class="mt-2 text-3xl font-semibold">{{ $sale->invoice_number }}</h2>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('warehouse-invoices.receipt', ['sale' => $sale, 'auto_print' => 1]) }}" target="_blank" class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-medium text-white">Cetak invoice</a>
                    <a href="{{ route('warehouse-invoices.index') }}" class="rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-white">Kembali</a>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Pelanggan</p>
                    <p class="mt-2 text-lg font-semibold">{{ $sale->customer?->name ?? 'Pelanggan umum' }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $sale->customer?->phone ?? 'Tidak ada nomor telepon' }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Detail invoice</p>
                    <p class="mt-2 text-lg font-semibold">{{ str($sale->payment_method)->headline() }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $sale->sold_at->format('d M Y H:i') }}</p>
                </div>
                <div class="rounded-3xl bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Kasir</p>
                    <p class="mt-2 text-lg font-semibold">{{ $sale->cashier_name }}</p>
                </div>
                <div class="rounded-3xl bg-emerald-500 p-4 text-white">
                    <p class="text-sm text-emerald-50">Pembayaran pelanggan</p>
                    <p class="mt-2 text-3xl font-semibold">Rp {{ number_format($sale->customer_payment, 2) }}</p>
                </div>
            </div>

            <div class="mt-4">
                <span class="rounded-full px-4 py-2 text-sm font-semibold {{ $paymentStatus === 'Lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $paymentStatus }}
                </span>
            </div>

            <form action="{{ route('warehouse-invoices.update-payment-status', $sale) }}" method="POST" class="mt-4 flex flex-col gap-3 rounded-3xl bg-white/5 p-4 md:flex-row md:items-end">
                @csrf
                @method('PATCH')
                <div class="flex-1">
                    <label for="payment_status" class="text-sm text-slate-300">Ubah status pembayaran</label>
                    <select id="payment_status" name="payment_status" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200" required>
                        <option value="Lunas" @selected($paymentStatus === 'Lunas')>Lunas</option>
                        <option value="Belum Lunas" @selected($paymentStatus === 'Belum Lunas')>Belum Lunas</option>
                    </select>
                </div>
                <button type="submit" class="rounded-full bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20">
                    Simpan status
                </button>
            </form>

            <div class="mt-8 rounded-3xl bg-white/5 p-4">
                <p class="text-sm text-slate-300">Catatan</p>
                <p class="mt-2 text-sm text-white">{{ $sale->notes ?: 'Tidak ada catatan untuk invoice ini.' }}</p>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Item invoice Gudang</h2>
            <p class="mt-1 text-sm text-slate-500">Rincian lengkap produk Gudang pada invoice ini.</p>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Produk</th>
                            <th class="px-4 py-3 font-medium">Qty</th>
                            <th class="px-4 py-3 font-medium">Harga satuan</th>
                            <th class="px-4 py-3 font-medium">Total baris</th>
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
                    <span>Total</span>
                    <span>Rp {{ number_format($sale->total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>Pembayaran pelanggan</span>
                    <span>Rp {{ number_format($sale->customer_payment, 2) }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-semibold text-slate-900">
                    <span>Kembalian</span>
                    <span>Rp {{ number_format($sale->change_amount, 2) }}</span>
                </div>
            </div>
        </section>
    </div>
@endsection
